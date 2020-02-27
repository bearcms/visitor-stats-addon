<?php

/*
 * Visitor stats addon for Bear CMS
 * https://github.com/bearcms/visitor-stats-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS;

use BearCMS\VisitorStats\BeforeApplyEventDetails;
use BearFramework\App;
use IvoPetkov\HTML5DOMDocument;

/**
 *
 */
class VisitorStats
{
    use \BearFramework\EventsTrait;

    /**
     *
     *
     * @param App\Response $response
     * @param array $options Available values: trackPageview
     * @return void
     */
    public function apply(App\Response $response, array $options = []): void
    {
        if ($this->hasEventListeners('beforeApply')) {
            $eventDetails = new BeforeApplyEventDetails($response);
            $this->dispatchEvent('beforeApply', $eventDetails);
            if ($eventDetails->preventDefault) {
                return;
            }
        }
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
            $htmlToInsert .= '<script>(function(){var a=function(){var b={};b.url=window.location.toString();var a="";try{var c=""!==document.referrer?(new URL(document.referrer)).host:"";a=c!==window.location?c:document.referrer}catch(d){}b.referrer=a;vsjs.log("pageview",b)};"loading"===document.readyState?document.addEventListener("DOMContentLoaded",a):a()})();</script>';
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
        $dataToWrite[] = 2; // data format version
        $dataToWrite[] = time();
        $dataToWrite[] = $action;
        $dataToWrite[] = $data;

        $app->data->append('bearcms-visitor-stats/' . date('Y-m-d') . '.jsonlist', json_encode($dataToWrite) . "\n");
    }


