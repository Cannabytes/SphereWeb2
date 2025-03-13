<?php
/** UPDATE **/
namespace Ofey\Logan22\model\config;

use AllowDynamicProperties;
use Ofey\Logan22\model\db\sql;

class template
{

    private bool $isLoadedTemplateInfo = false;

    private string $name = "";

    private float $version = 0.0;

    private int $date = 0;

    private string $author = "";

    private string $contact = "";

    private string $description = "";

    private string $img = "";

    private string $template = "KnightDesert";

    public function __construct($setting)
    {
            $this->template = $setting['template'] ?? $this->template;
    }

    private function loadJSONTemplate(): void
    {
        if ( ! $this->isLoadedTemplateInfo) {
            $template   = $this->template;
            $readmeJson = "template/{$template}/readme.json";
            $img        = "/src/template/sphere/assets/images/none.png";
            $this->name        = $template;
            if (file_exists($readmeJson)) {
                $jsonContents      = file_get_contents($readmeJson);
                $demo              = json_decode($jsonContents, true);
                $this->version     = $demo['version'] ?? 0.0;
                $this->date        = $demo['date'] ?? 0;
                $this->author      = $demo['author'] ?? "";
                $this->contact     = $demo['contact'] ?? "";
                $this->description = $demo['description'] ?? "";
                $img               = "/template/{$template}/{$demo['screen']}";
            }
            $this->img = $img;
        }
    }

    /**
     * @return string
     */
    public function getAuthor(): mixed
    {
        $this->loadJSONTemplate();

        return $this->author;
    }

    /**
     * @return string
     */
    public function getContact(): mixed
    {
        $this->loadJSONTemplate();

        return $this->contact;
    }

    /**
     * @return int
     */
    public function getDate(): mixed
    {
        $this->loadJSONTemplate();

        return $this->date;
    }

    /**
     * @return string
     */
    public function getDescription(): mixed
    {
        $this->loadJSONTemplate();

        return $this->description;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        $this->loadJSONTemplate();
        return $this->name;
    }

    public function setName($name): void
    {
        $this->template = $name;
    }

    /**
     * @return float|mixed
     */
    public function getVersion(): mixed
    {
        $this->loadJSONTemplate();

        return $this->version;
    }

    public function getImg(): string
    {
        $this->loadJSONTemplate();

        return $this->img;
    }

}