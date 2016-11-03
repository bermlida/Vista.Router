<?php

namespace Vista\Router;

use ReflectionMethod;
use ReflectionFunction;
use Psr\Http\Message\ServerRequestInterface;
use Vista\Router\Interfaces\RouteInterface;
use Vista\Router\Interfaces\RouteModelInterface;
use Vista\Router\Traits\RouteSetterTrait;
use Vista\Router\Traits\RouteGetterTrait;
use Vista\Router\Traits\RouteTrait;

class Route implements RouteInterface
{
    use RouteSetterTrait, RouteGetterTrait, RouteTrait;

    protected $name_prefix = '';

    protected $path_prefix = '';

    protected $name = '';

    protected $path = '';

    protected $tokens = [];

    protected $methods = [];

    protected $handler;

    protected $param_sources = [];

    protected $param_handlers = [];

    protected function judgeValidMethod(string $method)
    {
        return true;
    }

    protected function judgeValidRegex(string $regex)
    {
        return true;
    }

    protected function judgeValidSource(string $source)
    {
        switch ($source) {
            case 'uri':
            case 'get':
            case 'post':
            case 'file':
            case 'cookie':
                return true;
            default:
                return false;
        }
    }

    protected function judgeValidHandler($handler)
    {
        if (is_array($handler)) {
            if (is_object($handler[0]) || is_string($handler[0])) {
                if (!isset($handler[1])|| is_string($handler[1])) {
                    return true;
                }
            }
        } elseif (is_callable($handler)) {
            return true;
        }
        return false;
    }

    protected function resolveHandler($handler)
    {
        if (is_array($handler)) {
            $object = is_string($handler[0]) ? new $handler[0] : $handler[0];
            $method = $handler[1] ?? "__invoke";

            $reflector = new ReflectionMethod($object, $method);
            return $reflector->getClosure($object);
        }
        return $handler;
    }

    protected function resolveSources(ServerRequestInterface $request)
    {
        $original_data = [
            'get' => $request->getQueryParams(),
            'post' => $request->getParsedBody(),
            'file' => $request->getUploadedFiles(),
            'cookie' => $request->getCookieParams(),
            'uri' => $this->resolveUriSource($request)
        ];

        foreach ($this->param_sources as $item => $source) {
            if (isset($original_data[$source][$item])) {
                $params[$item] = $original_data[$source][$item];
            }
        }
        return $params;
    }

    protected function handleParams(array $params)
    {
        foreach ($this->param_handlers as $item => $handler) {
            if (isset($params[$item])) {
                $handler = $this->resolveHandler($handler);
                $new_param = $this->callHandler($handler, [$params[$item]]);
                $params[$item] = $new_param;
            }
        }
        
        return $params;
    }

    protected function bindArguments(array $params)
    {
        $handler = $this->resolveHandler($this->handler);
        $parameters = (new ReflectionFunction($handler))->getParameters();
        
        if (!empty($parameters)) {
            if (count($parameters) == 1 && !is_null($reflector = $parameters[0]->getClass())) {
                if ($reflector->implementsInterface(RouteModelInterface::class)) {
                    $constructor = $reflector->getConstructor();                
                    if (!is_null($constructor)) {
                        foreach ($constructor->getParameters() as $key => $parameter) {
                            if (isset($params[$parameter->name])) {
                                $value = $params[$parameter->name];
                                $arguments[$key] = $value;
                            }
                        }
                        $arguments = [$reflector->newInstanceArgs(($arguments ?? []))];
                    }
                } else {
                    if (isset($params[$parameters[0]->name])) {
                        $arguments[] = $params[$parameters[0]->name];
                    }
                }
            } else {
                foreach ($parameters as $key => $parameter) {
                    if (isset($params[$parameter->name])) {
                        $value = $params[$parameter->name];
                        $arguments[$key] = $value;
                    }
                }
            }
        }

        return $arguments ?? [];
    }

    protected function callHandler(Callable $handler, array $arguments)
    {
        switch (count($arguments)) {
            case 0:
                return $handler();
            case 1:
                return $handler($arguments[0]);
            case 2:
                return $handler($arguments[0], $arguments[1]);
            case 3:
                return $handler($arguments[0], $arguments[1], $arguments[2]);
            case 4:
                return $handler($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
            case 5:
                return $handler($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
            default:
                return call_user_func_array($handler, $arguments);
        }
    }

    protected function resolveUriSource(ServerRequestInterface $request)
    {
        $uri = $request->getServerParams()['REQUEST_URI'];
        $uri_path = trim(parse_url($uri)['path'], '/');
        $key_result = preg_match_all('/\{(\w+)\}/', $this->full_path, $key_matches);
        $value_result = preg_match('/' . $this->full_regex . '/', $uri_path, $value_matches);
        
        if ($key_result >= 1 && $value_result === 1) {
            unset($key_matches[0]);
            unset($value_matches[0]);
            return array_combine($key_matches[1], $value_matches);
        }
        return [];
    }
}