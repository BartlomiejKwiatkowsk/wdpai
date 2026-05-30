<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Species.php';

class SpeciesRepository extends Repository {

    public function getSpeciesList(): array {
        $stmt = $this->database->connect()->prepare('SELECT * FROM public.species ORDER BY common_name ASC');
        $stmt->execute();

        $speciesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];

        foreach ($speciesData as $species) {
            $result[] = new Species(
                $species['id_species'],
                $species['common_name'],
                $species['scientific_name'],
                $species['water_compatibility'],
                $species['ideal_ph_min'],
                $species['ideal_ph_max'],
                $species['ideal_temp_min'],
                $species['ideal_temp_max'],
                $species['image_path']
            );
        }
        return $result;
    }

    public function addSpeciesToTank(string $tankId, string $speciesId, int $quantity, string $health): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO public.tank_livestock (id_tank, id_species, quantity, health)
            VALUES (:tankId, :speciesId, :quantity, :health)
            ON CONFLICT (id_tank, id_species) 
            DO UPDATE SET quantity = public.tank_livestock.quantity + EXCLUDED.quantity, health = EXCLUDED.health
        ');

        $stmt->execute([
            ':tankId' => $tankId,
            ':speciesId' => $speciesId,
            ':quantity' => $quantity,
            ':health' => $health
        ]);
    }
}