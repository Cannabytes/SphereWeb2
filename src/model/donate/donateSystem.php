<?php

namespace Ofey\Logan22\model\donate;

class donateSystem
{

    private bool $enable = false;
    private array $inputs = [];

    function __construct($enable, $inputs)
    {
        $this->enable = filter_var($enable, FILTER_VALIDATE_BOOLEAN);
        foreach ($inputs AS $name => $value){
            $this->inputs[$name] = $value;
        }
    }

    /**
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->enable;
    }

    /**
     * @param null $method
     * @return array|string
     */
    public function getInputs($method = null): array|string
    {
        if($method===null){
            return $this->inputs;
        }
        $methodNameParts = explode('::', $method);
        $methodName = end($methodNameParts);
        return $this->inputs[$methodName];
    }



}