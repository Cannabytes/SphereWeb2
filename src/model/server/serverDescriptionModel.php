<?php

namespace Ofey\Logan22\model\server;

use DateTime;
use Ofey\Logan22\model\page\page;

class serverDescriptionModel
{

    private string $lang = 'en';

    private int $pageId = 0;

    private DateTime $dateCreate;

    private DateTime $dateUpdate;

    private string $poster;

    private string $link;

    public function __construct(int $pageId)
    {
        $page             = page::getMinInfo($pageId);
        $this->lang       = $page['lang'];
        $this->pageId     = $page['id'];
        $this->poster     = $page['poster'];
        $this->link       = $page['link'];
        $this->dateCreate = new DateTime($page['date_create']);
        $this->dateUpdate = new DateTime($page['date_update']);
    }

    /**
     * @return string
     */
    public function getPoster(): string
    {
        return $this->poster;
    }

    /**
     * @return string
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * @param   string  $lang
     *
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
     * @param   int  $pageId
     *
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
     * @param   int  $default
     *
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
     * @param   DateTime  $dateCreate
     *
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
     * @param   DateTime  $dateUpdate
     *
     * @return serverDescriptionModel
     */
    public function setDateUpdate(DateTime $dateUpdate): serverDescriptionModel
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

}