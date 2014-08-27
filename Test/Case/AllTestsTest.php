<?php

/*
 * Custom test suite to execute all tests
 */

class AllTestsTest extends PHPUnit_Framework_TestSuite
{
	public static function suite()
	{
		$suite = new CakeTestSuite('All CakeFileStorage tests');
		$suite->addTestDirectoryRecursive(App::pluginPath('CakeFileStorage') . 'Test' . DS  . 'Case');

		return $suite;
	}
}