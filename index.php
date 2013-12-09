<?php
function __autoload($class_name) {
    include $class_name . '.php';
}

use src\Routing;

$routes = new Routing($_SERVER['DOCUMENT_ROOT'].'/conf/route.xml');

$URI = explode('?', $_SERVER['REQUEST_URI']);
if(!$routes->checkURI($URI[0])){
    die('Specified incorrect URL');
}

if(!$currentRoute = $routes->getCurrentRoute()){
    die('Internal error');
}

__autoload('src\\'.$currentRoute['controller']);

echo $routes->doAction('src\\'.$currentRoute['controller'], $currentRoute['action'].'Action');