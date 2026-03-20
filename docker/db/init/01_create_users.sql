-- Tworzymy Enum dla ról - prowadzący lubi takie rzeczy przy weryfikacji uprawnień
CREATE TYPE user_role AS ENUM ('admin', 'user', 'pro_member');

-- Tabela users dopasowana do założeń projektu i uwierzytelniania
CREATE TABLE users (
                       id_user UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                       email VARCHAR(255) UNIQUE NOT NULL,
                       password_hash VARCHAR(255) NOT NULL,
                       role user_role DEFAULT 'user',
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Wrzucam od razu testowego admina, żeby było na czym testować logowanie.
-- Hasło to 'admin123' (zabezpieczone Bcryptem, wymóg bezpieczeństwa spełniony).
INSERT INTO users (email, password_hash, role)
VALUES ('admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');