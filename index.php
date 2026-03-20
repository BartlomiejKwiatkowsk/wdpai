<?php

require_once "Routing.php";

$path = trim($_SERVER["REQUEST_URI"], '/');
$path = parse_url($path, PHP_URL_PATH);

Routing::get('login', 'login');
Routing::get('dashboard', 'dashboard');
Routing::get('', 'login');
Routing::get('logout', 'logout');

Routing::run($path);