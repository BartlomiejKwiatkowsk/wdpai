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
    public function addTank(Tank $tank, string $userEmail): void {
        $conn = $this->database->connect();

        // Spełnienie wymogu: transakcje na odpowiednim poziomie izolacji
        $conn->exec("SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $conn->beginTransaction();

        try {
            // Pobranie UUID użytkownika na podstawie sesyjnego adresu email
            $stmt = $conn->prepare('SELECT id_user FROM public.users WHERE email = :email');
            $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("Błąd autoryzacji: Użytkownik nie istnieje.");
            }

            // Insert nowego zbiornika
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

            // Zatwierdzenie transakcji
            $conn->commit();

        } catch (Exception $e) {
            // Wycofanie zmian w przypadku błędu (np. przerwanie połączenia)
            $conn->rollBack();
            throw $e;
        }
    }
}