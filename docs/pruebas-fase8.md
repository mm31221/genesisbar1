# Pruebas Fase 8

Fecha: 2026-08-10

## Entorno

- Windows con XAMPP.
- URL local: `http://localhost/genesisbar1/`.
- Base de datos: `genesisbar1`.
- PHP usado para validacion: `C:\xampp\php\php.exe`.

## Resultados

| Prueba | Resultado |
| --- | --- |
| Sintaxis PHP de todo el proyecto | OK |
| Login Administrador | OK |
| Login Mozo | OK |
| Login Cocina | OK |
| Login Cajero | OK |
| Bloqueo de Mozo en Productos | OK |
| Bloqueo de Cocina en Caja | OK |
| Bloqueo de Cajero en Empleados | OK |
| Portal cliente HTTP 200 | OK |
| Estadisticas HTTP 200 | OK |
| Endpoint productos con `imagen_url` | OK |
| Pedido temporal creado desde Mozo | OK |
| Estados Pendiente, Preparando, Listo, Entregado | OK |
| Cobro desde Caja | OK |
| Movimiento de caja generado | OK |
| Movimiento de puntos generado | OK |
| Segundo cobro sin duplicar caja/puntos | OK |
| Limpieza de datos temporales | OK |
| Revision visual de escritorio sin imagenes rotas | OK |
| Revision visual de escritorio sin desborde horizontal | OK |

## Flujo completo probado

1. Login como `mozo`.
2. Creacion de pedido temporal tipo `Take Away`.
3. Agregado de producto activo.
4. Cambio de estados: `Preparando`, `Listo`, `Entregado`.
5. Login como `cajero`.
6. Cobro en efectivo.
7. Verificacion de `estado = Cobrado` y `estado_pago = Pagado`.
8. Verificacion de 1 movimiento de caja y 1 movimiento de puntos.
9. Reintento de cobro sin duplicar registros.
10. Eliminacion del pedido, detalle, cliente, caja y puntos temporales.

## Observaciones

- Administrador tiene acceso global por diseno.
- La optimizacion automatica de imagenes queda pendiente porque GD/Imagick no esta activo en esta instalacion local.
- La prueba movil final debe hacerse en dispositivos reales del local.
