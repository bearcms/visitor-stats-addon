<?php

return function($ip) {
    if (empty($ip)) {
        return null;
    }
    $parts = explode('.', $ip);
    if (sizeof($parts) !== 4) {
        return null;
    }
    $v = (int) $parts[3] + ((int) $parts[2] * 256) + ((int) $parts[1] * 256 * 256) + ((int) $parts[0] * 256 * 256 * 256);
    if ($v >= 0 && $v < 522766335) {
        $index = 1;
    } elseif ($v >= 522766336 && $v < 755778559) {
        $index = 2;
    } elseif ($v >= 755778560 && $v < 770306047) {
        $index = 3;
    } elseif ($v >= 770306048 && $v < 1066270719) {
        $index = 4;
    } elseif ($v >= 1066270720 && $v < 1168867327) {
        $index = 5;
    } elseif ($v >= 1168867328 && $v < 1358583551) {
        $index = 6;
    } elseif ($v >= 1358583552 && $v < 1495280127) {
        $index = 7;
    } elseif ($v >= 1495280128 && $v < 1540658943) {
        $index = 8;
    } elseif ($v >= 1540658944 && $v < 1567743487) {
        $index = 9;
    } elseif ($v >= 1567743488 && $v < 1729369343) {
        $index = 10;
    } elseif ($v >= 1729369344 && $v < 1733869567) {
        $index = 11;
    } elseif ($v >= 1733869568 && $v < 1740997631) {
        $index = 12;
    } elseif ($v >= 1740997632 && $v < 1760821247) {
        $index = 13;
    } elseif ($v >= 1760821248 && $v < 2083020799) {
        $index = 14;
    } elseif ($v >= 2083020800 && $v < 2333923327) {
        $index = 15;
    } elseif ($v >= 2333923328 && $v < 2555834367) {
        $index = 16;
    } elseif ($v >= 2555834368 && $v < 2745106431) {
        $index = 17;
    } elseif ($v >= 2745106432 && $v < 2916024319) {
        $index = 18;
    } elseif ($v >= 2916024320 && $v < 3033923583) {
        $index = 19;
    } elseif ($v >= 3033923584 && $v < 3107995647) {
        $index = 20;
    } elseif ($v >= 3107995648 && $v < 3113081855) {
        $index = 21;
    } elseif ($v >= 3113081856 && $v < 3118155775) {
        $index = 22;
    } elseif ($v >= 3118155776 && $v < 3174215679) {
        $index = 23;
    } elseif ($v >= 3174215680 && $v < 3225420799) {
        $index = 24;
    } elseif ($v >= 3225420800 && $v < 3229221119) {
        $index = 25;
    } elseif ($v >= 3229221120 && $v < 3233680639) {
        $index = 26;
    } elseif ($v >= 3233680640 && $v < 3241078271) {
        $index = 27;
    } elseif ($v >= 3241078272 && $v < 3255097343) {
        $index = 28;
    } elseif ($v >= 3255097344 && $v < 3271884799) {
        $index = 29;
    } elseif ($v >= 3271884800 && $v < 3292274943) {
        $index = 30;
    } elseif ($v >= 3292275712 && $v < 3332426239) {
        $index = 31;
    } elseif ($v >= 3332426240 && $v < 3341195007) {
        $index = 32;
    } elseif ($v >= 3341195008 && $v < 3354980607) {
        $index = 33;
    } elseif ($v >= 3354980608 && $v < 3391398655) {
        $index = 34;
    } elseif ($v >= 3391398656 && $v < 3407026175) {
        $index = 35;
    } elseif ($v >= 3407026176 && $v < 3421650431) {
        $index = 36;
    } elseif ($v >= 3421650432 && $v < 3438165247) {
        $index = 37;
    } elseif ($v >= 3438165248 && $v < 3482591231) {
        $index = 38;
    } elseif ($v >= 3482591232 && $v < 3557326847) {
        $index = 39;
    } elseif ($v >= 3557326848 && $v < 3641904639) {
        $index = 40;
    } elseif ($v >= 3641904640 && $v < 4294967295) {
        $index = 41;
    } else {
        return null;
    }    
    $function = require __DIR__ . '/' . $index . '.php';
    return $function($v);
};
