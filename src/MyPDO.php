<?php

/**
 * Licensed under the MIT license.
 *
 * For the full copyright and license information, please view the LICENSE file.
 *
 * @author Rémi Lanvin <remi@cloudconnected.fr>
 * @author Developer's Helsinki Oy
 *
 * @link https://github.com/rlanvin/php-mypdo
 */

/**
 * MyPDO is a lightweight wrapper for PHP's PDO to add a few features
 * missing from the original:
 * - Disconnection
 * - Auto-reconnection after timeout
 * - Nested transactions
 * - Methods chainability
 *
 * MyPDO is designed for MySQL.
 *
 * This class can be used directly as a drop-in replacement.
 *
 * This class will NOT add higher logic to PDO (such as lazy loading, data mapping, etc.)
 */
class MyPDO
{
	/**
	 * @var PDO
	 */
	protected $pdo = null;

	protected $dsn = '';
	protected $user = '';
	protected $pass = '';

	/**
	 * @var array Stores PDO's $options constructor parameter (which, for some
	 *            reason is different than the "attributes").
	 *            Also used for class-specific settings.
	 */
	protected $pdo_options = array();

	/**
	 * @var array Stores the class options
	 */
	protected $options = array(
		'autoreconnect' => true
	);

	/**
	 * @var array Stores the current attributes (whiwh are for some reason
	 *            different than the "options" passed in the construtor)
	 */
	protected $attributes = array();

	/**
	 * @var int Stores the number of active transactions for nesting
	 */
	protected $active_transactions = 0;

	/**
	 * @var string Store the last "USE" query in order to reconnect to the same database
	 */
	protected $last_use = '';

