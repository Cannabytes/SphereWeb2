<?php

namespace Ofey\Logan22\component\stream;

use Ofey\Logan22\component\redirect;

class streamDetect
{
    function checkLiveStatus($channelID): streamInfo
    {
        $url = sprintf("https://www.youtube.com/channel/%s/live", $channelID);
        $info = new streamInfo();
        $info->channel_url = $url;

        $response = file_get_contents($url);
        if ($response === FALSE) {
            return $info;
        }

        $htmlContent = $response;

        if (preg_match('/"isLive":(.*?)/', $htmlContent)) {
            $info->is_live = true;
        }

        // Extract title
        if (preg_match('/\{"videoPrimaryInfoRenderer":\{"title":\{"runs":\[\{"text":"(.*?)"\}/', $htmlContent, $matches)) {
            $info->title = $matches[1];
        }

        // Extract username
        if (preg_match('/\},"title":\{"runs":\[\{"text":"(.*?)","navigationEndpoi/', $htmlContent, $matches)) {
            $info->username = $matches[1];
        }

        // Extract avatar URL
        if (preg_match('/"videoSecondaryInfoRenderer":\{"owner":\{"videoOwnerRenderer":\{"thumbnail":\{"thumbnails":\[\{"url":"(.*?)"/', $htmlContent, $matches)) {
            $info->avatar_url = $matches[1];
        }

        // Extract video URL
        if (preg_match('/"status":"LIKE","target":\{"videoId":"(.*?)"/', $htmlContent, $matches)) {
            $info->video_url = sprintf("https://www.youtube.com/watch?v=%s", $matches[1]);
            $info->video_id = $matches[1];
        }

        return $info;
    }

    function getChannelId($url) {
        $channelID = "";
        $response = file_get_contents($url);
        if ($response === FALSE) {
            return $channelID;
        }

        $htmlContent = $response;

        if (preg_match('/<meta itemprop="identifier" content="(.*?)">/', $htmlContent, $matches)) {
            $channelID = $matches[1];
        }

        return $channelID;
    }

    function YouTubeLink($channel) {
        $channelID = $this->getChannelId($channel);
        if (empty($channelID)) {
            error_log("Error getting channel ID");
            return new streamInfo();
        }
        $info = $this->checkLiveStatus($channelID);
        $info->channel_id = $channelID;
        return $info;
    }

    private streamInfo $streamInfo;

    public function __construct($channel) {
        $this->streamInfo = $this->YouTubeLink($channel);
    }

    public function get(): streamInfo
    {
        return $this->streamInfo;
    }


}