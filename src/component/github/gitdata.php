<?php

namespace Ofey\Logan22\component\github;

use Ofey\Logan22\component\time\time;

class gitdata
{

    private string $sha;

    private string $url;

    private string $author;

    private string $message;

    private string $date;

    public function __construct($commit_data = null)
    {
        if($commit_data === null) {
            return;
        }
        $this->sha      = $commit_data['sha'];
        $this->url      = $commit_data['commit']['url'];
        $this->author   = $commit_data['commit']['author']['name'] ?? "";
        $this->message  = $commit_data['commit']['message'] ?? "";
        $this->date     = time::iso8601ToMysql($commit_data['commit']['author']['date']);
    }

    public function getDataCommit($sha, $url, $author, $message, $date): void
    {
        $this->sha      = $sha;
        $this->url      = $url;
        $this->author   = $author;
        $this->message  = $message;
        $this->date     = time::iso8601ToMysql($date);
    }

    /**
     * @return string
     */
    public function getSHA(): string
    {
        return $this->sha;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

}