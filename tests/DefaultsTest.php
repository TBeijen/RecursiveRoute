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

    public function testDefaultsOnlyAllowedForEndParts() {
        $route = new RecursiveRoute('/page/:page_id/:page_title/');

        $this->setExpectedException('RecursiveRoute_InvalidArgument_Exception');
        $route->setDefaults(array(
            'page_id'=>1
        ));
    }

    public function testDefaultsNotAllowedIfRouteEndsWithStaticPart() {
        // first check that setting defaults doesn't throw exc.
        $route = new RecursiveRoute('/page/:page_id/:page_title/');
        $route->setDefaults(array(
            'page_id' => 10,
            'page_title' => 'home'
        ));

        // now the route ends with the static part 'json'. no defaults allowed.
        $route = new RecursiveRoute('/page/:page_id/:page_title/json/');
        $this->setExpectedException('RecursiveRoute_InvalidArgument_Exception');
        $route->setDefaults(array(
            'page_id' => 10,
            'page_title' => 'home'
        ));
    }

    public function testParsingDefaultsOnlyUsedWhenNotInUrl() {
        $route = new RecursiveRoute('/page/:page_id/');
        $route->setDefaults(array(
            'page_id' => 10,
        ));

        $parsed = $route->parseUrl('/page/20');
        $this->assertArrayHasKey('page_id',$parsed);
        $this->assertTrue($parsed['page_id']==20);
    }

    public function testParsingDefaultsCanExtendBeyondDefinedParams() {
        $route = new RecursiveRoute('/page/:page_id/');
        $route->setDefaults(array(
            'page_id' => 10,
            'page_title' => 'home'
        ));

        $parsed = $route->parseUrl('/page/');
        $this->assertArrayHasKey('page_id',$parsed);
        $this->assertTrue($parsed['page_id']==10);
        $this->assertArrayHasKey('page_title',$parsed);
        $this->assertTrue($parsed['page_title']=='home');
    }

    public function testCreatingUsesDefaultsWhenNotSupplied() {
        $route = new RecursiveRoute('/page/:page_id/:page_title');
        $route->setDefaults(array(
            'page_id' => 10,
            'page_title' => 'home'
        ));

        $url = $route->createUrl(array('page_id'=>20));
        $this->assertEquals($url, '/page/20/home/');
    }
 }
