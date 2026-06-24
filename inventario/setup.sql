-- ============================================================
--  INVENTARIO MUNICIPAL - Script de base de datos
--  Ejecutar en phpMyAdmin o en la terminal de MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS inventario_municipal
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inventario_municipal;

-- Tabla de áreas / departamentos
CREATE TABLE IF NOT EXISTS areas (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(100) NOT NULL,
  token       VARCHAR(50)  NOT NULL UNIQUE,
  creado_en   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Áreas de ejemplo — cambia los tokens en producción
INSERT INTO areas (nombre, token) VALUES
  ('Desarrollo Social',  'DSR2024'),
  ('Obras Públicas',     'OBR2024'),
  ('Salud',              'SAL2024'),
  ('Educación',          'EDU2024'),
  ('Tesorería',          'TES2024'),
  ('Administración',     'ADM2024');

-- Tabla principal de bienes
CREATE TABLE IF NOT EXISTS bienes (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  area_id               INT NOT NULL,
  no_etiqueta           VARCHAR(100) NOT NULL,
  caracteristicas       TEXT,
  marca                 VARCHAR(100),
  modelo                VARCHAR(100),
  numero_serie          VARCHAR(150),
  estado_uso            ENUM('Bueno','Regular','Malo') NOT NULL DEFAULT 'Bueno',
  costo                 DECIMAL(12,2),
  observaciones         TEXT,
  observaciones_adicionales TEXT,
  observaciones_encargado   TEXT,
  foto_inmueble         VARCHAR(255),
  foto_etiqueta         VARCHAR(255),
  creado_en             DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE CASCADE
);

-- Índices para búsqueda rápida
CREATE INDEX idx_area     ON bienes (area_id);
CREATE INDEX idx_estado   ON bienes (estado_uso);
CREATE INDEX idx_etiqueta ON bienes (no_etiqueta);
