-- GenesisBar 1.0 - ajustes login clientes/empleados, cocina, pagos mixtos y horario de entrega
-- Aplicar despues de las migraciones de Fase 8. No borra pedidos ni clientes.

ALTER TABLE clientes
    MODIFY COLUMN email VARCHAR(180) NULL,
    MODIFY COLUMN password VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS email_verificado TINYINT(1) NOT NULL DEFAULT 0 AFTER email,
    ADD COLUMN IF NOT EXISTS token_verificacion VARCHAR(100) NULL AFTER password,
    ADD COLUMN IF NOT EXISTS token_recuperacion VARCHAR(100) NULL AFTER token_verificacion,
    ADD COLUMN IF NOT EXISTS token_recuperacion_expira DATETIME NULL AFTER token_recuperacion;

UPDATE clientes
SET email = NULL
WHERE email IS NOT NULL AND TRIM(email) = '';

UPDATE clientes c
INNER JOIN (
    SELECT email, MIN(id_cliente) AS id_cliente_conservar
    FROM clientes
    WHERE email IS NOT NULL AND email <> ''
    GROUP BY email
    HAVING COUNT(*) > 1
) duplicados ON duplicados.email = c.email
SET c.email = NULL
WHERE c.id_cliente <> duplicados.id_cliente_conservar;

CREATE UNIQUE INDEX IF NOT EXISTS uq_clientes_email
    ON clientes (email);

CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_usuario
    ON usuarios (usuario);

ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS horario_entrega DATETIME NULL AFTER direccion_entrega,
    MODIFY COLUMN estado ENUM('Pendiente','Preparando','Listo','Entregado','Cobrado','Cancelado') NULL DEFAULT 'Pendiente',
    MODIFY COLUMN estado_pago ENUM('Pendiente','Pagado','Anulado') NOT NULL DEFAULT 'Pendiente';

INSERT INTO formas_pago (nombre)
SELECT 'Efectivo'
WHERE NOT EXISTS (
    SELECT 1 FROM formas_pago WHERE LOWER(nombre) LIKE '%efectivo%'
);

INSERT INTO formas_pago (nombre)
SELECT 'Mercado Pago'
WHERE NOT EXISTS (
    SELECT 1 FROM formas_pago WHERE LOWER(nombre) LIKE '%mercado%'
);

DROP INDEX IF EXISTS uq_movimientos_caja_ingreso_pedido ON movimientos_caja;

CREATE INDEX IF NOT EXISTS idx_movimientos_caja_pedido_tipo
    ON movimientos_caja (id_pedido, tipo);

CREATE INDEX IF NOT EXISTS idx_pedidos_horario_entrega
    ON pedidos (horario_entrega);
