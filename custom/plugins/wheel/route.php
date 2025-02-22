<?php

use Ofey\Logan22\component\plugins\wheel;

$routes = [
  [
    "method"  => "POST",
    "pattern" => "/admin/balance/pay/roulette",
    "file"    => "wheel.php",
    "call"    => function () {
        (new wheel\wheel())->payRoulette();
    },
  ],

  [
    "method"  => "GET",
    "pattern" => "/fun/wheel/create",
    "file"    => "wheel.php",
    "call"    => function () {
        (new wheel\wheel())->create();
    },
  ],

  [
    "method"  => "POST",
    "pattern" => "/fun/wheel/saveWheel",
    "file"    => "wheel.php",
    "call"    => function () {
        (new wheel\wheel())->saveWheel();
    },
  ],

  [
    "method"  => "GET",
    "pattern" => "/fun/wheel/edit/items/{id}",
    "file"    => "wheel.php",
    "call"    => function ($id) {
        (new wheel\wheel())->edit($id);
    },
  ],

  [
    "method"  => "POST",
    "pattern" => "/fun/wheel/edit/name",
    "file"    => "wheel.php",
    "call"    => function () {
        (new wheel\wheel())->editName();
    },
  ],

  [
    "method"  => "POST",
    "pattern" => "/fun/wheel/callback",
    "file"    => "wheel.php",
    "call"    => function () {
        (new wheel\wheel())->callback();
    },
  ],

  [
    "method"  => "GET",
    "pattern" => "/fun/wheel/admin",
    "file"    => "wheel.php",
    "call"    => function () {
        (new wheel\wheel())->admin();
    },
  ],

  [
    "method"  => "POST",
    "pattern" => "/fun/wheel/remove",
    "file"    => "wheel.php",
    "call"    => function () {
        (new wheel\wheel())->remove();
    },
  ],

  [
    "method"  => "GET",
    "pattern" => "/fun/wheel/{name}",
    "file"    => "wheel.php",
    "call"    => function ($name) {
        (new wheel\wheel())->show($name);
    },
  ],

];
