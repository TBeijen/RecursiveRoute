<?php
require_once dirname(__FILE__) . '/OneLevelTest.php';

class RecursiveRoute_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('RecursiveRouteTestSuite');
        $suite->addTestSuite('OneLevelTest');
        return $suite;
    }
}
