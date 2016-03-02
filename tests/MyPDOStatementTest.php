<?php

class MyPDOStatementTest extends PHPUnit_Framework_TestCase
{
	public function testPrepare()
	{
		$dbh = new MyPDO('sqlite::memory:');
		$stmt = $dbh->prepare('SELECT 1');

		$this->assertInstanceOf('MyPDOStatement', $stmt);
	}
}