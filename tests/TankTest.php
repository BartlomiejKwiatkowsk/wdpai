<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/models/Tank.php';

class TankTest extends TestCase {

    public function testTankCreationAndGetters() {
        // Przygotowanie danych testowych
        $tankId = '550e8400-e29b-41d4-a716-446655440000';
        $tank = new Tank(
            $tankId,
            'Rafowe 1',
            'Saltwater',
            500,
            'Healthy',
            15,
            '/img/test.png',
            'Testowa notatka'
        );

        // Weryfikacja (Asercje)
        $this->assertEquals($tankId, $tank->getId(), "Getter ID nie zwraca poprawnej wartości");
        $this->assertEquals('Rafowe 1', $tank->getName(), "Getter Name nie zwraca poprawnej wartości");
        $this->assertEquals('Saltwater', $tank->getWaterType(), "Getter WaterType nie działa prawidłowo");
        $this->assertEquals(500, $tank->getVolume(), "Błąd w getterze objętości");
        $this->assertEquals('Healthy', $tank->getStatus(), "Błąd w statusie akwarium");
        $this->assertEquals(15, $tank->getLivestockCount(), "Błąd przeliczania obsady");
        $this->assertEquals('/img/test.png', $tank->getImagePath(), "Błąd ścieżki do pliku");
        $this->assertEquals('Testowa notatka', $tank->getNotes(), "Getter notatek zawiódł");
    }
}