<?php

/*
 * Visitor stats addon for Bear CMS
 * https://github.com/bearcms/visitor-stats-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS;

use BearFramework\App;
use IvoPetkov\HTML5DOMDocument;

/**
 *
 */
class VisitorStats
{

    /**
     *
     *
     * @param App\Response $response
     * @param array $options Available values: trackPageview
     * @return void
     */
    public function apply(App\Response $response, array $options = []): void
    {
        $trackPageview = isset($options['trackPageview']) ? (int) $options['trackPageview'] > 0 : false;
        $app = App::get();
        if ($app->bearCMS->currentUser->exists()) {
            return;
        }
        $htmlToInsert = '';
        // taken from dev/library.js
        $htmlToInsert .= str_replace('INSERT_URL_HERE', $app->urls->get('/-vs.js'), '<script>var vsjs="undefined"!==typeof vsjs?vsjs:function(){return{log:function(b,c){"undefined"===typeof b&&(b="");"undefined"===typeof c&&(c={});var a=document.createElement("script");a.type="text/javascript";a.async=!0;a.src="INSERT_URL_HERE?a="+encodeURIComponent(b)+"&d="+encodeURIComponent(JSON.stringify(c));var d=document.getElementsByTagName("script")[0];d.parentNode.insertBefore(a,d)}}}();</script>');
        if ($trackPageview) {
            // taken from dev/log-client-pageview-event.js
            $htmlToInsert .= '<script>(function(){var a=function(){var b={};b.url=window.location.toString();var a="";try{var c=(new URL(document.referrer)).host;a=c!==window.location?c:document.referrer}catch(d){}b.referrer=a;vsjs.log("pageview",b)};"loading"===document.readyState?document.addEventListener("DOMContentLoaded",a):a()})();</script>';
        }
        $domDocument = new HTML5DOMDocument();
        $domDocument->loadHTML($response->content, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
        $domDocument->insertHTML($htmlToInsert);
        $response->content = $domDocument->saveHTML();
    }
    /**
     *
     * @param string $action
     * @param array $data
     */
    public function log(string $action, array $data = [])
    {
        $app = App::get();

//        $anonymizeIP = function($ip) {
        //            $v6 = strpos($ip, ':') !== false;
        //            $parts = explode($v6 ? ':' : '.', $ip);
        //            $partsCount = sizeof($parts);
        //            for ($i = $v6 ? 6 : 3; $i < $partsCount; $i++) {
        //                $parts[$i] = '*';
        //            }
        //            return implode($v6 ? ':' : '.', $parts);
        //        };
        //        $anonymizeIP(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''));
        //        isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''

        $dataToWrite = [];
        $dataToWrite[] = 1; // data format version
        $dataToWrite[] = date('H:i:s');
        $dataToWrite[] = $action;
        $dataToWrite[] = $data;

        $app->data->append('bearcms-visitor-stats/' . date('Y-m-d') . '.jsonlist', json_encode($dataToWrite) . "\n");
    }

    /**
     *
     *
     * @param integer $startDate
     * @param integer $endDate
     * @param array $types
     * @return array
     */
    public function getStats(int $startDate, int $endDate, array $types): ?array
    {
        $invervalCodes = self::getDateIntervalCodes($startDate, $endDate);
        $data = self::getData($startDate, $endDate);

        $get = function (string $type) use ($data, $invervalCodes) {
            $result = [];
            $resultType = null;
            $setDayCountType = function ($dateCode) use (&$result, &$resultType) {
                if (!isset($result[$dateCode])) {
                    $result[$dateCode] = 0;
                }
                if ($resultType === null) {
                    $resultType = 'dayCount';
                }
            };
            $setListCountType = function ($key) use (&$result, &$resultType) {
                if (!isset($result[$key])) {
                    $result[$key] = 0;
                }
                if ($resultType === null) {
                    $resultType = 'listCount';
                }
            };
            $setListType = function () use (&$resultType) {
                if ($resultType === null) {
                    $resultType = 'list';
                }
            };

            $getHost = function (string $url): ?string {
                if (strlen($url) === 0) {
                    return null;
                }
                if (strpos($url, '://') === false) {
                    $url = 'http://' . $url;
                }
                return parse_url($url, PHP_URL_HOST);
            };

            $getPath = function (string $url): ?string {
                if (strlen($url) === 0 || strpos($url, '://') === false) {
                    return null;
                }
                return parse_url($url, PHP_URL_PATH);
            };

            foreach ($data as $item) {
                $dateCode = substr($item[0], 0, 10);
                $action = $item[1];
                $itemData = $item[2];

                if ($type === 'lastPageviews') {
                    if ($action === 'pageview' && isset($itemData['url'], $itemData['referrer'])) {
                        $setListType();
                        $urlHost = $getHost($itemData['url']);
                        $referrerHost = $getHost($itemData['referrer']);
                        $dateTime = new \DateTime($item[0]);
                        $result[] = ['datetime' => $dateTime->getTimestamp(), 'page' => $getPath($itemData['url']), 'source' => ($urlHost !== $referrerHost ? $referrerHost : null)];
                        if (sizeof($result) === 100) { // todo
                            break;
                        }
                    }
                } elseif ($type === 'pageviewsPerDayCount') {
                    if ($action === 'pageview') {
                        $setDayCountType($dateCode);
                        $result[$dateCode]++;
                    }
                } elseif ($type === 'sessionsPerDayCount') {
                    if ($action === 'pageview' && isset($itemData['url'], $itemData['referrer'])) {
                        $setDayCountType($dateCode);
                        $urlHost = $getHost($itemData['url']);
                        $referrerHost = $getHost($itemData['referrer']);
                        if ($urlHost !== null && $urlHost !== $referrerHost) {
                            $result[$dateCode]++;
                        }
                    }
                } elseif ($type === 'sourcesVisitsCount') {
                    if ($action === 'pageview' && isset($itemData['url'], $itemData['referrer'])) {
                        $urlHost = $getHost($itemData['url']);
                        $referrerHost = $getHost($itemData['referrer']);
                        if ($referrerHost !== null && $urlHost !== $referrerHost) {
                            $setListCountType($referrerHost);
                            $result[$referrerHost]++;
                        }
                    }
                } elseif ($type === 'landingPagesCount') {
                    if ($action === 'pageview' && isset($itemData['url'], $itemData['referrer'])) {
                        $urlHost = $getHost($itemData['url']);
                        $referrerHost = $getHost($itemData['referrer']);
                        if ($urlHost !== null && $referrerHost !== null && $urlHost !== $referrerHost) {
                            $path = $getPath($itemData['url']);
                            $setListCountType($path);
                            $result[$path]++;
                        }
                    }
                } elseif ($type === 'pageviewsPerPageCount') {
                    if ($action === 'pageview' && isset($itemData['url'])) {
                        $path = $getPath($itemData['url']);
                        if ($path !== null) {
                            $setListCountType($path);
                            $result[$path]++;
                        }
                    }
                } else {
                    return null;
                }
            }
            if ($resultType === null) {
                return null;
            }
            if ($resultType === 'dayCount') {
                $temp = [];
                foreach ($invervalCodes as $dateCode) {
                    $temp[$dateCode] = isset($result[$dateCode]) ? $result[$dateCode] : 0;
                }
                ksort($temp);
                $result = $temp;
                unset($temp);
            } elseif ($resultType === 'listCount') {
                arsort($result);
            }
            return $result;
        };

        $result = [];
        foreach ($types as $type) {
            $result[$type] = $get($type);
        }
        return $result;
    }

    /**
     * Returns in intervals in REVERSE order
     *
     * @param integer $startDate
     * @param integer $endDate
     * @return array
     */
    private function getDateIntervalCodes(int $startDate, int $endDate): array
    {
        if ($startDate > $endDate) {
            throw new \Exception();
        }
        $result = [];
        for ($timestamp = $startDate; $timestamp <= $endDate; $timestamp += 86400) {
            $result[date('Y-m-d', $timestamp)] = true;
        }
        $result = array_keys($result);
        $result = array_reverse($result);
        return $result;
    }

    /**
     * Returns data in REVERSE order
     *
     * @param integer $startDate
     * @param integer $endDate
     * @return array
     */
    private function getData(int $startDate, int $endDate): array
    {
        $app = App::get();
        $result = [];
        $invervalCodes = self::getDateIntervalCodes($startDate, $endDate);
        foreach ($invervalCodes as $dateCode) {
            $list = $app->data->getValue('bearcms-visitor-stats/' . $dateCode . '.jsonlist');
            if ($list !== null) {
                $list = explode("\n", $list);
                $list = array_reverse($list);
                foreach ($list as $item) {
                    $item = trim($item);
                    if (isset($item[0])) {
                        $item = json_decode($item, true);
                        if ($item[0] === 1) {
                            $result[] = [$dateCode . ' ' . $item[1], $item[2], $item[3]];
                        }
                    }
                }
            }
        }
        return $result;
    }

}
