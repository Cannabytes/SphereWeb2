<?php
/**
 * Created by Logan22
 * Список разных стилей для данного шаблона
 */

$stylesConfig = [
    [
        "name" => "styles.css",
        "description" => "Стандартный стиль, который используется по умолчанию",
        "video" => false,
    ],
    [
        "name" => "styles_dark_hell.css",
        "description" => "Темный стиль Dark Hell, с красными и синими оттенками, с видео на фоне",
        "video" => true,
        "video_to_body" => '
            <video id="site-video-bg" autoplay muted loop playsinline poster="" preload="auto">
                <source src="https://cdn.jsdelivr.net/gh/Cannabytes/otherobject@main/bg_hell.webm" type="video/webm">
            </video>

            <video id="site-video-fire" autoplay muted loop playsinline poster="" preload="auto" style="mix-blend-mode: screen;">
                <source src="https://cdn.jsdelivr.net/gh/Cannabytes/otherobject@main/fire_hell.webm" type="video/webm">
            </video>
        ',
    ],
];