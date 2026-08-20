-- GenesisBar 1.0 - reservas, pedidos programados y cobro anticipado
-- Migracion no destructiva.

ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS tipo_reserva ENUM('Ninguna','Mesa','Pedido') NOT NULL DEFAULT 'Ninguna' AFTER horario_entrega;

CREATE INDEX IF NOT EXISTS idx_pedidos_reserva_horario
    ON pedidos (tipo_reserva, horario_entrega);
