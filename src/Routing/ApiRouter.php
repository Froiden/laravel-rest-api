<?php

namespace Froiden\RestAPI\Routing;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Routing\Router;

class ApiRouter extends Router
{

    /**
     * Route a resource to a controller.
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return void
     */
    public function resource($name, $controller, array $options = [])
    {
        if ($this->container && $this->container->bound('Froiden\RestAPI\Routing\ApiResourceRegistrar')) {
            $registrar = $this->container->make('Froiden\RestAPI\Routing\ApiResourceRegistrar');
        }
        else {
            $registrar = new ResourceRegistrar($this);
        }

        $registrar->register($name, $controller, $options);
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    protected function addRoute($methods, $uri, $action)
    {
        // We do not keep routes in ApiRouter. Whenever a route is added,
        // we add it to Laravel's primary route collection
        $routes = app("router")->getRoutes();

        $routes->add($this->createRoute($methods, $uri, $action));

        app("router")->setRoutes($routes);
    }
}