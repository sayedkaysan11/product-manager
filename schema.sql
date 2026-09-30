-- Jalankan file ini di phpMyAdmin (tab SQL) atau lewat terminal mysql
-- sebelum aplikasi pertama kali dijalankan.

CREATE DATABASE IF NOT EXISTS product_manager
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE product_manager;

CREATE TABLE IF NOT EXISTS products (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)   NOT NULL UNIQUE,
    category   VARCHAR(50)    NOT NULL,
    price      DECIMAL(12,2)  NOT NULL,
    stock      INT UNSIGNED   NOT NULL,
    created_at TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Contoh data awal (boleh dihapus)
INSERT INTO products (name, category, price, stock) VALUES
('Kopi Arabica 250g', 'Minuman', 85000, 12),
('Keyboard Mekanik TKL', 'Elektronik', 550000, 4),
('Buku Tulis 58 Lembar', 'Alat Tulis', 7500, 150);
