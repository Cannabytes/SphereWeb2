<?php

namespace Ofey\Logan22\component\stream;

class streamInfo {
    public $channel_id;
    public $is_live;
    public $title;
    public $username;
    public $avatar_url;
    public $channel_url;
    public $video_url;
    public $video_id;

    public function __construct() {
        $this->is_live = false;
    }
}