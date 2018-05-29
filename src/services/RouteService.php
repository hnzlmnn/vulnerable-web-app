<?php

class RouteService {

    private static $instance;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new RouteService();
        }
        return self::$instance;
    }

    private $routes = array();

    public function __construct() {
        $this->addRoute(array(
            new Route(
                'home',
                '/admin',
                'dashboard',
                'Dashboard',
                array(
                    UserService::RIGHT_SUPERUSER,
                    UserService::RIGHT_EDITOR
                ),
                'OR'
            ),
            new Route(
                'article',
                array(
                    '/admin/articles',
                    '/admin/articles/new',
                    '/admin/articles/edit'
                ),
                'file-text',
                'Articles',
                array(
                    UserService::RIGHT_SUPERUSER,
                    UserService::RIGHT_EDITOR
                ),
                'OR'
            ),
            new Route(
                'comments',
                array(
                    '/admin/comments'
                ),
                'comments',
                'Comments',
                array(
                    UserService::RIGHT_SUPERUSER,
                    UserService::RIGHT_EDITOR
                ),
                'OR'
            ),
            new Route(
                'users',
                array(
                    '/admin/users',
                    '/admin/users/add',
                    '/admin/users/edit'
                ),
                'users',
                'Users',
                array(
                    UserService::RIGHT_SUPERUSER
                )
            )
        ));
    }

    public function addRoute($routes) {
        if (is_array($routes)) {
            foreach ($routes as $route) {
                $this->addRoute($route);
            }
            return;
        }
        $this->routes[] = $routes;
    }

    public function isActive($route) {
        if (is_string($route)) {
            $route = $this->getRoute($route);
        }
        if ($route === null) {
            return false;
        }
//        var_dump(Base::instance()->get('PATH'));
//        var_dump($route->getUrl());
        return $route->isRoute(Base::instance()->get('PATH'));
    }

    public function getRoutes($all = false) {
        if ($all) {
            return $this->routes;
        }
        $routes = array();
        foreach ($this->routes as $route) {
            if ($route->hasRights()) {
                $routes[] = $route;
            }
        }
        return $routes;
    }

    public function getRoute($name) {
        if ($name === null || empty($name)) {
            return null;
        }
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }
        return null;
    }

}