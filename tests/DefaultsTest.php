<?php
require_once('./BaseTestCase.php');

class DefaultsTest extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }


    public function testSetDefaultsOnlyAcceptsAssocArray() {
        $route = new RecursiveRoute('/');
        $this->setExpectedException('RecursiveRoute_InvalidArgument_Exception');
        $route->setDefaults(array('default1','default2'));
    }


    // TODO: think how to handle defaults when matching parsing and creating params

 }
