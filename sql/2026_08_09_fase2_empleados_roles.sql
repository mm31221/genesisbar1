-- GenesisBar 1.0 - Fase 2: empleados, roles y permisos
-- Migracion no destructiva: agrega auditoria basica y asegura usuario Mozo inicial.

CREATE TABLE IF NOT EXISTS roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(40) NOT NULL,
    UNIQUE KEY roles_nombre_unico (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO roles (nombre) VALUES
    ('Administrador'),
    ('Mozo'),
    ('Cocina'),
    ('Cajero');

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER activo,
    ADD COLUMN IF NOT EXISTS ultimo_acceso DATETIME NULL AFTER fecha_creacion;

ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('Administrador','Mozo','Cocina','Cajero') NULL DEFAULT 'Mozo';

INSERT INTO usuarios (nombre, usuario, password, rol, activo)
SELECT 'Mozo', 'mozo', password, 'Mozo', 1
FROM usuarios
WHERE usuario = 'admin'
AND NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'mozo')
LIMIT 1;

CREATE INDEX IF NOT EXISTS idx_usuarios_rol_activo
    ON usuarios (rol, activo);
