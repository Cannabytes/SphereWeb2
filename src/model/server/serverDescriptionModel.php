<?php

namespace Ofey\Logan22\model\server;

use DateTime;

class serverDescriptionModel
{
    private int $server_id = 0;
    private string $lang = 'en';
    private int $pageId = 0;
    private int $default = 0;
    private DateTime $dateCreate;
    private DateTime $dateUpdate;

    function __construct($desc)
    {
        $this->server_id = $desc['server_id'];
        $this->lang = $desc['lang'];
        $this->pageId = $desc['page_id'];
        $this->default = $desc['default'];
        $this->dateCreate = $desc['date_create'];
        $this->dateUpdate = $desc['date_update'];
    }

    /**
     * @return int
     */
    public function getServerId(): int
    {
        return $this->server_id;
    }

    /**
     * @param int $server_id
     * @return serverDescriptionModel
     */
    public function setServerId(int $server_id): serverDescriptionModel
    {
        $this->server_id = $server_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * @param string $lang
     * @return serverDescriptionModel
     */
    public function setLang(string $lang): serverDescriptionModel
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageId(): int
    {
        return $this->pageId;
    }

    /**
     * @param int $pageId
     * @return serverDescriptionModel
     */
    public function setPageId(int $pageId): serverDescriptionModel
    {
        $this->pageId = $pageId;
        return $this;
    }

    /**
     * @return int
     */
    public function getDefault(): int
    {
        return $this->default;
    }

    /**
     * @param int $default
     * @return serverDescriptionModel
     */
    public function setDefault(int $default): serverDescriptionModel
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateCreate(): DateTime
    {
        return $this->dateCreate;
    }

    /**
     * @param DateTime $dateCreate
     * @return serverDescriptionModel
     */
    public function setDateCreate(DateTime $dateCreate): serverDescriptionModel
    {
        $this->dateCreate = $dateCreate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateUpdate(): DateTime
    {
        return $this->dateUpdate;
    }

    /**
     * @param DateTime $dateUpdate
     * @return serverDescriptionModel
     */
    public function setDateUpdate(DateTime $dateUpdate): serverDescriptionModel
    {
        $this->dateUpdate = $dateUpdate;
        return $this;
    }


}