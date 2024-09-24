<?php

use Ofey\Logan22\component\plugins\set_http_referrer;

$routes = [
  [
    "method"  => "GET",
    "pattern" => "/admin/statistic/http/referral",
    "file"    => "httpReferrerPlugin.php",
    "call"    => function () {
        (new set_http_referrer\httpReferrerPlugin())->show();
    },
  ],

  [
    "method"  => "GET",
    "pattern" => "/admin/statistic/http/referral/(.*)/(.*)/(.*)",
    "file"    => "httpReferrerPlugin.php",
    "call"    => function ($referName, $dateStart, $dateEnd) {
        (new set_http_referrer\httpReferrerPlugin())->get($referName, $dateStart, $dateEnd);
    },
  ],

  [
    "method"  => "GET",
    "pattern" => "/admin/statistic/http/referral/(.*)",
    "file"    => "httpReferrerPlugin.php",
    "call"    => function ($referName) {
        (new set_http_referrer\httpReferrerPlugin())->get($referName);
    },
  ],

  [
    "method"  => "GET",
    "pattern" => "/admin/statistic/http/referral/(.*)",
    "file"    => "httpReferrerPlugin.php",
    "call"    => function ($referName) {
        (new set_http_referrer\httpReferrerPlugin())->get($referName);
    },
  ],

  [
    "method"  => "GET",
    "pattern" => "/l2oops",
    "file"    => "httpReferrerPlugin.php",
    "call"    => function () {
        $_SESSION['HTTP_REFERER'] = "l2oops";
        \Ofey\Logan22\component\redirect::location("/");
    },
  ],
  [
    "method"  => "GET",
    "pattern" => "/l2pick",
    "file"    => "httpReferrerPlugin.php",
    "call"    => function () {
        $_SESSION['HTTP_REFERER'] = "l2pick";
        \Ofey\Logan22\component\redirect::location("/");
    },
  ],
  [
    "method"  => "GET",
    "pattern" => "/l2op",
    "file"    => "httpReferrerPlugin.php",
    "call"    => function () {
        $_SESSION['HTTP_REFERER'] = "l2op";
        \Ofey\Logan22\component\redirect::location("/");
    },
  ],
  [
    "method"  => "GET",
    "pattern" => "/l2hub",
    "file"    => "httpReferrerPlugin.php",
    "call"    => function () {
        $_SESSION['HTTP_REFERER'] = "l2hub";
        \Ofey\Logan22\component\redirect::location("/");
    },
  ],



];
