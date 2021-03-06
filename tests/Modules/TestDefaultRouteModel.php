<?php

namespace Vista\Router\Tests\Modules;

use Vista\Router\Interfaces\RouteModelInterface;

/**
 * @codeCoverageIgnore
 */
class TestDefaultRouteModel implements RouteModelInterface
{
    private $item_name;
    private $item_property;
    
    public function __construct(string $item_name, string $item_property)
    {
        $this->item_name = $item_name;
        $this->item_property = $item_property;
    }
    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        return null;
    }
}