<?php

require_once 'AppController.php';
require_once __DIR__ .'/../repository/SpeciesRepository.php';
require_once __DIR__ .'/../repository/TankRepository.php';

class SpeciesController extends AppController {

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

                    // Ekstrakcja czystego komunikatu błędu
                    if (strpos($rawError, 'Niezgodność ekosystemu') !== false) {
                        $cleanError = preg_replace('/^.*?ERROR:\s*/', '', $rawError);
                        $cleanError = explode('CONTEXT:', $cleanError)[0];
                        $_SESSION['error_message'] = trim($cleanError);
                    } else {
                        $_SESSION['error_message'] = "Wystąpił krytyczny błąd bazy danych.";
                    }

                    header("Location: /tank_details?id=" . $tankId);
                    exit();
                }
            }
        }
    }
}