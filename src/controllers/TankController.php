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

    public function tankDetails() {
        session_start();

        if (!isset($_SESSION['user_email'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        $tankId = $_GET['id'] ?? null;

        if (!$tankId) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/dashboard");
            exit();
        }

        $tankRepository = new TankRepository();
        $tank = $tankRepository->getTankById($tankId, $_SESSION['user_email']);

        if (!$tank) {
            die("Błąd 404/403: Akwarium nie istnieje lub brak uprawnień do jego przeglądania.");
        }

        // Pobieranie ostatnich logów do wyświetlenia na kafelkach
        $latestLog = $tankRepository->getLatestLog($tankId);

        $this->render('tank-details', [
            'tank' => $tank,
            'latestLog' => $latestLog
        ]);
    }

    public function editTank() {
        session_start();

        if (!isset($_SESSION['user_email'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        $tankId = $_GET['id'] ?? null;
        $tankRepository = new TankRepository();
        $tank = $tankRepository->getTankById($tankId, $_SESSION['user_email']);

        if (!$tank) {
            die("Błąd dostępu do zasobu.");
        }

        if ($this->isPost()) {
            try {
                $tankRepository->updateTank(
                    $tankId,
                    $_POST['name'],
                    $_POST['water_type'],
                    (int)$_POST['volume_liters'],
                    $_SESSION['user_email']
                );
                $url = "http://$_SERVER[HTTP_HOST]";
                header("Location: {$url}/tank_details?id=" . $tankId);
                exit();
            } catch (Exception $e) {
                return $this->render('edit-tank', ['tank' => $tank, 'messages' => ['Błąd aktualizacji: ' . $e->getMessage()]]);
            }
        }

        $this->render('edit-tank', ['tank' => $tank]);
    }

    public function addLog() {
        session_start();

        if (!isset($_SESSION['user_email'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        if ($this->isPost()) {
            $tankId = $_GET['id'] ?? null;
            $tankRepository = new TankRepository();

            // Zabezpieczenie: Sprawdzenie, czy ten użytkownik ma prawo do tego akwarium
            $tank = $tankRepository->getTankById($tankId, $_SESSION['user_email']);

            if ($tank) {
                $tankRepository->addWaterLog(
                    $tankId,
                    (float)$_POST['ph_level'],
                    (float)$_POST['temperature'],
                    $_POST['log_notes']
                );
            }

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/tank_details?id=" . $tankId);
            exit();
        }
    }
}