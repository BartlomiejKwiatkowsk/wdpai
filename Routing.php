<?php

require_once 'src/controllers/DefaultController.php';

class Routing {
    public static $routes;

    public static function get($url, $view) {
        self::$routes[$url] = $view;
    }

    // Dodana brakująca metoda POST
    public static function post($url, $view) {
        self::$routes[$url] = $view;
    }

    public static function run($url) {
        if ($url === '') {
            $url = '';
        }

        if (!array_key_exists($url, self::$routes)) {
            $templatePath = 'public/views/404.html';
            if(file_exists($templatePath)){
                include($templatePath);
            } else {
                echo "Strona nie istnieje! (404)";
            }
            return;
        }

        $controllerName = self::$routes[$url];

        if ($controllerName === 'login' || $controllerName === 'logout') {
            require_once 'src/controllers/SecurityController.php';
            $object = new SecurityController();
        } elseif ($controllerName === 'addTank' || $controllerName === 'tankDetails' || $controllerName === 'editTank' || $controllerName === 'addLog' || $controllerName === 'addEquipment' || $controllerName === 'addLivestock' || $controllerName === 'deleteItem') {
            require_once 'src/controllers/TankController.php';
            $object = new TankController();
        } elseif ($controllerName === 'speciesCatalog' || $controllerName === 'addSpeciesToTankAction' || $controllerName === 'createNewSpecies') {
            require_once 'src/controllers/SpeciesController.php';
            $object = new SpeciesController();
        } else {
            require_once 'src/controllers/DefaultController.php';
            $object = new DefaultController();
        }

        if(method_exists($object, $controllerName)){
            $object->$controllerName();
        } else {
            echo "Akcja $controllerName nie została zaimplementowana w przypisanym kontrolerze!";
        }
    }
}