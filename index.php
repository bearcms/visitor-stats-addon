<?php

/*
 * Visitor stats addon for Bear CMS
 * https://github.com/bearcms/visitor-stats-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearCMS\Addons\VisitorStats;
use BearFramework\App;
use IvoPetkov\HTML5DOMDocument;

$app = App::get();

$context = $app->contexts->get(__FILE__);

$context->classes
    ->add('BearCMS\Addons\VisitorStats', 'classes/VisitorStats.php');

$app->bearCMS->addons
    ->register('bearcms/visitor-stats-addon', function (\BearCMS\Addons\Addon $addon) use ($app) {
        $addon->initialize = function (array $options) use ($app) {

            \BearCMS\Internal\Config::$appSpecificServerData['glzm4a4'] = 1;

            \BearCMS\Internal\ServerCommands::add('visitorStatsGet', function (array $data) {
                if (isset($data['type'], $data['startDate'], $data['endDate'])) {
                    $type = $data['type'];
                    $startDate = (new DateTime($data['startDate']))->getTimestamp();
                    $endDate = (new DateTime($data['endDate']))->getTimestamp();
                    $result = VisitorStats::getStats($startDate, $endDate, [$type]);
                    return $result[$type];
                }
            });

            $app->routes
                ->add('/-vs.js', function () {
                    $data = isset($_GET['d']) ? json_decode(urldecode($_GET['d']), true) : null;
                    if (!is_array($data)) {
                        $data = [];
                    }
                    $action = isset($_GET['a']) ? (string) urldecode((string) $_GET['a']) : '';
                    VisitorStats::log($action, $data);
                    $response = new App\Response('{}');
                    $response->headers->set($response->headers->make('Content-Type', 'text/javascript; charset=UTF-8'));
                    $response->headers->set($response->headers->make('Cache-Control', 'no-cache, no-store, must-revalidate'));
                    return $response;
                });

            //     if ($this->logCurrentRequest) {
            //         if ((string) $app->request->path !== '/-vs.js') {
            //             $data = [];
            //             $data['url'] = $app->request->getURL();
            //             $data['method'] = $app->request->method;
            //             $referrer = isset($_SERVER['HTTP_REFERER']) ? (string) parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '';
            //             if (!empty($referrer)) {
            //                 $data['referrer'] = $referrer;
            //             }
            //             $this->log('request', $data);
            //         }
            //     }

            $app->addEventListener('beforeSendResponse', function (App\BeforeSendResponseEventDetails $details) use ($app) {
                if ($app->bearCMS->currentUser->exists()) {
                    return;
                }
                $response = $details->response;
                if ($response instanceof App\Response\HTML) {
                    $htmlToInsert = '';
                    // taken from dev/library.js
                    $htmlToInsert .= str_replace('INSERT_URL_HERE', $app->urls->get('/-vs.js'), '<script>var vsjs="undefined"!==typeof vsjs?vsjs:function(){return{log:function(b,c){"undefined"===typeof b&&(b="");"undefined"===typeof c&&(c={});var a=document.createElement("script");a.type="text/javascript";a.async=!0;a.src="INSERT_URL_HERE?a="+encodeURIComponent(b)+"&d="+encodeURIComponent(JSON.stringify(c));var d=document.getElementsByTagName("script")[0];d.parentNode.insertBefore(a,d)}}}();</script>');
                    // taken from dev/log-client-pageview-event.js
                    $htmlToInsert .= '<script>(function(){var a=function(){var b={};b.url=window.location.toString();var a="";try{var c=(new URL(document.referrer)).host;a=c!==window.location?c:document.referrer}catch(d){}b.referrer=a;vsjs.log("pageview",b)};"loading"===document.readyState?document.addEventListener("DOMContentLoaded",a):a()})();</script>';
                    $domDocument = new HTML5DOMDocument();
                    $domDocument->loadHTML($response->content, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
                    $domDocument->insertHTML($htmlToInsert);
                    $response->content = $domDocument->saveHTML();
                }
            });

        };
    });
