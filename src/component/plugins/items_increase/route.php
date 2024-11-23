<?php
use Ofey\Logan22\component\plugins\items_increase;

$routes = [
       [
            "method"  => "GET",
            "pattern" => "/admin/statistic/item/increase",
            "file"    => "items_increase.php",
            "call"    => function() {
                (new items_increase\items_increase())->show();
            },
       ],


       [
            "method"  => "POST",
            "pattern" => "/admin/statistic/item/increase/save",
            "file"    => "items_increase.php",
            "call"    => function() {
                (new items_increase\items_increase())->save();
            },
       ],


];
