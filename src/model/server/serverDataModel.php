<?php

namespace Ofey\Logan22\model\server;

class serverDataModel
{
    private int $id = 0;
    private string $key = '';
    private string $val = '';
    private int $server_id = 0;

    function __construct($server_data)
    {
        $this->id = $server_data['id'] ?? 0;
        $this->key = $server_data['key'] ?? '';
        $this->val = $this->parseValue($server_data['val'] ?? '');
        $this->server_id = $server_data['server_id'] ?? 0;
    }
    private function parseValue($value)
    {
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $boolValue !== null ? $boolValue : $value;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return serverDataModel
     */
    public function setId(int $id): serverDataModel
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return serverDataModel
     */
    public function setKey(string $key): serverDataModel
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function getVal(): string
    {
        return $this->val;
    }

    /**
     * @param string $val
     * @return serverDataModel
     */
    public function setVal(string $val): serverDataModel
    {
        $this->val = $val;
        return $this;
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
     * @return serverDataModel
     */
    public function setServerId(int $server_id): serverDataModel
    {
        $this->server_id = $server_id;
        return $this;
    }


}