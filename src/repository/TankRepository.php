<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Tank.php';

class TankRepository extends Repository {

    // Pobiera wszystkie zbiorniki dla konkretnego użytkownika bazując na jego emailu z sesji
    public function getTanks(string $userEmail): array {
        $result = [];

        // Używamy  widoku v_dashboard_summary
        // Łączymy z tabelą users, żeby filtrować po emailu (bo w sesji mamy email, a nie id_user)
        $stmt = $this->database->connect()->prepare('
            SELECT v.* 
            FROM public.v_dashboard_summary v
            JOIN public.users u ON v.id_user = u.id_user
            WHERE u.email = :email
        ');

        $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $stmt->execute();

        $tanks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mapujemy surowe dane z bazy na ładne obiekty PHP
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
}