    /**
     * Undocumented function
     *
     * @param array $list
     * @return array|null
     */
    public function getStats(array $list): ?array
    {
        $app = App::get();

        $calculateIntervalDateCodes = function (int $startDate, int $endDate): array {
            if ($startDate > $endDate) {
                throw new \Exception();
            }
            $result = [];
            for ($timestamp = $startDate; $timestamp <= $endDate; $timestamp += 86400) {
                $result[date('Y-m-d', $timestamp)] = true;
            }
            $result[date('Y-m-d', $endDate)] = true;
            return array_keys($result);
        };

        $getHost = function (string $url): ?string {
            if (strlen($url) === 0) {
                return null;
            }
            if (strpos($url, '://') === false) {
                $url = 'http://' . $url;
            }
            return parse_url($this->fixEncoding($url), PHP_URL_HOST);
        };

        $getPath = function (string $url): ?string {
            if (strlen($url) === 0 || strpos($url, '://') === false) {
                return null;
            }
            return parse_url($this->fixEncoding($url), PHP_URL_PATH);
        };

        $getSource = function (array $itemData) use ($getHost): ?string {
            if (isset($itemData['source']) && strlen($itemData['source']) > 0) {
                return $itemData['source'];
            }
            $urlHost = $getHost($itemData['url']);
            $referrerHost = $getHost($itemData['referrer']);
            if ($urlHost === $referrerHost) {
                return '-'; // internal link
            }
            if ($referrerHost === null) {
                return null; // no referrer
            }
            if (mb_substr($referrerHost, 0, 4) === 'www.') {
                $referrerHost = mb_substr($referrerHost, 4);
            }

            $textSearchEngine = __('bearcms.visitor-stats-addon.search engine');
            $groups = [
                'Google (' . $textSearchEngine . ')' => ['google.com', 'google.ad', 'google.ae', 'google.com.af', 'google.com.ag', 'google.com.ai', 'google.al', 'google.am', 'google.co.ao', 'google.com.ar', 'google.as', 'google.at', 'google.com.au', 'google.az', 'google.ba', 'google.com.bd', 'google.be', 'google.bf', 'google.bg', 'google.com.bh', 'google.bi', 'google.bj', 'google.com.bn', 'google.com.bo', 'google.com.br', 'google.bs', 'google.bt', 'google.co.bw', 'google.by', 'google.com.bz', 'google.ca', 'google.cd', 'google.cf', 'google.cg', 'google.ch', 'google.ci', 'google.co.ck', 'google.cl', 'google.cm', 'google.cn', 'google.com.co', 'google.co.cr', 'google.com.cu', 'google.cv', 'google.com.cy', 'google.cz', 'google.de', 'google.dj', 'google.dk', 'google.dm', 'google.com.do', 'google.dz', 'google.com.ec', 'google.ee', 'google.com.eg', 'google.es', 'google.com.et', 'google.fi', 'google.com.fj', 'google.fm', 'google.fr', 'google.ga', 'google.ge', 'google.gg', 'google.com.gh', 'google.com.gi', 'google.gl', 'google.gm', 'google.gr', 'google.com.gt', 'google.gy', 'google.com.hk', 'google.hn', 'google.hr', 'google.ht', 'google.hu', 'google.co.id', 'google.ie', 'google.co.il', 'google.im', 'google.co.in', 'google.iq', 'google.is', 'google.it', 'google.je', 'google.com.jm', 'google.jo', 'google.co.jp', 'google.co.ke', 'google.com.kh', 'google.ki', 'google.kg', 'google.co.kr', 'google.com.kw', 'google.kz', 'google.la', 'google.com.lb', 'google.li', 'google.lk', 'google.co.ls', 'google.lt', 'google.lu', 'google.lv', 'google.com.ly', 'google.co.ma', 'google.md', 'google.me', 'google.mg', 'google.mk', 'google.ml', 'google.com.mm', 'google.mn', 'google.ms', 'google.com.mt', 'google.mu', 'google.mv', 'google.mw', 'google.com.mx', 'google.com.my', 'google.co.mz', 'google.com.na', 'google.com.ng', 'google.com.ni', 'google.ne', 'google.nl', 'google.no', 'google.com.np', 'google.nr', 'google.nu', 'google.co.nz', 'google.com.om', 'google.com.pa', 'google.com.pe', 'google.com.pg', 'google.com.ph', 'google.com.pk', 'google.pl', 'google.pn', 'google.com.pr', 'google.ps', 'google.pt', 'google.com.py', 'google.com.qa', 'google.ro', 'google.ru', 'google.rw', 'google.com.sa', 'google.com.sb', 'google.sc', 'google.se', 'google.com.sg', 'google.sh', 'google.si', 'google.sk', 'google.com.sl', 'google.sn', 'google.so', 'google.sm', 'google.sr', 'google.st', 'google.com.sv', 'google.td', 'google.tg', 'google.co.th', 'google.com.tj', 'google.tl', 'google.tm', 'google.tn', 'google.to', 'google.com.tr', 'google.tt', 'google.com.tw', 'google.co.tz', 'google.com.ua', 'google.co.ug', 'google.co.uk', 'google.com.uy', 'google.co.uz', 'google.com.vc', 'google.co.ve', 'google.vg', 'google.co.vi', 'google.com.vn', 'google.vu', 'google.ws', 'google.rs', 'google.co.za', 'google.co.zm', 'google.co.zw', 'google.cat'],
                'Facebook' => ['*.facebook.com', 'l.messenger.com'],
                'Instagram' => ['*.instagram.com'],
                'Baidu' => ['*.baidu.com'],
                'Bing (' . $textSearchEngine . ')' => ['*.bing.com'],
                'Yandex' => ['*.yandex.ru'],
                'abv.bg' => ['*.abv.bg'],
                'LinkedIn' => ['linkedin.com'],
                'Skype' => ['web.skype.com'],
                'Gmail' => ['mail.google.com'],
                'Yahoo (' . $textSearchEngine . ')' => ['search.yahoo.com'],
                'PayPal' => ['paypal.com'],
                'DuckDuckGo (' . $textSearchEngine . ')' => ['duckduckgo.com']
            ];
            foreach ($groups as $name => $items) {
                foreach ($items as $item) {
                    if ($item === $referrerHost) {
                        return $name;
                    }
                    if (substr($item, 0, 2) === '*.' && substr($referrerHost, - (strlen($item) - 2)) === substr($item, 2)) {
                        return $name;
                    }
                }
            }
            return $referrerHost;
        };

        $getAvailableDateCodes = function () use ($app) {
            $list = $app->data->getList()
                ->filterBy('key', 'bearcms-visitor-stats/', 'startWith')
                ->sliceProperties(['key']);
            $result = [];
            foreach ($list as $item) {
                $matches = null;
                preg_match('/bearcms-visitor-stats\/([0-9]{4}\-[0-9]{2}\-[0-9]{2})\.jsonlist/', $item->key, $matches);
                if (isset($matches[1])) {
                    $result[] = $matches[1];
                }
            }
            return $result;
        };

        $sortDataByTime = function (&$data, $order = 'desc') {
            usort($data, function ($a, $b) use ($order) {
                if ($order === 'desc') {
                    return $b[0] - $a[0];
                }
                return $a[0] - $b[0];
            });
        };

        $get = function (string $type, array $options) use ($getHost, $getPath, $getSource, $getAvailableDateCodes, $sortDataByTime, $calculateIntervalDateCodes) {
            $result = [];

            $isPageview = function ($item) {
                return $item[1] === 'pageview' && isset($item[2]['url'], $item[2]['referrer']);
            };

            $getStringOption = function (string $name) use ($options): ?string {
                return isset($options[$name]) ? (string) $options[$name] : null;
            };

            $getIntOption = function (string $name) use ($options): ?int {
                return isset($options[$name]) ? (int) $options[$name] : null;
            };

            $getFromOption = function () use ($options) {
                return isset($options['from']) ? (int) $options['from'] : time() - 7 * 86400;
            };

            $getToOption = function () use ($options) {
                return isset($options['to']) ? (int) $options['to'] : time();
            };

            $getSortByCountOption = function () use ($options) {
                return isset($options['sortByCount']) ? (array_search($options['sortByCount'], ['asc', 'desc']) !== false ? $options['sortByCount'] : null) : null;
            };

            if ($type === 'lastPageviews' || $type === 'lastPageviewsPerPath' || $type === 'lastPageviewsPerSource') {
                $limit = $getIntOption('limit');
                $dateCodes = $getAvailableDateCodes();
                if ($type === 'lastPageviewsPerPath') {
                    $pathOption = $this->fixEncoding($getStringOption('path'));
                } elseif ($type === 'lastPageviewsPerSource') {
                    $sourceOption = $getStringOption('source');
                }
                rsort($dateCodes);
                foreach ($dateCodes as $dateCode) {
                    $data = $this->getData([$dateCode]);
                    $sortDataByTime($data);
                    $break = false;
                    foreach ($data as $item) {
                        if (!$isPageview($item)) {
                            continue;
                        }
                        $itemData = $item[2];
                        $urlHost = $getHost($itemData['url']);
                        $path = $getPath($itemData['url']);
                        $source = $getSource($itemData);
                        if ($type === 'lastPageviewsPerPath') {
                            if ($path !== null && $path !== $pathOption) {
                                continue;
                            }
                        } elseif ($type === 'lastPageviewsPerSource') {
                            if ($source === null || $source !== $sourceOption) {
                                continue;
                            }
                        }
                        $result[] = [
                            'datetime' => $item[0],
                            'path' => $path,
                            'source' => $source
                        ];
                        if ($limit !== null && sizeof($result) === $limit) {
                            $break = true;
                            break;
                        }
                    }
                    if ($break) {
                        break;
                    }
                }
            } elseif ($type === 'pageviewsPerDayCount' || $type === 'sessionsPerDayCount' || $type === 'pageviewsPerDayPerPageCount' || $type === 'pageviewsPerDayPerSourceCount') {
                $from = $getFromOption();
                $to = $getToOption();
                $dateCodes = $calculateIntervalDateCodes($from, $to);
                if ($type === 'pageviewsPerDayPerPageCount') {
                    $pathOption = $this->fixEncoding($getStringOption('path'));
                } elseif ($type === 'pageviewsPerDayPerSourceCount') {
                    $sourceOption = $getStringOption('source');
                }
                $data = $this->getData($dateCodes);
                $temp = [];
                foreach ($data as $item) {
                    $itemData = $item[2];
                    if (!$isPageview($item)) {
                        continue;
                    }
                    if ($item[0] < $from || $item[0] > $to) {
                        continue;
                    }
                    if ($type === 'sessionsPerDayCount') {
                        $urlHost = $getHost($itemData['url']);
                        $referrerHost = $getHost($itemData['referrer']);
                        if ($referrerHost === null || $urlHost === $referrerHost) {
                            continue;
                        }
                    } elseif ($type === 'pageviewsPerDayPerPageCount') {
                        $path = $getPath($itemData['url']);
                        if ($path !== null && $path !== $pathOption) {
                            continue;
                        }
                    } elseif ($type === 'pageviewsPerDayPerSourceCount') {
                        $source = $getSource($itemData);
                        if ($source === null || $source !== $sourceOption) {
                            continue;
                        }
                    }
                    $dateCode = date('Y-m-d', $item[0]);
                    if (!isset($temp[$dateCode])) {
                        $temp[$dateCode] = 0;
                    }
                    $temp[$dateCode]++;
                }
                if (isset($options['addEmptyDays']) && $options['addEmptyDays'] === true) {
                    foreach ($dateCodes as $dateCode) {
                        if (!isset($temp[$dateCode])) {
                            $temp[$dateCode] = 0;
                        }
                    }
                }
                if (isset($options['sortByDate'])) {
                    if ($options['sortByDate'] === 'desc') {
                        krsort($temp);
                    } else {
                        ksort($temp);
                    }
                }
                foreach ($temp as $dateCode => $count) {
                    $result[] = [
                        'date' => $dateCode,
                        'count' => $count
                    ];
                }
                unset($temp);
            } elseif ($type === 'sourcesVisitsCount') {
                $from = $getFromOption();
                $to = $getToOption();
                $data = $this->getData($calculateIntervalDateCodes($from, $to));
                $temp = [];
                foreach ($data as $item) {
                    $itemData = $item[2];
                    if (!$isPageview($item)) {
                        continue;
                    }
                    if ($item[0] < $from || $item[0] > $to) {
                        continue;
                    }
                    $source = $getSource($itemData);
                    if ($source !== null && $source !== '-') {
                        if (!isset($temp[$source])) {
                            $temp[$source] = 0;
                        }
                        $temp[$source]++;
                    }
                }
                $sortByCount = $getSortByCountOption();
                if ($sortByCount !== null) {
                    if ($sortByCount === 'desc') {
                        arsort($temp);
                    } else {
                        asort($temp);
                    }
                }
                $limit = $getIntOption('limit');
                if ($limit !== null && !empty($temp)) {
                    $temp = array_chunk($temp, $limit, true)[0];
                }
                foreach ($temp as $source => $count) {
                    $result[] = [
                        'source' => $source,
                        'count' => $count
                    ];
                }
                unset($temp);
            } elseif ($type === 'landingPagesCount' || $type === 'pageviewsPerPageCount') {
                $from = $getFromOption();
                $to = $getToOption();
                $data = $this->getData($calculateIntervalDateCodes($from, $to));
                $temp = [];
                foreach ($data as $item) {
                    $itemData = $item[2];
                    if (!$isPageview($item)) {
                        continue;
                    }
                    if ($item[0] < $from || $item[0] > $to) {
                        continue;
                    }
                    if ($type === 'landingPagesCount') {
                        $urlHost = $getHost($itemData['url']);
                        $referrerHost = $getHost($itemData['referrer']);
                        if ($urlHost !== null && $referrerHost !== null && $urlHost !== $referrerHost) {
                            $path = $getPath($itemData['url']);
                            if (!isset($temp[$path])) {
                                $temp[$path] = 0;
                            }
                            $temp[$path]++;
                        }
                    } elseif ($type === 'pageviewsPerPageCount') {
                        $path = $getPath($itemData['url']);
                        if (!isset($temp[$path])) {
                            $temp[$path] = 0;
                        }
                        $temp[$path]++;
                    }
                }
                $sortByCount = $getSortByCountOption();
                if ($sortByCount !== null) {
                    if ($sortByCount === 'desc') {
                        arsort($temp);
                    } else {
                        asort($temp);
                    }
                }
                $limit = $getIntOption('limit');
                if ($limit !== null && !empty($temp)) {
                    $temp = array_chunk($temp, $limit, true)[0];
                }
                foreach ($temp as $path => $count) {
                    $result[] = [
                        'path' => $path,
                        'count' => $count
                    ];
                }
                unset($temp);
            }

            return $result;
        };

        $result = [];
        foreach ($list as $item) {
            $type = $item['type'];
            unset($item['type']);
            $result[$type] = $get($type, $item);
        }
        return $result;
    }

