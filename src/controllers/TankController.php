<?php

require_once 'AppController.php';
require_once __DIR__ .'/../models/Tank.php';
require_once __DIR__ .'/../repository/TankRepository.php';

class TankController extends AppController {

    public function addTank() {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            header("Location: http://$_SERVER[HTTP_HOST]/login");
            exit();
        }

        if ($this->isPost()) {
            $dbImagePath = '/public/img/tanks/default-tank.png';
            if (isset($_FILES['tank_image']) && is_uploaded_file($_FILES['tank_image']['tmp_name'])) {
                $fileExtension = pathinfo($_FILES['tank_image']['name'], PATHINFO_EXTENSION);
                $uniqueFilename = uniqid('tank_') . '.' . $fileExtension;
                $uploadPath = dirname(__DIR__) . '/../public/img/tanks/' . $uniqueFilename;
                if (move_uploaded_file($_FILES['tank_image']['tmp_name'], $uploadPath)) {
                    $dbImagePath = '/public/img/tanks/' . $uniqueFilename;
                }
            }

            $tank = new Tank(
                null, $_POST['name'], $_POST['water_type'], (int)$_POST['volume_liters'],
                'Empty', 0, $dbImagePath, $_POST['notes'] ?? null
            );
            $tankRepository = new TankRepository();

            try {
                $tankRepository->addTank($tank, $_SESSION['user_email']);
                header("Location: http://$_SERVER[HTTP_HOST]/dashboard");
                exit();
            } catch (Exception $e) {
                return $this->render('add-tank', ['messages' => ['Save Error: ' . $e->getMessage()]]);
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

        if (!$tank) die("Błąd 404/403: Brak uprawnień do tego zbiornika.");

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
            header("Location: http://$_SERVER[HTTP_HOST]/login");
            exit();
        }

        $tankId = $_GET['id'] ?? null;
        $tankRepository = new TankRepository();
        $tank = $tankRepository->getTankById($tankId, $_SESSION['user_email']);

        if (!$tank) die("Błąd dostępu do zasobu.");

        if ($this->isPost()) {
            $dbImagePath = $tank->getImagePath();
            if (isset($_FILES['tank_image']) && is_uploaded_file($_FILES['tank_image']['tmp_name'])) {
                $fileExtension = pathinfo($_FILES['tank_image']['name'], PATHINFO_EXTENSION);
                $uniqueFilename = uniqid('tank_') . '.' . $fileExtension;
                $uploadPath = dirname(__DIR__) . '/../public/img/tanks/' . $uniqueFilename;
                if (move_uploaded_file($_FILES['tank_image']['tmp_name'], $uploadPath)) {
                    $dbImagePath = '/public/img/tanks/' . $uniqueFilename;
                }
            }
            try {
                $tankRepository->updateTank(
                    $tankId, $_POST['name'], $_POST['water_type'], (int)$_POST['volume_liters'],
                    $_SESSION['user_email'], $dbImagePath, $_POST['notes'] ?? null
                );
                header("Location: http://$_SERVER[HTTP_HOST]/tank_details?id=" . $tankId);
                exit();
            } catch (Exception $e) {
                return $this->render('edit-tank', ['tank' => $tank, 'messages' => ['Update Error: ' . $e->getMessage()]]);
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

            if ($phLevel < 0 || $phLevel > 14 || $temperature < 10 || $temperature > 50) {
                $_SESSION['error_message'] = "Błąd zapisu parametrów: pH musi znajdować się w przedziale od 0 do 14, a temperatura od 10°C do 50°C.";
                header("Location: http://$_SERVER[HTTP_HOST]/tank_details?id=" . $tankId);
                exit();
            }

            $tankRepository = new TankRepository();
            $tank = $tankRepository->getTankById($tankId, $_SESSION['user_email']);

            if ($tank) {
                $tankRepository->addWaterLog($tankId, $phLevel, $temperature, null);
            }
            header("Location: http://$_SERVER[HTTP_HOST]/tank_details?id=" . $tankId);
            exit();
        }
    }

    public function addEquipment() {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            header("Location: http://$_SERVER[HTTP_HOST]/login");
            exit();
        }

        if ($this->isPost()) {
            $tankId = $_GET['id'] ?? null;
            $tankRepository = new TankRepository();
            $tank = $tankRepository->getTankById($tankId, $_SESSION['user_email']);

            if ($tank) {
                $tankRepository->addEquipment($tankId, $_POST['eq_name'], $_POST['eq_type'], $_POST['eq_status']);
            }
            header("Location: http://$_SERVER[HTTP_HOST]/tank_details?id=" . $tankId);
            exit();
        }
    }

    public function addLivestock() {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            header("Location: http://$_SERVER[HTTP_HOST]/login");
            exit();
        }

        if ($this->isPost()) {
            $tankId = $_GET['id'] ?? null;
            $tankRepository = new TankRepository();
            $tank = $tankRepository->getTankById($tankId, $_SESSION['user_email']);

            if ($tank) {
                try {
                    $tankRepository->addLivestock($tankId, $_POST['species_id'], (int)$_POST['quantity'], $_POST['health']);
                    header("Location: http://$_SERVER[HTTP_HOST]/tank_details?id=" . $tankId);
                    exit();
                } catch (Exception $e) {
                    $rawError = $e->getMessage();
                    if (strpos($rawError, 'Ecosystem mismatch') !== false || strpos($rawError, 'Niezgodność ekosystemu') !== false) {
                        preg_match('/(Ecosystem mismatch:.*?!|Niezgodność ekosystemu:.*?!)/', $rawError, $matches);
                        $_SESSION['error_message'] = $matches[0] ?? "Ecosystem compatibility error.";
                    } else {
                        $_SESSION['error_message'] = "Critical database error occurred.";
                    }
                    header("Location: http://$_SERVER[HTTP_HOST]/tank_details?id=" . $tankId);
                    exit();
                }
            }
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
                    if ($tankRepository->deleteItem($itemId, $itemType)) {
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

    public function deleteTank() {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            header("Location: http://$_SERVER[HTTP_HOST]/login");
            exit();
        }

        if ($this->isPost()) {
            $tankId = $_POST['tank_id'] ?? null;
            if ($tankId) {
                $tankRepository = new TankRepository();
                $tank = $tankRepository->getTankById($tankId, $_SESSION['user_email']);

                if ($tank) {
                    try {
                        $tankRepository->deleteTank($tankId);
                        header("Location: http://$_SERVER[HTTP_HOST]/dashboard");
                        exit();
                    } catch (Exception $e) {
                        $_SESSION['error_message'] = "Failed to delete tank: " . $e->getMessage();
                        header("Location: http://$_SERVER[HTTP_HOST]/tank_details?id=" . $tankId);
                        exit();
                    }
                } else {
                    $_SESSION['error_message'] = "Unauthorized or tank not found.";
                    header("Location: http://$_SERVER[HTTP_HOST]/dashboard");
                    exit();
                }
            }
        }
        header("Location: http://$_SERVER[HTTP_HOST]/dashboard");
        exit();
    }
}