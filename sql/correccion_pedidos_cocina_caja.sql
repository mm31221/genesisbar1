ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS numero_pedido VARCHAR(30) NULL AFTER id_pedido,
    ADD COLUMN IF NOT EXISTS origen VARCHAR(30) NOT NULL DEFAULT 'Pagina web' AFTER numero_pedido,
    ADD COLUMN IF NOT EXISTS direccion_entrega VARCHAR(180) NULL AFTER mesa,
    ADD COLUMN IF NOT EXISTS descuento DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total,
    ADD COLUMN IF NOT EXISTS recargo DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER descuento,
    ADD COLUMN IF NOT EXISTS total_final DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER recargo,
    ADD COLUMN IF NOT EXISTS fecha_hora_cobro DATETIME NULL AFTER fecha_hora_entrega,
    ADD COLUMN IF NOT EXISTS id_usuario_cobro INT NULL AFTER id_usuario;

ALTER TABLE detalle_pedido
    ADD COLUMN IF NOT EXISTS observaciones TEXT NULL AFTER subtotal;

INSERT INTO categorias (nombre)
SELECT 'Pizza'
WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nombre = 'Pizza');

INSERT INTO categorias (nombre)
SELECT 'Sushi'
WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nombre = 'Sushi');

INSERT INTO categorias (nombre)
SELECT 'Empanadas'
WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nombre = 'Empanadas');

INSERT INTO categorias (nombre)
SELECT 'Bebidas'
WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nombre = 'Bebidas');

INSERT INTO categorias (nombre)
SELECT 'Tragos'
WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nombre = 'Tragos');

UPDATE categorias SET nombre = 'Pizza' WHERE nombre = 'Pizzas';

UPDATE productos
SET id_categoria = (SELECT id_categoria FROM categorias WHERE nombre = 'Sushi' LIMIT 1)
WHERE LOWER(nombre) LIKE '%combinado%';

UPDATE productos
SET id_categoria = (SELECT id_categoria FROM categorias WHERE nombre = 'Bebidas' LIMIT 1)
WHERE id_categoria IN (SELECT id_categoria FROM categorias WHERE nombre = 'Cervezas');

DELETE categorias
FROM categorias
LEFT JOIN productos ON productos.id_categoria = categorias.id_categoria
WHERE categorias.nombre NOT IN ('Pizza', 'Sushi', 'Empanadas', 'Bebidas', 'Tragos')
AND productos.id_producto IS NULL;

CREATE TABLE IF NOT EXISTS movimientos_caja (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NULL,
    tipo ENUM('Ingreso','Egreso','Apertura','Cierre') NOT NULL DEFAULT 'Ingreso',
    concepto VARCHAR(150) NOT NULL,
    monto DECIMAL(10,2) NOT NULL DEFAULT 0,
    id_forma_pago INT NULL,
    id_usuario INT NULL,
    fecha_hora DATETIME NOT NULL,
    observaciones TEXT NULL,
    KEY movimientos_pedido (id_pedido),
    KEY movimientos_fecha (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX IF NOT EXISTS idx_pedidos_estado_fecha
    ON pedidos (estado, fecha_hora_inicio);

CREATE INDEX IF NOT EXISTS idx_detalle_pedido_id
    ON detalle_pedido (id_pedido);