	/**
	 * Create a new PDO handler.
	 * @param $password pass an array to hide the password from the stacktrace
	 */
	public function __construct($dsn, $username = '', $password = '', array $pdo_options = array(), array $options = array())
	{
		$this->dsn = $dsn;
		$this->user = $username;
		$this->pass = is_array($password) ? $password[0] : $password;
		$this->pdo_options = $pdo_options;
		$this->options = array_merge($this->options, $options);

		$this->connect();

		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * This function will record all attributes set, to reset them on reconnection.
	 */
	public function setAttribute($attribute, $value)
	{
		$this->attributes[$attribute] = $value;

		$this->pdo->setAttribute($attribute, $value);

		return $this;
	}

	/**
	 * Connect to the database
	 * @return $this;
	 */
	public function connect()
	{
		try {
			$this->pdo = new PDO($this->dsn, $this->user, $this->pass, $this->pdo_options);
		} catch (PDOException $e) {
			// rethrow the exception to hide the password in the stack trace
			throw new PDOException($e->getMessage(), $e->getCode());
		}
		$this->active_transactions = 0;

		// reset all PDO's attributes.
		foreach ( $this->attributes as $attribute => $value ) {
			$this->pdo->setAttribute($attribute, $value);
		}

		return $this;
	}

	/**
	 * Explicitely reconnect to the database
	 */
	public function reconnect()
	{
		$this->disconnect();
		$this->connect();
		return $this;
	}
	
	/**
	 * @internal You shouldn't use this function, but it's public because MyPDOStatement needs it.
	 */
	public function autoreconnect()
	{
		if ( $this->active_transactions > 0 ) {
			throw new RuntimeException('2006 MySQL has gone away during an active transaction.');
		}

		$this->disconnect();
		$this->connect();
		
		if ( $this->last_use ) {
			$this->pdo->exec($this->last_use);
		}

		return $this;
	}

	/**
	 * Explicitely disconnect from the database
	 * @return $this;
	 */
	public function disconnect()
	{
		$this->pdo = null;
		return $this;
	}

	/**
	 * Return the raw PDO object.
	 * @return PDO
	 */
	public function getPDO()
	{
		return $this->pdo;
	}

	/**
	 * Create a prepared statement.
	 * This methods will replace PDOStatement by MyPDOStatement
	 * @return MyPDOStatement
	 */
	public function prepare($statement, array $driver_options = array())
	{
		return new MyPDOStatement($this, $statement, $driver_options);
	}

	/**
	 * Ping the connection. Return true if still connected to the database, false otherwise.
	 * This function does not reconnect.
	 * @return bool
	 */
	public function ping()
	{
		if ( $this->pdo === null ) {
			return false;
		}

		try {
			$this->pdo->query('SELECT 1');
			return true;
		} catch ( PDOException $e ) {
			return false;
		}
	}

	/**
	 * Return the connection ID, or null if the connection has timed out.
	 * @return string or NULL
	 */
	public function getConnectionId()
	{
		if ( $this->pdo === null ) {
			return null;
		}

		try {
			return $this->pdo->query('SELECT CONNECTION_ID()')->fetch(PDO::FETCH_COLUMN);
		} catch ( PDOException $e ) {
			return null;
		}
	}

	/**
	 * Wrap every function call to catch timeout and attempt to reconnect.
	 *
	 * This function will NOT auto-reconnect if transactions were active.
	 */
	public function __call($function, array $args = array()) 
	{
		if ( null === $this->pdo ) {
			throw new RuntimeException('Not connected to DB');
		}

		// save the current database for later reconnection
		if ( ($function == 'exec' || $function == 'query') && substr($args[0],0,4) == 'USE ') {
			$this->last_use = $args[0];
		}

		$attempts = 0;
		do {
			try {
				return @call_user_func_array(array($this->pdo, $function), $args);
			} catch ( PDOException $e ) {
				if ( $this->options['autoreconnect'] && strpos($e->getMessage(), '2006 MySQL') !== false ) {
					$this->autoreconnect();
				}
				else {
					throw $e;
				}
			}
			$attempts += 1;
		} while ($attempts < 3);

		throw new RuntimeException('Max number of retry exceeded');
	}

/**
 * @name Nested transaction support
 */
//@{

	/**
	 * Return the number of active transactions
	 */
	public function getActiveTransactions()
	{
		return $this->active_transactions;
	}

	/**
	 * Wrapper for PDO's beginTransaction.
	 *
	 * This wrapper will use SAVEPOINT instead of beginTransaction when
	 * starting a nested transaction (when a transaction is already active).
	 *
	 * @see http://www.php.net/manual/en/pdo.begintransaction.php
	 */
	public function beginTransaction()
	{
		if ( $this->active_transactions == 0 ) {
			// we can attempt an auto-reconnect here, it doesn't matter
			$this->__call('beginTransaction');
		}
		else {
			$this->pdo->exec(sprintf('SAVEPOINT T%d', $this->active_transactions));
		}

		$this->active_transactions += 1;

		return $this;
	}

	/**
	 * Wrapper for PDO's commit.
	 *
	 * This wrapper will use RELEASE SAVEPOINT when commiting a nested transaction.
	 *
	 * @see http://www.php.net/manual/en/pdo.commit.php
	 */
	public function commit()
	{
		if ( $this->active_transactions == 0 ) {
			throw new RuntimeException('Commit failed, no active transaction.');
		}

		$this->active_transactions -= 1;
		
		if ( $this->active_transactions == 0 ) {
			return $this->pdo->commit();
		}
		else {
			$this->pdo->exec(sprintf('RELEASE SAVEPOINT T%d', $this->active_transactions));
			return true;
		}
	}
	
	/**
	 * Wrapper for PDO's rollback.
	 *
	 * This wrapper will use ROLLBACK TO SAVEPOINT when a nested transaction is
	 * rolled back.
	 * 
	 * This function will not attempt to auto-reconnect, because if the session
	 * has timed out, so has any active transaction, and thus an exception shall be
	 * raised.
	 *
	 * @see http://www.php.net/manual/en/pdo.rollback.php
	 */
	public function rollback()
	{
		if ( $this->active_transactions == 0 ) {
			throw new RuntimeException('Rollback failed, no active transaction.');
		}

		$this->active_transactions -= 1;

		if ( $this->active_transactions == 0 ) {
			return $this->pdo->rollback();
		}
		else {
			$this->pdo->exec(sprintf('ROLLBACK TO SAVEPOINT T%d', $this->active_transactions));
			return true;
		}
	}
//@}
}
