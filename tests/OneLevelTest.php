<?php
require_once('./BaseTestCase.php');

class OneLevelTest extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function testParsing() {
        $route = new RecursiveRoute(':param1/:param2');
        $parsed = $route->parseUrl('/value1/value2/');
        
        $this->assertArrayHasKey('param1',$parsed);
        $this->assertTrue($parsed['param1']=='value1');
        $this->assertArrayHasKey('param2',$parsed);
        $this->assertTrue($parsed['param2']=='value2');
    }

    public function testParsingIgnoresExcessUrl() {
        $route = new RecursiveRoute(':param1/:param2');
        $parsed = $route->parseUrl('/value1/value2/value3');

        $this->assertArrayHasKey('param1',$parsed);
        $this->assertTrue($parsed['param1']=='value1');
        $this->assertArrayHasKey('param2',$parsed);
        $this->assertTrue($parsed['param2']=='value2');
    }

    public function testParsingPatternContainsStaticParts() {
        $route = new RecursiveRoute('/static1/:param1/static2/:param2');

        $parsed = $route->parseUrl('/static1/value1/somethingElse/value2');
        $this->assertEquals($parsed, array());

        $parsed = $route->parseUrl('/somethingelse/value1/static2/value2');
        $this->assertEquals($parsed, array());

        $parsed = $route->parseUrl('/static1/value1/static2/value2');
        $this->assertArrayHasKey('param1',$parsed);
        $this->assertTrue($parsed['param1']=='value1');
        $this->assertArrayHasKey('param2',$parsed);
        $this->assertTrue($parsed['param2']=='value2');
    }

    public function testParsingPatternAlsoParsesKeyValuePairs() {
        $route = new RecursiveRoute('/static1/:param1/static2/:param2');

        $parsed = $route->parseUrl('static1/value1/static2/value2/param3/value3');
        $this->assertArrayHasKey('param1',$parsed);
        $this->assertTrue($parsed['param1']=='value1');
        $this->assertArrayHasKey('param2',$parsed);
        $this->assertTrue($parsed['param2']=='value2');
        $this->assertArrayHasKey('param3',$parsed);
        $this->assertTrue($parsed['param3']=='value3');
    }

    public function testCreatingWithoutRequiredParamsThrowsException() {
        $route = new RecursiveRoute('/static1/:param1/static2/:param2');
        $this->setExpectedException('RecursiveRoute_InvalidArgument_Exception');
        $url = $route->createUrl(array('param1'=>'value1'));
    }

    public function testCreatingStaticRouteReturnsStaticPart() {
        $route = new RecursiveRoute('/static1/');
        $url = $route->createUrl(array());
        $this->assertEquals($url,'/static1/');
    }

    public function testCreatingStaticRouteAppendsKeyValuePairs() {
        $route = new RecursiveRoute('/static1/');
        $url = $route->createUrl(array('key1'=>'value1'));
        $this->assertEquals($url,'/static1/key1/value1/');
    }

    public function testCreatingUsesParameters() {
        $route = new RecursiveRoute('/static1/:param1/static2/:param2/static3');

        $url = $route->createUrl(array(
            'param1' => 'value1',
            'param2' => 'value2'
        ));
        $this->assertEquals($url,'/static1/value1/static2/value2/static3/');
    }
 }
