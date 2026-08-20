-- GenesisBar 1.0 - reformas de pedidos, direcciones y caja
-- Migracion no destructiva: conserva direcciones y movimientos existentes.

ALTER TABLE clientes
    ADD COLUMN IF NOT EXISTS direccion_calle VARCHAR(120) NULL AFTER direccion,
    ADD COLUMN IF NOT EXISTS direccion_altura VARCHAR(20) NULL AFTER direccion_calle;

ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS direccion_calle VARCHAR(120) NULL AFTER direccion_entrega,
    ADD COLUMN IF NOT EXISTS direccion_altura VARCHAR(20) NULL AFTER direccion_calle;

UPDATE clientes
SET direccion_calle = direccion
WHERE direccion_calle IS NULL
  AND direccion IS NOT NULL
  AND TRIM(direccion) <> '';

UPDATE pedidos
SET direccion_calle = direccion_entrega
WHERE direccion_calle IS NULL
  AND direccion_entrega IS NOT NULL
  AND TRIM(direccion_entrega) <> '';

ALTER TABLE movimientos_caja
    MODIFY COLUMN tipo ENUM('Ingreso','Egreso','Apertura','Cierre') NOT NULL DEFAULT 'Ingreso',
    ADD COLUMN IF NOT EXISTS dinero_recibido DECIMAL(10,2) NULL AFTER monto,
    ADD COLUMN IF NOT EXISTS vuelto DECIMAL(10,2) NULL AFTER dinero_recibido;

DROP INDEX IF EXISTS uq_movimientos_caja_ingreso_pedido ON movimientos_caja;

CREATE INDEX IF NOT EXISTS idx_movimientos_caja_pedido_tipo
    ON movimientos_caja (id_pedido, tipo);

CREATE INDEX IF NOT EXISTS idx_movimientos_caja_tipo_fecha
    ON movimientos_caja (tipo, fecha_hora);

CREATE INDEX IF NOT EXISTS idx_pedidos_direccion
    ON pedidos (direccion_calle, direccion_altura);
