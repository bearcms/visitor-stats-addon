<?php

/*
 * Visitor stats addon for Bear CMS
 * https://github.com/bearcms/visitor-stats-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearCMS\VisitorStats;
use BearFramework\App;

$app = App::get();

$context = $app->contexts->get(__FILE__);

$context->classes
    ->add('BearCMS\VisitorStats\BeforeApplyEventDetails', 'classes/VisitorStats/BeforeApplyEventDetails.php')
    ->add('BearCMS\VisitorStats', 'classes/VisitorStats.php');

$app->bearCMS->addons
    ->register('bearcms/visitor-stats-addon', function (\BearCMS\Addons\Addon $addon) use ($app) {
        $addon->initialize = function (array $options) use ($app) {
            $context = $app->contexts->get(__FILE__);

            \BearCMS\Internal\Config::$robotsTxtDisallow[] = '/-vs.js';

            $context->assets->addDir('assets');

            $app->shortcuts
                ->add('visitorStats', function () {
                    return new VisitorStats();
                });

            $app->localization
                ->addDictionary('en', function () use ($context) {
                    return include $context->dir . '/locales/en.php';
                })
                ->addDictionary('bg', function () use ($context) {
                    return include $context->dir . '/locales/bg.php';
                })
                ->addDictionary('ru', function () use ($context) {
                    return include $context->dir . '/locales/ru.php';
                });

            $autoTrackPageviews = isset($options['autoTrackPageviews']) ? (int) $options['autoTrackPageviews'] > 0 : true;
            $excludeBotsInPageviews = isset($options['excludeBotsInPageviews']) ? (int) $options['excludeBotsInPageviews'] > 0 : true;

            \BearCMS\Internal\Config::$appSpecificServerData['glzm4a4'] = 1;

            \BearCMS\Internal\ServerCommands::add('visitorStatsGet', function (array $data) use ($app) {
                return $app->visitorStats->getStats($data);
            });

            $app->routes
                ->add('/-vs.js', function (App\Request $request) use ($app, $excludeBotsInPageviews) {
                    $action = isset($_GET['a']) ? trim((string) urldecode((string) $_GET['a'])) : '';
                    $data = isset($_GET['d']) ? json_decode(urldecode($_GET['d']), true) : null;
                    $userAgent = isset($_GET['u']) ? trim(strtolower(str_replace(' ', '', (string) $_GET['u']))) : '';
                    if (!is_array($data)) {
                        $data = [];
                    }
                    $cancel = false;
                    if ($action === 'pageview') {
                        $serverUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower(str_replace(' ', '', $_SERVER['HTTP_USER_AGENT'])) : '';
                        $anonymizedUserAgent = preg_replace('/[0-9]/', '*', $userAgent !== '' ? $userAgent : $serverUserAgent);
                        if ($anonymizedUserAgent === '') {
                            $anonymizedUserAgent = 'unknown';
                        }
                        if ($excludeBotsInPageviews) {
                            $bots = ['bingpreview', 'bot', 'spider', 'crawl'];
                            foreach ($bots as $bot) {
                                if (strpos($anonymizedUserAgent, $bot) !== false || strpos($serverUserAgent, $bot) !== false) {
                                    $cancel = true;
                                    break;
                                }
                            }
                        }
                        if (!$cancel) {
                            $data['anonymizedUserAgent'] = $anonymizedUserAgent;
                            // if (isset($data['url'])) {
                            //     $query = parse_url($data['url'], PHP_URL_QUERY);
                            //     if (strlen($query) > 0) {
                            //         $temp = null;
                            //         parse_str($query, $temp);
                            //         if (isset($temp['-vssource'])) {
                            //             $data['source'] = trim($temp['-vssource']);
                            //         }
                            //     }
                            // }
                            $ip = (string)$request->client->ip;
                            if (strlen($ip) > 0) {
                                $getCountryCode = function ($ip) {
                                    if ($ip === '127.0.0.1') {
                                        return null;
                                    }
                                    $function = require __DIR__ . '/countries-db/result.php';
                                    return $function($ip);
                                };
                                $data['country'] = $getCountryCode($ip);
                            }
                            $data['deviceType'] = strpos($anonymizedUserAgent, 'mobi') !== false ? 'mobile' : 'desktop';
                        }
                    }
                    if (!$cancel) {
                        $app->visitorStats->log($action, $data);
                    }
                    $response = new App\Response('{}');
                    $response->headers->set($response->headers->make('Content-Type', 'text/javascript; charset=UTF-8'));
                    $response->headers->set($response->headers->make('Cache-Control', 'no-cache, no-store, must-revalidate'));
                    return $response;
                });

            if ($autoTrackPageviews) {
                $app->addEventListener('beforeSendResponse', function (App\BeforeSendResponseEventDetails $details) use ($app) {
                    if ($details->response instanceof App\Response\HTML) {
                        $app->visitorStats->apply($details->response, ['trackPageview' => true]);
                    }
                });
            }
        };
    });
