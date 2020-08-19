<?php 
namespace API\Controllers;
class Router {

    private $_routes;

    public function __construct($routes = []){
        $this->setRoutes($routes);
    }

    public function routeExist($route){
        return $this->getRoute($route) ? true : false;
    }

    public function getRoute($route) {
        return isset($this->_routes[$route]) ? $this->_routes[$route] : false;
    }

    public function setRoutes($routes) {
        $this->_routes = (array) $routes;
        return $this;
    }

    public function addRoute($name, $route) {
        $this->_routes[$name] = $route;
        return $this;
    }
}