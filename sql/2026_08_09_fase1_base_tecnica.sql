-- GenesisBar 1.0 - Fase 1: base tecnica y datos no destructivos
-- Aplicar despues de verificar backup. No borra tablas ni datos existentes.

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
    MODIFY COLUMN rol ENUM('Administrador','Mozo','Cocina','Cajero','Caja') NULL DEFAULT 'Mozo';

UPDATE usuarios
SET rol = 'Cajero'
WHERE rol = 'Caja';

ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('Administrador','Mozo','Cocina','Cajero') NULL DEFAULT 'Mozo';

INSERT INTO usuarios (nombre, usuario, password, rol, activo)
SELECT 'Cocina', 'cocina', '$2y$10$dAT9TWhCwdxkwpMyiKWki..xDF/mJHvnkYikz511WaZ30BzvkvSZ6', 'Cocina', 1
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'cocina');

INSERT INTO usuarios (nombre, usuario, password, rol, activo)
SELECT 'Cajero', 'cajero', '$2y$10$dAT9TWhCwdxkwpMyiKWki..xDF/mJHvnkYikz511WaZ30BzvkvSZ6', 'Cajero', 1
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'cajero');

ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS estado_pago ENUM('Pendiente','Pagado','Anulado') NOT NULL DEFAULT 'Pendiente' AFTER estado;

UPDATE pedidos
SET estado_pago = CASE
    WHEN estado = 'Cobrado' THEN 'Pagado'
    WHEN estado = 'Cancelado' THEN 'Anulado'
    ELSE 'Pendiente'
END;

CREATE TABLE IF NOT EXISTS puntos_movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_pedido INT NULL,
    tipo ENUM('acreditacion','canje','ajuste','anulacion') NOT NULL,
    puntos INT NOT NULL,
    descripcion VARCHAR(255) NULL,
    fecha DATETIME NOT NULL,
    id_usuario INT NULL,
    KEY idx_puntos_cliente_fecha (id_cliente, fecha),
    KEY idx_puntos_pedido (id_pedido),
    UNIQUE KEY uq_puntos_pedido_tipo (id_pedido, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS configuraciones (
    clave VARCHAR(80) PRIMARY KEY,
    valor VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NULL,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuraciones (clave, valor, descripcion)
VALUES ('puntos_por_pesos', '100', 'Regla vigente: 1 punto cada 100 pesos cobrados')
ON DUPLICATE KEY UPDATE
    valor = VALUES(valor),
    descripcion = VALUES(descripcion);

CREATE INDEX IF NOT EXISTS idx_pedidos_estado_pago_fecha
    ON pedidos (estado_pago, fecha_hora_cobro);
