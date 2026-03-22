<?php

require_once 'AppController.php';
require_once __DIR__ .'/../models/Tank.php';
require_once __DIR__ .'/../repository/TankRepository.php';

class TankController extends AppController {

    public function addTank() {
        session_start();

        // Weryfikacja sesji
        if (!isset($_SESSION['user_email'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        // Obsługa wysłania formularza
        if ($this->isPost()) {
            // Tworzenie obiektu na podstawie danych z POST
            $tank = new Tank(
                null,
                $_POST['name'],
                $_POST['water_type'],
                (int)$_POST['volume_liters'],
                'Empty' // Domyślny status dla nowego zbiornika
            );

            $tankRepository = new TankRepository();

            try {
                $tankRepository->addTank($tank, $_SESSION['user_email']);
                $url = "http://$_SERVER[HTTP_HOST]";
                header("Location: {$url}/dashboard");
                exit();
            } catch (Exception $e) {
                return $this->render('add-tank', ['messages' => ['Błąd zapisu: ' . $e->getMessage()]]);
            }
        }

        // Zwykłe wejście na stronę (GET) renderuje czysty formularz
        $this->render('add-tank');
    }
}