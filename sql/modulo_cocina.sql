ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS numero_pedido VARCHAR(30) NULL AFTER id_pedido,
    ADD COLUMN IF NOT EXISTS direccion_entrega VARCHAR(180) NULL AFTER mesa;

ALTER TABLE detalle_pedido
    ADD COLUMN IF NOT EXISTS observaciones TEXT NULL AFTER subtotal;

CREATE INDEX IF NOT EXISTS idx_pedidos_cocina_estado_hora
    ON pedidos (estado, fecha_hora_inicio);

CREATE INDEX IF NOT EXISTS idx_detalle_pedido_id
    ON detalle_pedido (id_pedido);