    /**
     * Undocumented function
     *
     * @param array $dateCodes
     * @return array
     */
    private function getData(array $dateCodes): array
    {
        $app = App::get();
        $result = [];
        foreach ($dateCodes as $dateCode) {
            $list = $app->data->getValue('bearcms-visitor-stats/' . $dateCode . '.jsonlist');
            if ($list !== null) {
                $list = explode("\n", $list);
                $list = array_reverse($list);
                foreach ($list as $item) {
                    $item = trim($item);
                    if (isset($item[0])) {
                        $item = json_decode($item, true);
                        if ($item[0] === 2) {
                            $result[] = [$item[1], $item[2], $item[3]];
                        } elseif ($item[0] === 1) {
                            $datetime = new \DateTime($dateCode . ' ' . $item[1]);
                            $result[] = [$datetime->getTimestamp(), $item[2], $item[3]];
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Fixes URL encoding (UTF8 to ASCII)
     *
     * @param string $url
     * @return string
     */
    private function fixEncoding(string $url): string
    {
        $length = mb_strlen($url);
        $chars = [];
        for ($i = 0; $i < $length; $i += 1) {
            $char = mb_substr($url, $i, 1);
            if (!mb_check_encoding($char, 'ASCII')) {
                $char = urlencode($char);
            }
            $chars[] = $char;
        }
        return implode('', $chars);
    }
}
