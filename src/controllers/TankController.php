<?php

require_once 'AppController.php';
require_once __DIR__ .'/../models/Tank.php';
require_once __DIR__ .'/../repository/TankRepository.php';

class TankController extends AppController {

    public function addTank() {
        session_start();

        if (!isset($_SESSION['user_email'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        if ($this->isPost()) {
            $tank = new Tank(
                null,
                $_POST['name'],
                $_POST['water_type'],
                (int)$_POST['volume_liters'],
                'Empty'
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

        $this->render('add-tank');
    }

    // Nowa metoda obsługująca widok zarządzania akwarium
    public function tankDetails() {
        session_start();

        if (!isset($_SESSION['user_email'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        // Pobieranie ID z paska adresu (przygotowanie pod logikę wyciągania danych z bazy)
        $tankId = $_GET['id'] ?? null;

        // Na ten moment renderujemy czysty widok.
        // W przyszłości pobierzemy tu dane konkretnego akwarium przez TankRepository
        $this->render('tank-details');
    }
}