<?php
namespace Leap;

use Psr\Container\ContainerInterface;

class ControllerFactory
{

    private $di;

    function __construct(ContainerInterface $di)
    {
        /* Normally it is bad design to inject DI Container into a class,
         * because it can be misused as a service locator. However, for this
         * purpose it is OK, as this is a special case, because the Controller
         * class is only known at runtimeand the DIC is only used to resolve
         * the Controller, not to retrieve other services.
         */
        $this->di = $di;
    }

    public function make(Route $route): Controller
    {
        $controllerInfo = $route->callback;
        $this->loadControllerClass($controllerInfo);
        $controllerClass = $this->getNamespaceClass($controllerInfo);

        /** @var Controller $controller */
        $controller = null;

        /* Check if controller class extends the core controller */
        if ($controllerClass == 'Leap\Controller' || is_subclass_of($controllerClass, "Leap\\Core\\Controller")) {
            /* Create the controller instance */
            $hooks = $this->di->get('hooks');
            $pluginManager = $this->di->get('pluginManager');
            $config = $this->di->get('config');
            $controller = new Controller($route, $hooks, $pluginManager, $config, null);
        } else if (class_exists($controllerClass)) {
            /* TODO: error handling */
            pre("Controller class '" . $controllerClass . "' does not extend the base 'Leap\\Core\\Controller' class", true);
        } else {
            /* TODO: error handling */
            pre("Controller class '" . $controllerClass . "' not found", true);
        }

        return $controller;
    }

    private function getNamespaceClass(array $controllerInfo): string
    {
        /* If the controller class name does not contain the namespace yet, add it */
        if (strpos($controllerInfo['class'], "\\") === false && isset($controllerInfo['plugin'])) {
            $namespace               = getNamespace($controllerInfo['plugin'], "controller");
            $controllerInfo['class'] = $namespace . $controllerInfo['class'];
        }
        return $controllerInfo['class'];
    }

    private function loadControllerClass(array $controllerInfo): void
    {
        /* Add controller class to autoloader if a custom file for controller class is specified */
        if (isset($controllerInfo['file'])) {
            global $autoloader;
            $autoloader->addClassMap(["Leap\\Plugins\\" . ucfirst($controllerInfo['plugin']) . "\\Controllers\\" . $controllerInfo['class'] => $controllerInfo['file']]);
        }
    }
}