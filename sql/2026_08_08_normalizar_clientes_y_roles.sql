-- GenesisBar 1.0 - normalizacion de clientes y roles internos
-- Aplicar manualmente solo si la instalacion conserva la tabla clientes antigua con columna id.

ALTER TABLE clientes
    CHANGE COLUMN id id_cliente INT AUTO_INCREMENT;

ALTER TABLE clientes
    ADD COLUMN IF NOT EXISTS apellido VARCHAR(100) NOT NULL DEFAULT '' AFTER nombre,
    ADD COLUMN IF NOT EXISTS password VARCHAR(255) NULL AFTER email,
    ADD COLUMN IF NOT EXISTS fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER direccion,
    ADD COLUMN IF NOT EXISTS puntos INT NOT NULL DEFAULT 0 AFTER fecha_registro,
    ADD COLUMN IF NOT EXISTS puntos_mes INT NOT NULL DEFAULT 0 AFTER puntos,
    ADD COLUMN IF NOT EXISTS observaciones TEXT NULL AFTER puntos_mes,
    ADD COLUMN IF NOT EXISTS estado VARCHAR(20) NOT NULL DEFAULT 'Activo' AFTER observaciones,
    ADD COLUMN IF NOT EXISTS ultimo_acceso DATETIME NULL AFTER estado;

ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('Administrador','Mozo','Cocina','Cajero') NOT NULL DEFAULT 'Mozo';

INSERT INTO usuarios (nombre, usuario, password, rol, activo)
SELECT 'Cocina', 'cocina', '$2y$10$dAT9TWhCwdxkwpMyiKWki..xDF/mJHvnkYikz511WaZ30BzvkvSZ6', 'Cocina', 1
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'cocina');

INSERT INTO usuarios (nombre, usuario, password, rol, activo)
SELECT 'Cajero', 'cajero', '$2y$10$dAT9TWhCwdxkwpMyiKWki..xDF/mJHvnkYikz511WaZ30BzvkvSZ6', 'Cajero', 1
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'cajero');
