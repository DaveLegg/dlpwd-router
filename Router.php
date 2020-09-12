<?php

namespace dlpwd\router;

use ReflectionFunction;
use ReflectionMethod;

class Router
{
    private $__routes = [
        'get' => [],
        'post' => [],
        'delete' => [],
    ];

    private $__prefix = '';

    public function get($_url, $_handler) {
        $this->__routes['get'][$this->toRegex($_url)] = $_handler;
    }

    public function post($_url, $_handler) {
        $this->__routes['post'][$this->toRegex($_url)] = $_handler;
    }

    public function delete($_url, $_handler) {
        $this->__routes['delete'][$this->toRegex($_url)] = $_handler;
    }

    public function addRoute($_method, $_url, $_handler) {
        if(!\in_array(\strtolower($_method), ['get', 'post', 'delete'])) {
            throw new \InvalidArgumentException(sprintf('Argument 1 to \\dlpwd\\router\\Router::addRoute must be one of "GET", "POST" or "DELETE". %s given', $_method));
        }
        $this->__routes[\strtolower($_method)][$this->toRegex($_url)] = $_handler;
    }

    private function toRegex($_url) {
        $_url = $this->__prefix . '/' . trim($_url, '/');
        return \preg_replace_callback('/{\s*?([a-zA-Z_][\w]+)\s*?}/', function($matches) {
            return '(?<'.trim($matches[1]).'>[^\/]+)';
        }, str_replace('/', '\/',$_url));
    }

    public function addPrefix($_prefix, callable $_callback) {
        $oldPrefix = $this->__prefix;

        $this->__prefix = $oldPrefix . '/' . trim($_prefix,'/');

        $_callback($this);

        $this->__prefix = $oldPrefix;
    }

    public function resolve($_url = null, $_method = null)
    {
        if(!isset($_url)) {
            $_url = strtok($_SERVER["REQUEST_URI"], '?');
        }
        if(!isset($_method)) {
            $_method = $_SERVER['REQUEST_METHOD'];
        }
        foreach($this->__routes[strtolower($_method)] as $_regex => $_handler) {
            $matches = [];
            $result = \preg_match('/^' . $_regex . '\/?$/', $_url, $matches);
            if($result == 1) {

                if($_handler instanceof \Closure) {
                    $reflection = new \ReflectionFunction($_handler);
                } else {
                    $_handlerMethod = '';
                    if(!\is_callable($_handler, false, $_handlerMethod)) {
                        throw new RouterException(sprintf("The handler provided for the route was not provided in a format the router can understand.\nRequested URL: %s", $_url));
                    }
                    if(\stripos($_handlerMethod, '::')) {

                        $handlerData = \explode('::', $_handlerMethod);
                        if(!\class_exists($handlerData[0])) {
                            throw new RouterException(sprintf("The class specified as the controller for the requested url does not exist.\nRequested URL: %s\nController class: %s", $_url, $handlerData[0]), 500);
                        }
        
                        if(!\method_exists($handlerData[0], $handlerData[1])) {
                            throw new RouterException(sprintf("The method specified as the controller for the requested url does not exist on the specified class.\nRequested URL: %s\nController class: %s\nMethod: %s", $_url, $handlerData[0], $handlerData[1]), 500);
                        }
        
                        $reflection = new \ReflectionMethod($_handlerMethod);
        
                        if(!$reflection->isPublic()) {
                            throw new RouterException(sprintf("The method specified as the controller for the requested url is not public\nRequested URL: %s\nController Class: %s\nMethod: %s", $_url, $handlerData[0], $handlerData[1]), 500);
                        }
                    } else {
                        $reflection = new \ReflectionFunction($_handler);
                    }
                }

                $pass = array();
                foreach ($reflection->getParameters() as $param) {
                    if (isset($matches[$param->getName()])) {
                        $pass[] = $matches[$param->getName()];
                    } else {
                        if($param->isDefaultValueAvailable()) {
                            $pass[] = $param->getDefaultValue();
                        }
                        else {
                            throw new RouterException(sprintf('No value provided for parameter %s on method %s in class %s. Either update the route calling this controller method to capture the parameter value, or provide a default value for the parameter in the method definition.',$param->getName(), $handlerData[1], $handlerData[0]), 500);
                        }
                    }
                }

                if($reflection instanceof ReflectionMethod) {

                    if ($reflection->isStatic()) {
                        $reflection->invokeArgs(null, $pass);
                    } else {
                        $controllerClass = new $handlerData[0]();
        
                        return $reflection->invokeArgs($controllerClass, $pass);
                    }
                } else if ($reflection instanceof ReflectionFunction) {
                    return $reflection->invokeArgs($pass);
                }
            }
        }
        
        throw new RouterException(\sprintf('No route found which matches requested URL "%s"', $_url), 404);
    }
}
