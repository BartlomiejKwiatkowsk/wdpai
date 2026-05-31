<?php

require_once 'AppController.php';
require_once __DIR__ .'/../repository/SpeciesRepository.php';
require_once __DIR__ .'/../repository/TankRepository.php';

class SpeciesController extends AppController {

    const MAX_FILE_SIZE = 1024 * 1024 * 5;
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
                    if (strpos($rawError, 'Ecosystem mismatch') !== false || strpos($rawError, 'Niezgodność ekosystemu') !== false) {
                        preg_match('/(Ecosystem mismatch:.*?!|Niezgodność ekosystemu:.*?!)/', $rawError, $matches);
                        $_SESSION['error_message'] = $matches[0] ?? "Ecosystem compatibility error.";
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
        $this->enforceAdmin();

        if ($this->isPost() && is_uploaded_file($_FILES['file']['tmp_name']) && $this->validate($_FILES['file'])) {
            $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $uniqueFilename = uniqid('species_') . '.' . $fileExtension;
            $uploadPath = dirname(__DIR__) . self::UPLOAD_DIRECTORY . $uniqueFilename;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
                $dbImagePath = '/public/img/catalog/' . $uniqueFilename;
                $species = new Species(
                    null, $_POST['common_name'], $_POST['scientific_name'], $_POST['water_type'],
                    (float)$_POST['ph_min'], (float)$_POST['ph_max'], (float)$_POST['temp_min'], (float)$_POST['temp_max'], $dbImagePath
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

    public function editSpecies() {
        session_start();
        $this->enforceAdmin();

        if ($this->isPost()) {
            $speciesId = $_POST['species_id'];
            $speciesRepository = new SpeciesRepository();
            $existingSpecies = $speciesRepository->getSpeciesById($speciesId);

            if (!$existingSpecies) {
                $_SESSION['error_message'] = "Gatunek nie istnieje.";
                header("Location: /catalog");
                exit();
            }

            $dbImagePath = $existingSpecies->getImagePath();

            if (isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name']) && $this->validate($_FILES['file'])) {
                $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                $uniqueFilename = uniqid('species_') . '.' . $fileExtension;
                $uploadPath = dirname(__DIR__) . self::UPLOAD_DIRECTORY . $uniqueFilename;

                if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
                    $dbImagePath = '/public/img/catalog/' . $uniqueFilename;
                }
            }

            $species = new Species(
                $speciesId, $_POST['common_name'], $_POST['scientific_name'], $_POST['water_type'],
                (float)$_POST['ph_min'], (float)$_POST['ph_max'], (float)$_POST['temp_min'], (float)$_POST['temp_max'], $dbImagePath
            );

            $speciesRepository->updateSpecies($species);
            header("Location: /catalog");
            exit();
        }
    }

    public function deleteSpecies() {
        session_start();
        $this->enforceAdmin();

        if ($this->isPost()) {
            $speciesId = $_POST['species_id'] ?? null;
            if ($speciesId) {
                $speciesRepository = new SpeciesRepository();
                try {
                    $speciesRepository->deleteSpecies($speciesId);
                } catch (PDOException $e) {
                    // Blokada 23503 to naruszenie klucza obcego ON DELETE RESTRICT
                    if ($e->getCode() == '23503') {
                        $_SESSION['error_message'] = "Błąd: Nie można usunąć gatunku. Przynajmniej jeden użytkownik posiada ten gatunek w swoim akwarium.";
                    } else {
                        $_SESSION['error_message'] = "Krytyczny błąd bazy danych.";
                    }
                }
            }
        }
        header("Location: /catalog");
        exit();
    }

    private function enforceAdmin() {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            die("Błąd 403: Brak uprawnień. Tylko administrator ma dostęp do tej operacji.");
        }
    }

    private function validate(array $file): bool {
        if ($file['size'] > self::MAX_FILE_SIZE) return false;
        if (!isset($file['type']) || !in_array($file['type'], self::SUPPORTED_TYPES)) return false;
        return true;
    }
}