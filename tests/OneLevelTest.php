<?php
require_once('./BaseTestCase.php');

class OneLevelTest extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function testCanCreateEmptyRoute() {
        $route = new RecursiveRoute();

        $this->assertTrue($route instanceof RecursiveRoute);
    }

 }
