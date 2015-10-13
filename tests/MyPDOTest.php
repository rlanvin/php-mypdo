<?php

class MyPDOTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException PDOException
	 */
	public function testNoAutoReconnect()
	{
		global $config;

		$dbh = new MyPDO($config['dsn'], $config['user'], $config['password'], array(), array(
			'autoreconnect' => false
		));
		$dbh->exec('SET session wait_timeout = 1');

		sleep(2);

		$result = $dbh->query('SELECT 1');
	}

	public function testAutoReconnect()
	{
		global $config;

		$dbh = new MyPDO($config['dsn'], $config['user'], $config['password']);
		$dbh->exec('SET session wait_timeout = 1');

		sleep(2);

		$result = $dbh->query('SELECT 1');
	}
}