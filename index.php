<?php

/*
 * Visitor stats addon for Bear CMS
 * https://github.com/bearcms/visitor-stats-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearCMS\VisitorStats;
use BearCMS\VisitorStats\Internal\Utilities;
use BearFramework\App;

$app = App::get();

$context = $app->contexts->get(__FILE__);

$context->classes
    ->add('BearCMS\VisitorStats\BeforeApplyEventDetails', 'classes/VisitorStats/BeforeApplyEventDetails.php')
    ->add('BearCMS\VisitorStats\Internal\Utilities', 'classes/VisitorStats/Internal/Utilities.php')
    ->add('BearCMS\VisitorStats', 'classes/VisitorStats.php');

$app->bearCMS->addons
    ->register('bearcms/visitor-stats-addon', function (\BearCMS\Addons\Addon $addon) use ($app) {
        $addon->initialize = function (array $options) use ($app) {
            $context = $app->contexts->get(__FILE__);

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
                ->add('POST /-vs-log', function (App\Request $request) use ($app, $excludeBotsInPageviews) {
                    $action = $request->formData->getValue('a');
                    $action = $action !== null ? trim((string) urldecode(is_array($action) ? '' : (string) $action)) : '';
                    $data = $request->formData->getValue('d');
                    $data = $data !== null ? json_decode(urldecode($data), true) : null;
                    $userAgent = $request->formData->getValue('u');
                    $userAgent = $userAgent !== null ? trim(strtolower(str_replace(' ', '', (string) $userAgent))) : '';
                    $timeZone = $request->formData->getValue('z');
                    $timeZone = $timeZone !== null ? trim(strtolower((string) $timeZone)) : '';
                    if (!is_array($data)) {
                        $data = [];
                    }
                    $cancel = false;
                    if ($action === 'pageview') {
                        if ($userAgent === '') {
                            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower(str_replace(' ', '', $_SERVER['HTTP_USER_AGENT'])) : '';
                        }
                        if ($excludeBotsInPageviews) {
                            $bots = ['bingpreview', 'bot', 'spider', 'crawl'];
                            foreach ($bots as $bot) {
                                if (strpos($userAgent, $bot) !== false) {
                                    $cancel = true;
                                    break;
                                }
                            }
                        }
                        if (!$cancel) {
                            $data['country'] = Utilities::getCountryCode((string)$timeZone);
                            if ($data['country'] === null) {
                                unset($data['country']);
                            }
                            $data['deviceType'] = strpos($userAgent, 'mobi') !== false ? 'mobile' : 'desktop';
                        }
                    }
                    if (!$cancel) {
                        $app->visitorStats->log($action, $data);
                    }
                    $response = new App\Response\Text('');
                    $response->headers->set($response->headers->make('X-Robots-Tag', 'noindex, nofollow'));
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
