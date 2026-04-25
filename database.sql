CREATE DATABASE IF NOT EXISTS gaiacity_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gaiacity_db;

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('admin','officer','citizen') NOT NULL DEFAULT 'citizen',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS laporan_lingkungan (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT           NOT NULL,
    judul       VARCHAR(200)  NOT NULL,
    kategori    ENUM('Polusi Udara','Sampah Liar','Banjir','Kerusakan Pohon','Lainnya') NOT NULL,
    deskripsi   TEXT          NOT NULL,
    lokasi      VARCHAR(255)  NOT NULL,
    foto        VARCHAR(255)  DEFAULT NULL,
    status      ENUM('Pending','Diproses','Selesai') NOT NULL DEFAULT 'Pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO users (name, email, password, role) VALUES
  ('Admin GaiaCity',  'admin@gaiacity.id',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
  ('Budi Santoso',    'officer@gaiacity.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'officer'),
  ('Warga Demo',      'citizen@gaiacity.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citizen');
-- Password semua: Password123