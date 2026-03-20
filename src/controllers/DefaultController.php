<?php

require_once 'AppController.php';
require_once __DIR__ .'/../repository/TankRepository.php'; // DODANE

class DefaultController extends AppController {

    public function login() {
        $this->render('login');
    }

    public function dashboard() {
        session_start();

        if (!isset($_SESSION['user_email'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        // Pobieramy akwaria z bazy
        $tankRepository = new TankRepository();
        $tanks = $tankRepository->getTanks($_SESSION['user_email']);

        // Przekazujemy tablicę obiektów 'tanks' do widoku
        $this->render('dashboard', [
            'userEmail' => $_SESSION['user_email'],
            'userRole' => $_SESSION['user_role'],
            'tanks' => $tanks
        ]);
    }
}