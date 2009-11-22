<?php
require_once('./BaseTestCase.php');

class RecursiveTest extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function testAddRouteAcceptsRecursiveRoute() {
        $route = new RecursiveRoute('/module/');
        $route->addRoute(new RecursiveRoute('page/'));

        $url = $route->createUrl();
        $this->assertEquals($url,'/module/page/');
    }

    public function testAddRouteAcceptsString() {
        $route = new RecursiveRoute('/module/');
        $route->addRoute('page/');

        $url = $route->createUrl();
        $this->assertEquals($url,'/module/page/');
    }

    public function testParsingLastRouteHasPrecedence() {
        $route = new RecursiveRoute('/');
        $route->addRoute(':page_id/');
        $route->addRoute(':news_id/');

        $parsed = $route->parseUrl('/20');

        $this->assertArrayHasKey('news_id',$parsed);
        $this->assertArrayNotHasKey('page_id',$parsed);
        $this->assertEquals($parsed['news_id'],20);
    }

    public function testCreatingLastRouteHasPrecedence() {
        $route = new RecursiveRoute('/');
        $route->addRoute(':page_id/');
        $route->addRoute('/pages/:page_id/');

        $url = $route->createUrl(array('page_id'=>1));

        $this->assertEquals($url,'/pages/1/');
    }

    public function testParsingSkipsRouteIfReqPartMissing() {
        $route = new RecursiveRoute('/');
        $route->addRoute('module/:page_id/');
        $route->addRoute('module/:news_id/:news_title');

        $parsed = $route->parseUrl('/module/123');

        $this->assertArrayHasKey('page_id',$parsed);
        $this->assertArrayNotHasKey('news_id',$parsed);
        $this->assertEquals($parsed['page_id'],123);
    }

    public function testCreatingSkipsRouteIfReqPartMissing() {
        $route = new RecursiveRoute('/');
        $route->addRoute('module/:page_id/');
        $route->addRoute('module/:page_id/:page_title');

        $url = $route->createUrl(array('page_id'=>2));

        $this->assertEquals($url,'/module/2/');
    }

    public function testThreeLevelParsing() {
        $route = $this->getThreeLevelRoute();

        // matches news, but adds no param as both subroutes req. 2 params
        $parsed = $route->parseUrl('/news/10/');
        $this->assertEquals(count($parsed),0);

        $parsed = $route->parseUrl('/news/archive/2009/11');
        $this->assertEquals($parsed,array('year'=>'2009','month'=>'11'));

        $parsed = $route->parseUrl('/news/2009/11');
        $this->assertEquals($parsed,array('id'=>'2009','title'=>'11'));

        $parsed = $route->parseUrl('/page/1/myTitle/');
        $this->assertEquals($parsed,array('page_id'=>'1','page_title'=>'myTitle'));

        $parsed = $route->parseUrl('/page/1/');
        $this->assertEquals($parsed,array('page_id'=>'1'));

        $parsed = $route->parseUrl('/page/1/myTitle/key1/value1');
        $this->assertEquals($parsed,array(
            'page_id'=>'1',
            'page_title'=>'myTitle',
            'key1'=>'value1'
        ));
    }

    public function testThreeLevelCreating() {
        $route = $this->getThreeLevelRoute();

        $url = $route->createUrl(array('year'=>2009,'month'=>11));
        $this->assertEquals($url,'/news/archive/2009/11/');

        $url = $route->createUrl(array('id'=>123,'title'=>'newsFlash'));
        $this->assertEquals($url,'/news/123/newsFlash/');

        $url = $route->createUrl(array('page_id'=>123));
        $this->assertEquals($url,'/page/123/');

        $url = $route->createUrl(array('page_id'=>123,'page_title'=>'myTitle'));
        $this->assertEquals($url,'/page/123/myTitle/');

        $url = $route->createUrl(array('page_id'=>123,'page_title'=>'myTitle','key1'=>'value1'));
        $this->assertEquals($url,'/page/123/myTitle/key1/value1/');
    }

    public function testParsingParsesKeyValuePairs() {
        $route = $this->getThreeLevelRoute();

        $parsed = $route->parseUrl('page/123/myTitle/param1/value1');
        $this->assertArrayHasKey('page_id', $parsed);
        $this->assertArrayHasKey('page_title', $parsed);
        $this->assertArrayHasKey('param1', $parsed);
        $this->assertEquals($parsed['param1'], 'value1');

        // this will consider 'param1' to be the page-title
        $parsed = $route->parseUrl('page/123/param1/value1');
        $this->assertArrayHasKey('page_id', $parsed);
        $this->assertArrayHasKey('page_title', $parsed);
        $this->assertArrayNotHasKey('param1', $parsed);
        $this->assertEquals($parsed['page_title'], 'param1');
    }

    public function testCreatingAddsKeyValuePairs() {
        $route = $this->getThreeLevelRoute();

        $url = $route->createUrl(array('param1'=>'value1'));
        $this->assertEquals($url, '/param1/value1/' );

        $url = $route->createUrl(array(
            'year'=>'2009',
            'month'=>11,
            'param1'=>'value1'
        ));
        $this->assertEquals($url, '/news/archive/2009/11/param1/value1/' );
    }

    protected function getThreeLevelRoute() {
        $route = new RecursiveRoute('/');

        $newsRoute = new RecursiveRoute('/news/');
        $newsRoute->addRoute(':id/:title/');
        $newsRoute->addRoute('archive/:year/:month');
        $route->addRoute($newsRoute);

        $pagesRoute = new RecursiveRoute('page');
        $pagesRoute->addRoute(':page_id');
        $pagesRoute->addRoute(':page_id/:page_title');
        $route->addRoute($pagesRoute);

        return $route;
    }
 }
