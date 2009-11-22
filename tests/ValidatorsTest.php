<?php
require_once('./BaseTestCase.php');

class ValidatorsTest extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }


    public function testSetValidatorsOnlyAcceptsAssocArray() {
        $route = new RecursiveRoute('/');
        $this->setExpectedException('RecursiveRoute_InvalidArgument_Exception');
        $route->setValidators(array('default1','default2'));
    }

    public function testValidatorsCanBeSuppliedInConstructor() {
        $route = new RecursiveRoute(
            '/page/:page_id/',
            array(
                'validators' => array(
                    'page_id' => '/^\d+$/'
                )
            )
        );

        // 'someId' won't validate, so route won't match:
        // param will be appended as key/value
        $url = $route->createUrl(array('page_id'=>'someId'));
        $this->assertEquals($url,'/page_id/someId/');
    }
    
    public function testParsingUsesValidator() {
        $route = $this->getRoute();

        $parsed = $route->parseUrl('/page/myTitle');
        $this->assertArrayHasKey('module',$parsed);
        $this->assertArrayHasKey('title',$parsed);
        $this->assertArrayNotHasKey('id',$parsed);
        $this->assertEquals($parsed['module'],'page');
        $this->assertEquals($parsed['title'],'myTitle');
    }

    public function testCreatingUsesValidator() {
        $route = $this->getRoute();

        $url = $route->createUrl(array('module'=>'page','id'=>'123'));
        $this->assertEquals($url,'/page/123/');

        // route with id will be ignored. Values appended as key/value
        $url = $route->createUrl(array('module'=>'page','id'=>'someId'));
        $this->assertEquals($url,'/module/page/id/someId/');
    }

    // TODO: think how to handle defaults when matching parsing and creating params


    protected function getRoute() {
        $route = new RecursiveRoute('/');
        $subRoute1 = new RecursiveRoute(':module/:title');
        $subRoute2 = new RecursiveRoute(':module/:id');
        $subRoute2->setValidators( array('id'=>'/^\d+$/'));
        $route->addRoute($subRoute1);
        $route->addRoute($subRoute2);

        return $route;
    }
 }
