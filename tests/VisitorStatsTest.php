<?php

/*
 * Visitor stats addon for Bear CMS
 * https://github.com/bearcms/visitor-stats-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class VisitorStatsTest extends BearCMS\AddonTests\PHPUnitTestCase
{

    /**
     *
     */
    public function testShortcut()
    {
        $app = $this->getApp();
        $this->assertTrue($app->visitorStats instanceof \BearCMS\VisitorStats);
    }

    /**
     *
     */
    public function testInitialize()
    {
        $app = $this->getApp();
        $request = new \BearFramework\App\Request();
        $request->method = 'GET';
        $request->path->set('/-vs.js');
        $request->query->set($request->query->make('a', 'test-action'));
        $request->query->set($request->query->make('d', json_encode(['some-data' => 'some-value'])));
        $response = $app->routes->getResponse($request);
        $this->assertTrue($response instanceof \BearFramework\App\Response);
    }

    /**
     *
     */
    public function testApply()
    {
        $app = $this->getApp();
        $request = new \BearFramework\App\Response\HTML('hello');
        $app->visitorStats->apply($request, ['trackPageview' => true]);
        $this->assertTrue(true);
    }

    /**
     *
     */
    public function testGetStats()
    {
        $app = $this->getApp();
        $app->visitorStats->log('pageview', ['url' => 'http://example.com/', 'referrer' => '', 'deviceType' => 'mobile']); // session start
        $app->visitorStats->log('pageview', ['url' => 'http://example.com/', 'referrer' => '', 'country' => 'us', 'deviceType' => 'mobile']); // session start
        $app->visitorStats->log('pageview', ['url' => 'http://example.com/products/', 'referrer' => 'http://example.com/', 'country' => 'de', 'deviceType' => 'desktop']);
        $app->visitorStats->log('pageview', ['url' => 'http://example.com/products/', 'referrer' => 'example.com', 'country' => 'de', 'deviceType' => 'desktop']);
        $app->visitorStats->log('pageview', ['url' => 'http://example.com/services/', 'referrer' => 'http://google.com/']); // session start
        $app->visitorStats->log('pageview', ['url' => 'http://example.com/products/', 'referrer' => 'google.com']); // session start
        $app->visitorStats->log('pageview', ['url' => 'http://example.com/products/', 'referrer' => 'bing.com']); // session start
        $app->visitorStats->log('pageview', ['url' => 'http://example.com/contacts/', 'referrer' => 'http://yahoo.com/asdads/']); // session start
        $app->visitorStats->log('pageview', ['url' => 'http://example.com/абвгдежз/', 'referrer' => '']); // session start
        $currentTime = time();
        $from = $currentTime - 7 * 86400;
        $to = $currentTime;
        $result = $app->visitorStats->getStats([
            ['type' => 'lastPageviews', 'limit' => 3],
            ['type' => 'pageviewsPerDayCount', 'from' => $from, 'to' => $to, 'addEmptyDays' => true, 'sortByDate' => 'asc'],
            ['type' => 'sessionsPerDayCount', 'from' => $from, 'to' => $to, 'addEmptyDays' => true, 'sortByDate' => 'asc'],
            ['type' => 'sourcesVisitsCount', 'from' => $from, 'to' => $to],
            ['type' => 'landingPagesCount', 'from' => $from, 'to' => $to],
            ['type' => 'pageviewsPerPageCount', 'from' => $from, 'to' => $to],
            ['type' => 'deviceTypesPageviewsCount', 'from' => $from, 'to' => $to],
            ['type' => 'countriesPageviewsCount', 'from' => $from, 'to' => $to],
        ]);
        // todo improve
        $this->assertTrue(!empty($result['lastPageviews']));
        $this->assertTrue(!empty($result['pageviewsPerDayCount']));
        $this->assertTrue(!empty($result['sessionsPerDayCount']));
        $this->assertTrue(!empty($result['sourcesVisitsCount']));
        $this->assertTrue(!empty($result['landingPagesCount']));
        $this->assertTrue(!empty($result['pageviewsPerPageCount']));
        $this->assertTrue(!empty($result['deviceTypesPageviewsCount']));
        $this->assertTrue(!empty($result['countriesPageviewsCount']));
    }
}
