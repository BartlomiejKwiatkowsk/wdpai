<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Tank.php';
require_once __DIR__.'/../models/Log.php';
require_once __DIR__.'/../models/Equipment.php';
require_once __DIR__.'/../models/Species.php';
require_once __DIR__.'/../models/Livestock.php';

class TankRepository extends Repository {

    public function getTanks(string $userEmail): array {
        $result = [];

        $stmt = $this->database->connect()->prepare('
            SELECT v.* FROM public.v_dashboard_summary v
            JOIN public.users u ON v.id_user = u.id_user
            WHERE u.email = :email
        ');

        $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $stmt->execute();

        $tanks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tanks as $tank) {
            $result[] = new Tank(
                $tank['id_tank'],
                $tank['tank_name'],
                $tank['water_type'],
                $tank['volume_liters'],
                $tank['status'],
                $tank['total_livestock_count']
            );
        }

        return $result;
    }

    public function addTank(Tank $tank, string $userEmail): void {
        $conn = $this->database->connect();

        $conn->exec("SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $conn->beginTransaction();

        try {
            $stmt = $conn->prepare('SELECT id_user FROM public.users WHERE email = :email');
            $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("Błąd autoryzacji: Użytkownik nie istnieje.");
            }

            $stmt = $conn->prepare('
                INSERT INTO public.tanks (id_user, name, water_type, volume_liters, status)
                VALUES (?, ?, ?, ?, ?)
            ');

            $stmt->execute([
                $user['id_user'],
                $tank->getName(),
                $tank->getWaterType(),
                $tank->getVolume(),
                $tank->getStatus()
            ]);

            $conn->commit();

        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function getTankById(string $id, string $userEmail): ?Tank {
        $stmt = $this->database->connect()->prepare('
            SELECT v.* FROM public.v_dashboard_summary v
            JOIN public.users u ON v.id_user = u.id_user
            WHERE v.id_tank = :id AND u.email = :email
        ');

        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $stmt->execute();

        $tank = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tank) {
            return null;
        }

        return new Tank(
            $tank['id_tank'],
            $tank['tank_name'],
            $tank['water_type'],
            $tank['volume_liters'],
            $tank['status'],
            $tank['total_livestock_count']
        );
    }

    public function updateTank(string $id, string $name, string $waterType, int $volume, string $userEmail): void {
        $stmt = $this->database->connect()->prepare('
            UPDATE public.tanks 
            SET name = :name, water_type = :water_type, volume_liters = :volume
            WHERE id_tank = :id AND id_user = (SELECT id_user FROM public.users WHERE email = :email)
        ');

        $stmt->execute([
            ':name' => $name,
            ':water_type' => $waterType,
            ':volume' => $volume,
            ':id' => $id,
            ':email' => $userEmail
        ]);
    }

    public function addWaterLog(string $tankId, float $ph, float $temp, ?string $notes): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO public.water_logs (id_tank, ph_level, temperature, notes)
            VALUES (:tankId, :ph, :temp, :notes)
        ');
        $stmt->execute([
            ':tankId' => $tankId,
            ':ph' => $ph,
            ':temp' => $temp,
            ':notes' => $notes
        ]);
    }

    public function getLatestLog(string $tankId): ?Log {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM public.water_logs 
            WHERE id_tank = :tankId 
            ORDER BY logged_at DESC LIMIT 1
        ');
        $stmt->bindParam(':tankId', $tankId, PDO::PARAM_STR);
        $stmt->execute();
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$log) return null;

        return new Log($log['id_log'], $log['id_tank'], $log['ph_level'], $log['temperature'], $log['notes'], $log['logged_at']);
    }
    public function getEquipmentForTank(string $tankId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM public.installed_equipment WHERE id_tank = :tankId ORDER BY added_at DESC
        ');
        $stmt->bindParam(':tankId', $tankId, PDO::PARAM_STR);
        $stmt->execute();

        $equipmentList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];

        foreach ($equipmentList as $eq) {
            $result[] = new Equipment(
                $eq['id_equipment'],
                $eq['id_tank'],
                $eq['name'],
                $eq['type'],
                $eq['status']
            );
        }
        return $result;
    }

    public function addEquipment(string $tankId, string $name, string $type, string $status): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO public.installed_equipment (id_tank, name, type, status)
            VALUES (:tankId, :name, :type, :status)
        ');
        $stmt->execute([
            ':tankId' => $tankId,
            ':name' => $name,
            ':type' => $type,
            ':status' => $status
        ]);
    }

    // Zaktualizowana funkcja pobierająca pełne dane gatunków
    public function getAllSpecies(): array {
        $stmt = $this->database->connect()->prepare('SELECT * FROM public.species ORDER BY common_name ASC');
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[] = new Species(
                $row['id_species'],
                $row['common_name'],
                $row['scientific_name'],
                $row['water_compatibility'],
                $row['ideal_ph_min'],
                $row['ideal_ph_max'],
                $row['ideal_temp_min'],
                $row['ideal_temp_max'],
                $row['image_path']
            );
        }
        return $result;
    }

    public function getLivestockForTank(string $tankId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT tl.id_livestock, s.common_name, tl.quantity, tl.health 
            FROM public.tank_livestock tl
            JOIN public.species s ON tl.id_species = s.id_species
            WHERE tl.id_tank = :tankId
            ORDER BY tl.added_at DESC
        ');
        $stmt->bindParam(':tankId', $tankId, PDO::PARAM_STR);
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[] = new Livestock($row['id_livestock'], $row['common_name'], $row['quantity'], $row['health']);
        }
        return $result;
    }

    public function addLivestock(string $tankId, string $speciesId, int $quantity, string $health): void {
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

    public function deleteItem(string $id, string $type): bool {
        $conn = $this->database->connect();
        if ($type === 'equipment') {
            $stmt = $conn->prepare('DELETE FROM public.installed_equipment WHERE id_equipment = :id');
        } elseif ($type === 'livestock') {
            $stmt = $conn->prepare('DELETE FROM public.tank_livestock WHERE id_livestock = :id');
        } else {
            return false;
        }

        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        return $stmt->execute();
    }
}