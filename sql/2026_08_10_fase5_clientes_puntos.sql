-- GenesisBar 1.0 - Fase 5: clientes y puntos
-- Migracion no destructiva: asegura indices y regla comercial visible.

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

CREATE INDEX IF NOT EXISTS idx_clientes_estado_nombre
    ON clientes (estado, nombre, apellido);

CREATE INDEX IF NOT EXISTS idx_clientes_telefono
    ON clientes (telefono);

CREATE INDEX IF NOT EXISTS idx_pedidos_cliente_fecha
    ON pedidos (id_cliente, fecha_hora_inicio);

INSERT INTO configuraciones (clave, valor, descripcion)
VALUES ('puntos_por_pesos', '100', 'Regla vigente: 1 punto cada 100 pesos cobrados')
ON DUPLICATE KEY UPDATE
    valor = VALUES(valor),
    descripcion = VALUES(descripcion);
