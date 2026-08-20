-- GenesisBar 1.0 - Fase 7: imagenes y experiencia visual
-- Migracion no destructiva: agrega rutas de imagen para productos.

ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS imagen VARCHAR(255) NULL AFTER descripcion;

UPDATE productos
SET imagen = CASE
    WHEN LOWER(nombre) LIKE '%premium%' THEN 'assets/img/productos/sushi-12-premium.jpeg'
    WHEN LOWER(nombre) LIKE '%clasica%' THEN 'assets/img/productos/sushi-12-clasicas.jpeg'
    WHEN LOWER(nombre) LIKE '%jamon%' THEN 'assets/img/productos/empanadas-1.jpeg'
    WHEN LOWER(nombre) LIKE '%pizza%' OR LOWER(nombre) LIKE '%napolitana%' OR LOWER(nombre) LIKE '%primavera%' THEN 'assets/img/productos/pizza-veg.jpeg'
    ELSE imagen
END
WHERE imagen IS NULL OR imagen = '';
