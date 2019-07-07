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
            // todo
            $excludeKnownQueryParameters = isset($options['excludeKnownQueryParameters']) ? (int) $options['excludeKnownQueryParameters'] > 0 : true;
            // todo
            $excludeBots = isset($options['excludeBots']) ? (int) $options['excludeBots'] > 0 : true;

            \BearCMS\Internal\Config::$appSpecificServerData['glzm4a4'] = 1;

            \BearCMS\Internal\ServerCommands::add('visitorStatsGet', function (array $data) use ($app) {
                if (isset($data['type'], $data['startDate'], $data['endDate'])) {
                    $type = $data['type'];
                    $startDate = (new DateTime($data['startDate']))->getTimestamp();
                    $endDate = (new DateTime($data['endDate']))->getTimestamp();
                    $result = $app->visitorStats->getStats($startDate, $endDate, [$type]);
                    return $result[$type];
                }
            });

            $app->routes
                ->add('/-vs.js', function () use ($app) {
                    $data = isset($_GET['d']) ? json_decode(urldecode($_GET['d']), true) : null;
                    if (!is_array($data)) {
                        $data = [];
                    }
                    $action = isset($_GET['a']) ? (string) urldecode((string) $_GET['a']) : '';
                    $app->visitorStats->log($action, $data);
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
