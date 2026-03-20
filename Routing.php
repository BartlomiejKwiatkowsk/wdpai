<?php

require_once 'src/controllers/DefaultController.php';

class Routing {
    public static $routes;

    public static function get($url, $view) {
        self::$routes[$url] = $view;
    }

    public static function run($url) {
        if ($url === '') {
            $url = '';
        }

        if (!array_key_exists($url, self::$routes)) {
            // Wymóg obsługi błędów globalnie
            $templatePath = 'public/views/404.html';
            if(file_exists($templatePath)){
                include($templatePath);
            } else {
                echo "Strona nie istnieje! (404)";
            }
            return;
        }

        $controllerName = self::$routes[$url];


        if ($controllerName === 'login') {
            require_once 'src/controllers/SecurityController.php';
            $object = new SecurityController();
        } else {
            require_once 'src/controllers/DefaultController.php';
            $object = new DefaultController();
        }

        if(method_exists($object, $controllerName)){
            $object->$controllerName();
        } else {
            echo "Akcja $controllerName nie została zaimplementowana w kontrolerze DefaultController!";
        }
    }
}