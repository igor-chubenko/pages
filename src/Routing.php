<?php

namespace src;

use \src\XmlUtils;
use \src\Parameters;
use \src\pageModel;

class Routing
{
    private $routes;
    private $currentRoute;

    public function __construct($filePath)
    {
        $this->currentRoute = false;

        $parameters = new Parameters($filePath);
        $routeParameters = $parameters->getParameters();

        // process routes
        foreach ($routeParameters as $parameter) {
            //check options values
            foreach ($parameter as $key => $option) {
                if (empty($option)) {
                    echo 'File '.$filePath.' incorrect.';
                    exit();
                }
            }

            //check controller class name
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/src/' . $parameter['controller'] . '.php')) {
                echo 'For route ' . $parameter['pattern'] . ' specified incorrect controller class name in parameter "controller"';
                exit();
            }

            $this->routes[$parameter['pattern']] = $parameter;
        }
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function getCurrentRoute(){
        return $this->currentRoute;
    }

    public function checkURI($URI)
    {
        if ($URI == '' || empty($URI) || empty($this->routes[$URI])) {
            return false;
        } else {
            $this->currentRoute = $this->routes[$URI];
            return true;
        }
    }

    public function doAction($className, $actionName){
        $result = new $className(new pageModel($_SERVER['DOCUMENT_ROOT'].'/conf/database.xml'));

        //check
        if(!method_exists($className, $actionName)){
            echo 'Unknown action '.$actionName.' in controller '.$className;
            exit();
        }

        return $result->$actionName();
    }
} 