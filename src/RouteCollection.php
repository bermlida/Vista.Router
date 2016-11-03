<?php

namespace Vista\Router;

use ArrayIterator;
use Vista\Router\Interfaces\RouteInterface;
use Vista\Router\Interfaces\RouteCollectionInterface;
use Vista\Router\Traits\RouteCollectionTrait;

class RouteCollection implements RouteCollectionInterface
{
    use RouteCollectionTrait;
    
    protected $routes = [];

    public function offsetSet($offset, $value)
    {
/*
        if (is_string($offset) && $offset !== '') {
            $implements = class_implements($value);
            if (in_array(RouteInterface::class, $implements)) {
                $value->name($offset);
            }
        }
*/
        $this->setRoute($value);
    }

    public function offsetExists($offset)
    {
        return !is_null($this->getRoute($offset));
    }

    public function offsetUnset($offset)
    {
        return $this->removeRoute($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getRoute($offset);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->routes);
    }

    public function count()
    {
        return count($this->routes);
    }
}