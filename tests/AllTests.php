<?php
require_once dirname(__FILE__) . '/EmptyRouteTest.php';
require_once dirname(__FILE__) . '/OneLevelTest.php';
require_once dirname(__FILE__) . '/RecursiveTest.php';
require_once dirname(__FILE__) . '/DefaultsTest.php';
require_once dirname(__FILE__) . '/ValidatorsTest.php';

class RecursiveRoute_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('RecursiveRouteTestSuite');
        $suite->addTestSuite('EmptyRouteTest');
        $suite->addTestSuite('OneLevelTest');
        $suite->addTestSuite('RecursiveTest');
        $suite->addTestSuite('DefaultsTest');
        $suite->addTestSuite('ValidatorsTest');
        return $suite;
    }
}
