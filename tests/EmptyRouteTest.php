<?php
require_once('./BaseTestCase.php');

class EmptyRouteTest extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function testCanCreateEmptyRoute() {
        $route = new RecursiveRoute();
        $this->assertTrue($route instanceof RecursiveRoute);
    }

    public function testEmptyPatternParsesKeyValuePairs() {
        $route = new RecursiveRoute('');
        $parsed = $route->parseUrl('/key1/value1/key2/value2');
        $this->assertArrayHasKey('key1',$parsed);
        $this->assertArrayHasKey('key2',$parsed);
        $this->assertTrue($parsed['key1']=='value1');
        $this->assertTrue($parsed['key2']=='value2');

        $route = new RecursiveRoute('/');
        $parsed = $route->parseUrl('key1/value1/key2/value2/');
        $this->assertArrayHasKey('key1',$parsed);
        $this->assertArrayHasKey('key2',$parsed);
        $this->assertTrue($parsed['key1']=='value1');
        $this->assertTrue($parsed['key2']=='value2');
    }

    public function testParsingKeyValuePairsIgnoresUnspecifiedValues() {
        $route = new RecursiveRoute('/');
        $parsed = $route->parseUrl('/key1/value1/key2/value2/key3');
        $this->assertArrayHasKey('key1',$parsed);
        $this->assertArrayHasKey('key2',$parsed);
        $this->assertArrayNotHasKey('key3',$parsed);
    }

    public function testParsingKeyValuePairsIgnoresNumericKeys() {
        $route = new RecursiveRoute('/');

        $parsed = $route->parseUrl('/1/value1/key2/value2');
        $this->assertArrayHasKey('key2',$parsed);
        $this->assertTrue($parsed['key2']=='value2');
        $this->assertTrue(count($parsed)==1);

        $parsed = $route->parseUrl('/false/value1/key2/value2');
        $this->assertArrayHasKey('false',$parsed);
        $this->assertTrue(count($parsed)==2);

        $parsed = $route->parseUrl('/null/value1/key2/value2');
        $this->assertArrayHasKey('null',$parsed);
        $this->assertTrue(count($parsed)==2);

        $parsed = $route->parseUrl('/1.2/value1/key2/value2');
        $this->assertArrayNotHasKey('1.2',$parsed);
        $this->assertTrue(count($parsed)==1);

        $parsed = $route->parseUrl('/-1/value1/key2/value2');
        $this->assertArrayNotHasKey('-1',$parsed);
        $this->assertTrue(count($parsed)==1);
    }

    public function testCreateUrlNotsupplyingArrayThrowsException() {
        $route = new RecursiveRoute('/');

        $this->setExpectedException('RecursiveRoute_InvalidArgument_Exception');
        $url = $route->createUrl('test');
    }

    public function testCreateUrlTreatsParamsAsKeyValuePairs() {
        $route = new RecursiveRoute('/');

        $url = $route->createUrl(array(
            'key1' => 'value1',
            'key2' => 'value2'
        ));
        $this->assertEquals($url,'/key1/value1/key2/value2/');
    }
 }