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
    ->add('BearCMS\VisitorStats', 'classes/VisitorStats.php');

$app->shortcuts
    ->add('visitorStats', function () {
        return new VisitorStats();
    });

$app->bearCMS->addons
    ->register('bearcms/visitor-stats-addon', function (\BearCMS\Addons\Addon $addon) use ($app) {
        $addon->initialize = function (array $options) use ($app) {

            $autoTrackPageviews = isset($options['autoTrackPageviews']) ? (int) $options['autoTrackPageviews'] > 0 : true;
            $excludeBotsInPageviews = isset($options['excludeBotsInPageviews']) ? (int) $options['excludeBotsInPageviews'] > 0 : true;

            \BearCMS\Internal\Config::$appSpecificServerData['glzm4a4'] = 1;

            \BearCMS\Internal\ServerCommands::add('visitorStatsGet', function (array $data) use ($app) {
                return $app->visitorStats->getStats($data);
            });

            $app->routes
                ->add('/-vs.js', function () use ($app, $excludeBotsInPageviews) {
                    $action = isset($_GET['a']) ? trim((string) urldecode((string) $_GET['a'])) : '';
                    $data = isset($_GET['d']) ? json_decode(urldecode($_GET['d']), true) : null;
                    if (!is_array($data)) {
                        $data = [];
                    }
                    $cancel = false;
                    if ($action === 'pageview') {
                        $anonymizedUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? preg_replace('/[0-9]/', '*', strtolower(str_replace(' ', '', $_SERVER['HTTP_USER_AGENT']))) : 'unknown';
                        if ($excludeBotsInPageviews) {
                            $bots = ['bingpreview', 'googlebot'];
                            foreach ($bots as $bot) {
                                if (strpos($anonymizedUserAgent, $bot) !== false) {
                                    $cancel = true;
                                    break;
                                }
                            }
                        }
                        if (!$cancel) {
                            $data['anonymizedUserAgent'] = $anonymizedUserAgent;
                            if (isset($data['url'])) {
                                $query = parse_url($data['url'], PHP_URL_QUERY);
                                if (strlen($query) > 0) {
                                    $temp = null;
                                    parse_str($query, $temp);
                                    if (isset($temp['-vssource'])) {
                                        $data['source'] = trim($temp['-vssource']);
                                    }
                                }
                            }
                            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                            } else {
                                $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
                            }
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
