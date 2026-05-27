<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Tank.php';

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
}