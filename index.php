<?php

require_once "Routing.php";

$path = trim($_SERVER["REQUEST_URI"], '/');
$path = parse_url($path, PHP_URL_PATH);

Routing::get('login', 'login');
Routing::get('dashboard', 'dashboard');
Routing::get('', 'login');
Routing::get('logout', 'logout');
Routing::get('addTank', 'addTank');
Routing::get('tank_details', 'tankDetails');
Routing::get('editTank', 'editTank');
Routing::get('addLog', 'addLog');
Routing::get('addEquipment', 'addEquipment');
Routing::get('addLivestock', 'addLivestock');
Routing::get('catalog', 'speciesCatalog');
Routing::get('addSpeciesToTank', 'addSpeciesToTankAction');
Routing::get('register', 'register');
Routing::get('users', 'usersPanel');


Routing::post('updateUserRole', 'updateUserRole');
Routing::post('updateUserPassword', 'updateUserPassword');
Routing::post('deleteUser', 'deleteUser');
Routing::post('register', 'register');
Routing::post('createNewSpecies', 'createNewSpecies');
Routing::post('deleteItem', 'deleteItem');
Routing::post('deleteTank', 'deleteTank');

Routing::run($path);