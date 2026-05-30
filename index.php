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
Routing::post('createNewSpecies', 'createNewSpecies');
Routing::post('deleteItem', 'deleteItem');

Routing::run($path);