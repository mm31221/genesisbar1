# GenesisBar 1.0

Sistema modular para gestion gastronomica en XAMPP con PHP, MySQL, HTML, CSS y JavaScript. La arquitectura activa de esta entrega es GenesisBar 1.0, organizada por modulos y sin dependencia de las paginas legacy reemplazadas.

## Arquitectura

- `config/`: configuracion central, rutas, zona horaria y nombre de base de datos.
- `includes/`: encabezado, navegacion y pie compartidos.
- `php/`: helpers compartidos de conexion, seguridad, permisos, estados, puntos e imagenes.
- `empleados/`: login unico, cierre de sesion, permisos por rol y administracion de usuarios.
- `pedidos/`: panel operativo, nuevo pedido, guardado, seguimiento y cambios de estado.
- `cocina/`: visualizacion compacta de comandas activas.
- `caja/`: pedidos para cobrar, confirmacion de pago, ticket e historial.
- `clientes/`: portal cliente, registro, login, perfil, pedidos online y administracion.
- `productos/`: ABM de productos con imagenes.
- `estadisticas/`: indicadores y graficos con datos reales.
- `ajax/`: endpoints compartidos de categorias y productos.
- `assets/img/`: imagenes del sistema, productos, logo y fallback.
- `sql/`: migraciones incrementales por fase.

## Instalacion en XAMPP

1. Copiar el proyecto en `C:\xampp\htdocs\genesisbar1`.
2. Iniciar Apache y MySQL desde XAMPP.
3. Crear la base de datos `genesisbar1` con charset `utf8mb4`.
4. Importar las migraciones SQL en este orden:
   - `sql/2026_08_08_normalizar_clientes_y_roles.sql`
   - `sql/2026_08_09_fase1_base_tecnica.sql`
   - `sql/2026_08_09_fase2_empleados_roles.sql`
   - `sql/2026_08_09_fase3_flujo_pedidos.sql`
   - `sql/2026_08_10_fase4_caja_pagos.sql`
   - `sql/2026_08_10_fase5_clientes_puntos.sql`
   - `sql/2026_08_10_fase6_estadisticas.sql`
   - `sql/2026_08_10_fase7_imagenes.sql`
5. Abrir `http://localhost/genesisbar1/`.

## Usuarios de prueba locales

En la base local validada durante la Fase 8:

| Rol | Usuario | Contrasena |
| --- | --- | --- |
| Administrador | `admin` | `admin` |
| Mozo | `mozo` | `admin` |
| Cocina | `cocina` | `admin` |
| Cajero | `cajero` | `admin` |

Para uso real, cambiar estas contrasenas desde `Empleados` antes de operar.

## Flujo principal

1. Un empleado inicia sesion desde `empleados/login.php`.
2. El servidor valida el rol real de la base de datos.
3. Mozo crea un pedido desde `pedidos/nuevo.php`.
4. El pedido queda `Pendiente` y aparece en Cocina.
5. El estado operativo avanza por `Pendiente`, `Preparando`, `Listo` y `Entregado`.
6. Caja confirma el pago y el pedido queda `Cobrado` con `estado_pago = Pagado`.
7. Si el pedido tiene cliente asociado, se acreditan puntos una sola vez.
8. Caja, clientes y estadisticas consumen esos datos reales.

## Sistema de puntos

La regla comercial activa es:

- `1 punto cada $100`.

El valor esta centralizado en `config/config.php` mediante `PUNTOS_POR_PESOS`.

Los movimientos se guardan en `puntos_movimientos` con historial auditable. No se depende de borrar puntos mensuales para calcular estadisticas.

## Imagenes

Las imagenes se guardan como archivos dentro de `assets/img/` y MySQL solo almacena la ruta. El helper `php/imagenes.php` valida errores de subida, tamano maximo, MIME y extensiones permitidas (`jpg`, `png`, `webp`), genera nombres seguros y usa una imagen fallback cuando un producto no tiene foto.

Nota: esta instalacion local de PHP no tiene GD/Imagick activo, por lo que la optimizacion automatica de dimensiones queda pendiente de activar esa extension o sumar una herramienta equivalente.

## Progreso por fases

- Fase 1: base tecnica, configuracion, conexion, sesiones y limpieza legacy.
- Fase 2: empleados, login unico, roles y permisos.
- Fase 3: pedidos, mozo, cocina y estados operativos.
- Fase 4: caja, pagos, ticket, historial y bloqueo de cobro duplicado.
- Fase 5: clientes, portal, administracion y puntos.
- Fase 6: estadisticas numericas y graficas.
- Fase 7: imagenes de productos y ajustes visuales.
- Fase 8: pruebas, documentacion y listas de verificacion.

Para ver el detalle organizado de los cambios entre el commit inicial y la version actual, consultar `CHANGELOG.md`.

## Pruebas realizadas en Fase 8

- Sintaxis PHP valida en todos los archivos del proyecto.
- Login validado para `admin`, `mozo`, `cocina` y `cajero`.
- Permisos revisados: roles no autorizados fueron redirigidos a acceso denegado.
- Flujo completo probado con datos temporales: pedido, estados, cobro, caja y puntos.
- Segundo cobro probado sin duplicar movimientos de caja ni puntos.
- Limpieza comprobada: no quedaron pedidos, clientes, movimientos de caja ni puntos temporales.
- Portal cliente y estadisticas responden HTTP 200.
- Revision visual de escritorio: sin errores PHP visibles, sin imagenes rotas y sin desborde horizontal en pantallas principales.

## Pendientes recomendados para uso real

- Cambiar todas las contrasenas de prueba.
- Cargar imagenes reales para bebidas y tragos que todavia usan fallback.
- Activar GD/Imagick o definir una herramienta de optimizacion para reducir imagenes automaticamente.
- Probar en telefonos reales del local, especialmente el panel de nuevo pedido del mozo.
- Definir politica comercial completa de canje de puntos y premios.
