CREATE DATABASE IF NOT EXISTS viagem_parcely
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE viagem_parcely;

CREATE TABLE parcels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gml_id VARCHAR(64) NOT NULL,
    ku_code VARCHAR(10) NOT NULL,
    parcel_number VARCHAR(30) NOT NULL,
    national_reference VARCHAR(40) NOT NULL,
    area_m2 INT UNSIGNED NULL,
    geom_full GEOMETRY NOT NULL,
    geom_simplified GEOMETRY NOT NULL,
    fetched_at DATETIME NOT NULL,
    UNIQUE KEY uq_gml_id (gml_id),
    KEY idx_ku_code (ku_code),
    SPATIAL INDEX idx_geom_full (geom_full),
    SPATIAL INDEX idx_geom_simplified (geom_simplified)
) ENGINE=InnoDB;

CREATE TABLE ku_cache_status (
    ku_code VARCHAR(10) PRIMARY KEY,
    ku_name VARCHAR(100) NOT NULL,
    last_synced_at DATETIME NULL,
    parcel_count INT UNSIGNED NULL
) ENGINE=InnoDB;
