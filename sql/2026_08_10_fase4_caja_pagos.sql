-- GenesisBar 1.0 - Fase 4: caja y pagos
-- Migracion no destructiva: refuerza cobro unico, trazabilidad y filtros de historial.

ALTER TABLE movimientos_caja
    ADD COLUMN IF NOT EXISTS dinero_recibido DECIMAL(10,2) NULL AFTER monto,
    ADD COLUMN IF NOT EXISTS vuelto DECIMAL(10,2) NULL AFTER dinero_recibido;

CREATE UNIQUE INDEX IF NOT EXISTS uq_movimientos_caja_ingreso_pedido
    ON movimientos_caja (id_pedido, tipo);

CREATE INDEX IF NOT EXISTS idx_movimientos_caja_forma_fecha
    ON movimientos_caja (id_forma_pago, fecha_hora);

CREATE INDEX IF NOT EXISTS idx_movimientos_caja_usuario_fecha
    ON movimientos_caja (id_usuario, fecha_hora);

CREATE INDEX IF NOT EXISTS idx_pedidos_usuario_cobro_fecha
    ON pedidos (id_usuario_cobro, fecha_hora_cobro);
