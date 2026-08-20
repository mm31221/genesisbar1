-- GenesisBar 1.0 - Fase 3: flujo operativo de pedidos
-- Migracion no destructiva: asegura los estados usados por Mozo, Cocina y Caja.

ALTER TABLE pedidos
    MODIFY COLUMN estado ENUM('Pendiente','Preparando','Listo','Entregado','Cobrado','Cancelado') NULL DEFAULT 'Pendiente',
    MODIFY COLUMN estado_pago ENUM('Pendiente','Pagado','Anulado') NOT NULL DEFAULT 'Pendiente';
