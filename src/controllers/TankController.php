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
            header("Location: http://$_SERVER[HTTP_HOST]/login");
            exit();
        }

        $tankId = $_GET['id'] ?? null;
        if (!$tankId) {
            header("Location: http://$_SERVER[HTTP_HOST]/dashboard");
            exit();
        }

        $tankRepository = new TankRepository();
        $tank = $tankRepository->getTankById($tankId, $_SESSION['user_email']);

        if (!$tank) {
            die("Błąd 404/403: Brak uprawnień do tego zbiornika.");
        }

        $latestLog = $tankRepository->getLatestLog($tankId);
        $equipment = $tankRepository->getEquipmentForTank($tankId);
        $livestock = $tankRepository->getLivestockForTank($tankId);
        $speciesList = $tankRepository->getAllSpecies();

        $this->render('tank-details', [
            'tank' => $tank,
            'latestLog' => $latestLog,
            'equipment' => $equipment,
            'livestock' => $livestock,
            'speciesList' => $speciesList
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
            header("Location: http://$_SERVER[HTTP_HOST]/login");
            exit();
        }

        if ($this->isPost()) {
            $tankId = $_GET['id'] ?? null;
            $phLevel = (float)$_POST['ph_level'];
            $temperature = (float)$_POST['temperature'];

            // Twarda walidacja backendowa zabezpieczająca przed PDOException
            if ($phLevel < 0 || $phLevel > 14 || $temperature < 10 || $temperature > 50) {
                $_SESSION['error_message'] = "Błąd zapisu parametrów: pH musi znajdować się w przedziale od 0 do 14, a temperatura od 10°C do 50°C.";
                header("Location: http://$_SERVER[HTTP_HOST]/tank_details?id=" . $tankId);
                exit();
            }

            $tankRepository = new TankRepository();
            $tank = $tankRepository->getTankById($tankId, $_SESSION['user_email']);

            if ($tank) {
                $tankRepository->addWaterLog(
                    $tankId,
                    $phLevel,
                    $temperature,
                    $_POST['log_notes']
                );
            }

            header("Location: http://$_SERVER[HTTP_HOST]/tank_details?id=" . $tankId);
            exit();
        }
    }

    public function deleteItem() {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        if ($this->isPost()) {
            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            if ($contentType === "application/json") {
                $content = trim(file_get_contents("php://input"));
                $decoded = json_decode($content, true);

                $itemId = $decoded['id'] ?? null;
                $itemType = $decoded['type'] ?? null;

                if ($itemId && $itemType) {
                    $tankRepository = new TankRepository();
                    $success = $tankRepository->deleteItem($itemId, $itemType);

                    if ($success) {
                        http_response_code(200);
                        echo json_encode(['status' => 'success']);
                        exit();
                    }
                }
            }
        }

        http_response_code(400);
        echo json_encode(['status' => 'error']);
        exit();
    }
}