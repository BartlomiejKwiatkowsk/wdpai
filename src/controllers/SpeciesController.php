<?php

require_once 'AppController.php';
require_once __DIR__ .'/../repository/SpeciesRepository.php';
require_once __DIR__ .'/../repository/TankRepository.php';

class SpeciesController extends AppController {

    const MAX_FILE_SIZE = 1024 * 1024 * 5; // 5MB
    const SUPPORTED_TYPES = ['image/png', 'image/jpeg'];
    const UPLOAD_DIRECTORY = '/../public/img/catalog/';

    public function speciesCatalog() {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            header("Location: /login");
            exit();
        }

        $speciesRepository = new SpeciesRepository();
        $speciesList = $speciesRepository->getSpeciesList();

        $tankRepository = new TankRepository();
        $userTanks = $tankRepository->getTanks($_SESSION['user_email']);

        $this->render('species-catalog', [
            'speciesList' => $speciesList,
            'userTanks' => $userTanks
        ]);
    }

    public function addSpeciesToTankAction() {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            header("Location: /login");
            exit();
        }

        if ($this->isPost()) {
            $speciesId = $_POST['species_id'] ?? null;
            $tankId = $_POST['tank_id'] ?? null;
            $quantity = (int)($_POST['quantity'] ?? 1);
            $health = 'Good';

            if($speciesId && $tankId && $quantity > 0) {
                $speciesRepository = new SpeciesRepository();
                try {
                    $speciesRepository->addSpeciesToTank($tankId, $speciesId, $quantity, $health);
                    header("Location: /tank_details?id=" . $tankId);
                    exit();
                } catch (Exception $e) {
                    $rawError = $e->getMessage();

                    if (strpos($rawError, 'Ecosystem mismatch') !== false) {
                        $cleanError = preg_replace('/^.*?ERROR:\s*/', '', $rawError);
                        $cleanError = explode('CONTEXT:', $cleanError)[0];
                        $_SESSION['error_message'] = trim($cleanError);
                    } else {
                        $_SESSION['error_message'] = "Critical database error occurred.";
                    }

                    header("Location: /tank_details?id=" . $tankId);
                    exit();
                }
            }
        }
    }

    public function createNewSpecies() {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            header("Location: /login");
            exit();
        }

        if ($this->isPost() && is_uploaded_file($_FILES['file']['tmp_name']) && $this->validate($_FILES['file'])) {

            $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $uniqueFilename = uniqid('species_') . '.' . $fileExtension;
            $uploadPath = dirname(__DIR__) . self::UPLOAD_DIRECTORY . $uniqueFilename;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {

                $dbImagePath = '/public/img/catalog/' . $uniqueFilename;

                $species = new Species(
                    null,
                    $_POST['common_name'],
                    $_POST['scientific_name'],
                    $_POST['water_type'],
                    (float)$_POST['ph_min'],
                    (float)$_POST['ph_max'],
                    (float)$_POST['temp_min'],
                    (float)$_POST['temp_max'],
                    $dbImagePath
                );

                $speciesRepository = new SpeciesRepository();
                $speciesRepository->addNewSpecies($species);

                header("Location: /catalog");
                exit();
            }
        }

        $_SESSION['error_message'] = "Failed to upload file or format is not supported.";
        header("Location: /catalog");
        exit();
    }

    private function validate(array $file): bool {
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return false;
        }
        if (!isset($file['type']) || !in_array($file['type'], self::SUPPORTED_TYPES)) {
            return false;
        }
        return true;
    }
}