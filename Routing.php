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
            // Wymóg obsługi błędów globalnie (Błąd 404 z regulaminu)
            $templatePath = 'public/views/404.html';
            if(file_exists($templatePath)){
                include($templatePath);
            } else {
                echo "Strona nie istnieje! (404)";
            }
            return;
        }

        $controllerName = self::$routes[$url];

        // System kierowania ruchem do odpowiednich kontrolerów
        if ($controllerName === 'login' || $controllerName === 'logout') {
            require_once 'src/controllers/SecurityController.php';
            $object = new SecurityController();
        } elseif ($controllerName === 'addTank') {
            require_once 'src/controllers/TankController.php';
            $object = new TankController();
        } else {
            require_once 'src/controllers/DefaultController.php';
            $object = new DefaultController();
        }

        if(method_exists($object, $controllerName)){
            $object->$controllerName();
        } else {
            // Zmiana komunikatu, aby był uniwersalny dla wszystkich kontrolerów
            echo "Akcja $controllerName nie została zaimplementowana w przypisanym kontrolerze!";
        }
    }
}