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
 * Prepared statement wrapper class to support auto-reconnect.
 */
class MyPDOStatement
{
	/**
	 * @var MyPDO
	 */
	protected $dbh = null;

	/**
	 * @var PDOStatement
	 */
	protected $pdo_statement = null;

	/**
	 * @var string
	 */
	protected $statement = '';

	protected $driver_options = array();

	public function __construct(& $dbh, $statement, array $driver_options = array())
	{
		$this->dbh = & $dbh;

		$this->statement = $statement;
		$this->driver_options = $driver_options;

		$this->prepare();
	}

	/**
	 * Called by the constructor
	 */
	protected function prepare()
	{
		// since PDO can emulate prepared statement, prepare() do not always
		// communicate with the database, and therefore do not trigger automatic
		// reconnection. Instead we manually check if the connection is alive.
		if ( ! $this->dbh->ping() ) {
			$this->dbh->autoreconnect();
		}

		$this->pdo_statement = $this->dbh->__call('prepare', array($this->statement, $this->driver_options));
	}

	public function __call($function, array $args = array()) 
	{
		if ( null === $this->statement ) {
			throw new \RuntimeException('No statement');
		}

		return call_user_func_array(array($this->pdo_statement, $function), $args);
	}

	/**
	 * This function is a bit special in terms of automatic reconnection. The
	 * reason is, that it seems the database handler is somehow bound to the
	 * pdo statement object. Even when KDO reconnect the database handler,
	 * the previously prepared statement will still think they are disconnected.
	 */
	public function execute($args = array())
	{
		$attempts = 0;
		do {
			try {
				return $this->pdo_statement->execute($args);
			} catch ( \PDOException $e ) {
				if (strpos($e->getMessage(), '2006 MySQL') !== false) {
					// we do not reconnect here. Instead, we need to destroy the
					// prepared statement object, and create a new one.
					$this->prepare();
				}
				else {
					throw $e;
				}
			}
			$attempts += 1;
		} while ($attempts < 3);

		throw new \RuntimeException('Max number of retry exceeded');
	}
}
