-- GenesisBar 1.0 - Fase 6: estadisticas
-- Migracion no destructiva: indices para filtros y graficos por fecha.

CREATE INDEX IF NOT EXISTS idx_pedidos_estado_pago_cobro_tipo
    ON pedidos (estado, estado_pago, fecha_hora_cobro, tipo_pedido);

CREATE INDEX IF NOT EXISTS idx_detalle_pedido_producto
    ON detalle_pedido (id_producto, id_pedido);

CREATE INDEX IF NOT EXISTS idx_productos_categoria
    ON productos (id_categoria, activo);

CREATE INDEX IF NOT EXISTS idx_puntos_fecha_tipo
    ON puntos_movimientos (fecha, tipo);
