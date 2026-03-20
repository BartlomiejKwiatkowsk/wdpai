-- =====================================================================================
-- AQUAMANAGER: GŁÓWNY SCHEMAT BAZY DANYCH (PostgreSQL)
-- Zgodność z 3 Postacią Normalną (3NF) - brak redundancji, pełna integralność referencyjna.
-- =====================================================================================

-- 1. ENUMERATORY (Słowniki na poziomie silnika bazy danych dla optymalizacji)
CREATE TYPE water_type_enum AS ENUM ('Freshwater', 'Saltwater');
CREATE TYPE tank_status_enum AS ENUM ('Healthy', 'Attention', 'Empty', 'Quarantine');
CREATE TYPE health_status_enum AS ENUM ('Excellent', 'Good', 'Monitor', 'Critical');

-- 2. RELACJA JEDEN-DO-JEDNEGO (1:1)
-- Spełnienie wymogu: Rozszerzenie danych użytkownika bez obciążania tabeli autoryzacyjnej
CREATE TABLE user_profiles (
                               id_user UUID PRIMARY KEY REFERENCES users(id_user) ON DELETE CASCADE,
                               full_name VARCHAR(100),
                               subscription_tier VARCHAR(50) DEFAULT 'Standard',
                               updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. RELACJA JEDEN-DO-WIELU (1:N)
-- Spełnienie wymogu: Jeden użytkownik może posiadać wiele zbiorników (Dashboard -> My Tanks)
CREATE TABLE tanks (
                       id_tank UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                       id_user UUID NOT NULL REFERENCES users(id_user) ON DELETE CASCADE,
                       name VARCHAR(100) NOT NULL,
                       water_type water_type_enum NOT NULL,
                       volume_liters INTEGER NOT NULL CHECK (volume_liters > 0),
                       width_cm INTEGER,
                       height_cm INTEGER,
                       depth_cm INTEGER,
                       status tank_status_enum DEFAULT 'Empty',
                       installation_date DATE,
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. TABELA SŁOWNIKOWA (Katalog Gatunków - Species Catalog)
CREATE TABLE species (
                         id_species UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                         common_name VARCHAR(100) NOT NULL,
                         scientific_name VARCHAR(150) UNIQUE NOT NULL,
                         water_compatibility water_type_enum NOT NULL,
                         ideal_ph_min NUMERIC(3,1),
                         ideal_ph_max NUMERIC(3,1),
                         ideal_temp_min NUMERIC(4,1), -- w stopniach Celsjusza
                         ideal_temp_max NUMERIC(4,1)
);

-- 5. RELACJA WIELE-DO-WIELU (N:M)
-- Spełnienie wymogu: Wiele zbiorników może zawierać wiele gatunków (Livestock wewnątrz Tank)
CREATE TABLE tank_livestock (
                                id_livestock UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                                id_tank UUID NOT NULL REFERENCES tanks(id_tank) ON DELETE CASCADE,
                                id_species UUID NOT NULL REFERENCES species(id_species) ON DELETE RESTRICT,
                                quantity INTEGER NOT NULL CHECK (quantity > 0),
                                health health_status_enum DEFAULT 'Good',
                                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Zabezpieczenie przed anomaliami
                                UNIQUE(id_tank, id_species)
);

-- =====================================================================================
-- WIDOKI (VIEWS) - Wymóg: Minimum 2 widoki z użyciem JOIN
-- =====================================================================================

-- WIDOK 1: Agregacja danych na główny Dashboard użytkownika
CREATE VIEW v_dashboard_summary AS
SELECT
    t.id_user,
    t.id_tank,
    t.name AS tank_name,
    t.water_type,
    t.volume_liters,
    t.status,
    COALESCE(SUM(tl.quantity), 0) AS total_livestock_count
FROM tanks t
         LEFT JOIN tank_livestock tl ON t.id_tank = tl.id_tank
GROUP BY t.id_user, t.id_tank, t.name, t.water_type, t.volume_liters, t.status;

-- WIDOK 2: Szczegółowy raport inwentarza dla konkretnego zbiornika
CREATE VIEW v_tank_ecosystem_details AS
SELECT
    t.id_tank,
    s.common_name,
    s.scientific_name,
    tl.quantity,
    tl.health,
    s.ideal_ph_min,
    s.ideal_ph_max
FROM tank_livestock tl
         JOIN tanks t ON tl.id_tank = t.id_tank
         JOIN species s ON tl.id_species = s.id_species;

-- =====================================================================================
-- FUNKCJE I WYZWALACZE (TRIGGERS) - Wymóg: Minimum 1 funkcja, Minimum 1 wyzwalacz
-- =====================================================================================

-- FUNKCJA: Walidacja zgodności ekosystemu
-- Sprawdza, czy użytkownik nie próbuje dodać ryby słodkowodnej do morskiego akwarium.
CREATE OR REPLACE FUNCTION fn_validate_water_compatibility()
RETURNS TRIGGER AS $$
DECLARE
v_tank_water_type water_type_enum;
    v_species_water_type water_type_enum;
BEGIN
    -- Pobierz typ wody zbiornika
SELECT water_type INTO v_tank_water_type FROM tanks WHERE id_tank = NEW.id_tank;

-- Pobierz kompatybilność gatunku
SELECT water_compatibility INTO v_species_water_type FROM species WHERE id_species = NEW.id_species;

-- Wykonaj walidację biznesową
IF v_tank_water_type != v_species_water_type THEN
        RAISE EXCEPTION 'Niezgodność ekosystemu: Próba dodania gatunku % do zbiornika %!', v_species_water_type, v_tank_water_type;
END IF;

RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- WYZWALACZ: Automatyczne odpalenie walidacji przed każdym INSERT/UPDATE na tabeli inwentarza
CREATE TRIGGER trg_check_livestock_compatibility
    BEFORE INSERT OR UPDATE ON tank_livestock
                         FOR EACH ROW
                         EXECUTE FUNCTION fn_validate_water_compatibility();

-- =====================================================================================
-- DANE TESTOWE (Aby aplikacja nie była pusta na prezentacji)
-- =====================================================================================
INSERT INTO species (common_name, scientific_name, water_compatibility, ideal_ph_min, ideal_ph_max, ideal_temp_min, ideal_temp_max)
VALUES
    ('Neon Tetra', 'Paracheirodon innesi', 'Freshwater', 6.0, 7.0, 21.0, 27.0),
    ('Ocellaris Clownfish', 'Amphiprion ocellaris', 'Saltwater', 8.0, 8.4, 23.0, 28.0),
    ('Yellow Tang', 'Zebrasoma flavescens', 'Saltwater', 8.1, 8.4, 24.0, 28.0);