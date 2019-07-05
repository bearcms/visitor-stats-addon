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
class VisitorStatsTest extends BearFramework\AddonTests\PHPUnitTestCase
{

    protected function initializeApp(bool $setLogger = true, bool $setDataDriver = true, bool $setCacheDriver = true, bool $addAddon = true): \BearFramework\App
    {
        $app = parent::initializeApp($setLogger, $setDataDriver, $setCacheDriver, false);
        $app->addons->add('bearcms/bearframework-addon');
        $app->bearCMS->initialize([]);
        $app->bearCMS->addons->add('bearcms/visitor-stats-addon');
        return $app;
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
    // public function testPageviewsCount()
    // {

    //     BearCMS\Addons\VisitorStats::log('pageview', []);
    //     BearCMS\Addons\VisitorStats::log('pageview', []);
    //     BearCMS\Addons\VisitorStats::log('pageview', []);
    //     $currentTime = time();
    //     $result = BearCMS\Addons\VisitorStats::getPageviewsCount($currentTime - 86400, $currentTime);
    //     $expectedResult = [
    //         [date('Y-m-d', $currentTime - 86400), 0],
    //         [date('Y-m-d', $currentTime), 3],
    //     ];
    //     $this->assertEquals($result, $expectedResult);
    // }

    /**
     *
     */
    // public function testSessions()
    // {

    //     BearCMS\Addons\VisitorStats::log('pageview', ['url' => 'http://example.com/']); // session start
    //     BearCMS\Addons\VisitorStats::log('pageview', ['url' => 'http://example.com/', 'referrer' => '']); // session start
    //     BearCMS\Addons\VisitorStats::log('pageview', ['url' => 'http://example.com/products/', 'referrer' => 'http://example.com/']);
    //     BearCMS\Addons\VisitorStats::log('pageview', ['url' => 'http://example.com/products/', 'referrer' => 'example.com']);
    //     BearCMS\Addons\VisitorStats::log('pageview', ['url' => 'http://example.com/products/', 'referrer' => 'http://google.com/']); // session start
    //     BearCMS\Addons\VisitorStats::log('pageview', ['url' => 'http://example.com/products/', 'referrer' => 'google.com']); // session start
    //     $currentTime = time();
    //     $result = BearCMS\Addons\VisitorStats::getSessionsCount($currentTime, $currentTime);
    //     $expectedResult = [
    //         [date('Y-m-d', $currentTime), 4],
    //     ];
    //     $this->assertEquals($result, $expectedResult);
    // }

}
