# Historial de cambios - GenesisBar 1.0

Fecha de organizacion: 2026-08-20

Este documento resume los cambios realizados despues del commit inicial del repositorio. La idea es que GitHub no muestre el proyecto solo como un bloque grande de codigo, sino como una evolucion entendible por modulos y por etapas.

## Punto de partida

- Commit inicial en GitHub: `1bc1692 Initial commit`.
- Estado actual local: hay cambios sin commitear todavia sobre `main`.
- El proyecto paso de paginas sueltas y helpers legacy a una estructura modular para operacion gastronomica.

## Resumen general

GenesisBar se reorganizo como sistema modular para XAMPP con PHP, MySQL, HTML, CSS y JavaScript. La version actual separa las responsabilidades principales en modulos: empleados, pedidos, cocina, caja, clientes, productos, estadisticas, assets, helpers compartidos y migraciones SQL incrementales.

## Cambios por etapa

### 1. Base tecnica y configuracion

Archivos principales:

- `config/config.php`
- `php/conexion.php`
- `includes/header.php`
- `includes/footer.php`
- `index.php`
- `index.html`
- `css/estilos.css`

Cambios:

- Se centralizo la configuracion de rutas, base de datos, charset y zona horaria.
- Se definio `GENESIS_BASE_URL` para generar enlaces internos consistentes.
- Se actualizo la conexion a MySQL para trabajar con `utf8mb4`.
- Se agregaron encabezado, navegacion y pie compartidos.
- Se reemplazo el inicio simple por una pantalla de entrada a los modulos del sistema.
- Se amplio la hoja de estilos general con componentes reutilizables.

### 2. Seguridad, sesiones, roles y permisos

Archivos principales:

- `php/seguridad.php`
- `empleados/login.php`
- `empleados/logout.php`
- `empleados/index.php`
- `empleados/acceso-denegado.php`
- `sql/2026_08_09_fase2_empleados_roles.sql`

Cambios:

- Se agrego login unico para empleados.
- Se normalizaron roles: Administrador, Mozo, Cocina y Cajero.
- Se incorporaron permisos por modulo.
- Se agrego proteccion CSRF en formularios importantes.
- Se usan contrasenas hasheadas con `password_hash` y validacion con `password_verify`.
- Se agrego pantalla de acceso denegado para roles no autorizados.
- Se incorporo administracion de empleados para crear, editar, activar y desactivar usuarios.

### 3. Productos e imagenes

Archivos principales:

- `productos/index.php`
- `productos/agregar.php`
- `productos/editar.php`
- `productos/eliminar.php`
- `ajax/productos.php`
- `ajax/categorias.php`
- `php/imagenes.php`
- `assets/img/productos/`
- `assets/img/logo/`
- `sql/2026_08_10_fase7_imagenes.sql`

Cambios:

- Se reemplazo el menu hardcodeado por productos administrables desde base de datos.
- Se agrego ABM de productos con alta, edicion, baja logica y stock.
- Se incorporo carga de imagenes de productos.
- Se agrego imagen fallback para productos sin foto.
- Se validan MIME, extension y peso maximo de imagen.
- Los endpoints de productos ahora devuelven tambien `imagen_url`.

### 4. Pedidos y flujo de mozo

Archivos principales:

- `pedidos/index.php`
- `pedidos/nuevo.php`
- `pedidos/guardar.php`
- `pedidos/api_pedidos.php`
- `pedidos/actualizar_estado.php`
- `pedidos/actualizar_horario.php`
- `pedidos/ver.php`
- `pedidos/historial.php`
- `pedidos/pedidos.js`
- `pedidos/ver.js`
- `js/pedidos.js`
- `php/pedidos_estados.php`
- `sql/2026_08_09_fase3_flujo_pedidos.sql`
- `sql/2026_08_14_login_cocina_caja_horario.sql`
- `sql/2026_08_16_reformas_pedidos_caja.sql`
- `sql/2026_08_19_reservas_extras_caja.sql`

Cambios:

- Se paso de un pedido simple de un producto a pedidos con carrito y detalle.
- Se agregaron estados operativos: Pendiente, Preparando, Listo, Entregado, Cobrado y Cancelado.
- Se centralizo la validacion de cambios de estado.
- Se agrego numero de pedido visible.
- Se agregaron tipos de pedido: Mesa, Take Away y Delivery.
- Se incorporaron datos de cliente, telefono, direccion, mesa y observaciones.
- Se agrego soporte para horario de entrega.
- Se agregaron reservas de mesa y pedidos programados.
- Se protegieron las acciones con CSRF.
- Se agrego historial y vista detalle del pedido.

### 5. Cocina

Archivos principales:

- `cocina/index.php`
- `cocina/api_pedidos.php`
- `cocina/cocina.js`
- `css/cocina.css`
- `sql/modulo_cocina.sql`

Cambios:

- Se reemplazo la antigua pantalla simple de cocina por un modulo protegido por rol.
- Cocina ahora consume pedidos activos desde un endpoint dedicado.
- Se muestran comandas activas con estado, destino, mesa/cliente y tiempos.
- Se separo la logica visual en JavaScript y CSS propios.
- Se elimino el flujo legacy basado en `php/cocina.php`, `php/comandas.php` y `php/entregado.php`.

### 6. Caja, cobros y tickets

Archivos principales:

- `caja/index.php`
- `caja/cobrar.php`
- `caja/confirmar.php`
- `caja/historial.php`
- `caja/ticket.php`
- `caja/funciones.php`
- `caja/ajax/pedidos.php`
- `caja/js/caja.js`
- `caja/css/caja.css`
- `css/caja.css`
- `sql/2026_08_10_fase4_caja_pagos.sql`

Cambios:

- Se agrego modulo de caja protegido por rol Cajero.
- Se agrego listado de pedidos cobrables.
- Se implemento confirmacion de cobro con transacciones.
- Se evita cobrar dos veces el mismo pedido.
- Se agrego soporte para una o dos formas de pago en un mismo pedido.
- Se calcula efectivo recibido y vuelto.
- Se registran movimientos de caja.
- Se agrego historial de caja con filtros y resumen.
- Se agrego ticket imprimible con detalle de productos, pagos, horarios y puntos.
- Se agregaron funciones compartidas para calcular totales, pagos, pedidos cobrables y resumen de turno.

### 7. Clientes y puntos

Archivos principales:

- `clientes/index.php`
- `clientes/login.php`
- `clientes/registro.php`
- `clientes/logout.php`
- `clientes/perfil.php`
- `clientes/admin.php`
- `clientes/ajax/confirmar_pedido.php`
- `clientes/ajax/estado_activo.php`
- `php/clientes_auth.php`
- `php/puntos.php`
- `css/clientes.css`
- `sql/2026_08_10_fase5_clientes_puntos.sql`

Cambios:

- Se agrego portal de clientes.
- Se agrego registro y login de clientes.
- Se agrego perfil con historial de pedidos.
- Se agrego panel administrativo de clientes.
- Se implemento sistema de puntos.
- La regla activa es 1 punto cada $100, centralizada en `config/config.php`.
- Se registran movimientos de puntos para auditoria.
- Se permite acreditar, canjear, ajustar o anular puntos desde administracion.
- Se evita duplicar puntos cuando un pedido ya fue cobrado.

### 8. Estadisticas

Archivos principales:

- `estadisticas/index.php`
- `estadisticas/js/estadisticas.js`
- `estadisticas/css/estadisticas.css`
- `sql/2026_08_10_fase6_estadisticas.sql`

Cambios:

- Se agrego modulo de estadisticas protegido por permisos.
- Se calculan ventas, pedidos, formas de pago, productos destacados, clientes y puntos.
- Se agregaron filtros por fecha.
- Se agregaron graficos y tablas con datos reales de la base.
- Se crearon indices SQL para mejorar consultas por fecha, estado y producto.

### 9. Migraciones SQL

Archivos principales:

- `sql/2026_08_08_normalizar_clientes_y_roles.sql`
- `sql/2026_08_09_fase1_base_tecnica.sql`
- `sql/2026_08_09_fase2_empleados_roles.sql`
- `sql/2026_08_09_fase3_flujo_pedidos.sql`
- `sql/2026_08_10_fase4_caja_pagos.sql`
- `sql/2026_08_10_fase5_clientes_puntos.sql`
- `sql/2026_08_10_fase6_estadisticas.sql`
- `sql/2026_08_10_fase7_imagenes.sql`
- `sql/2026_08_14_login_cocina_caja_horario.sql`
- `sql/2026_08_16_reformas_pedidos_caja.sql`
- `sql/2026_08_19_reservas_extras_caja.sql`

Cambios:

- Se organizaron cambios de base de datos como migraciones incrementales.
- Las migraciones estan pensadas como no destructivas.
- Se agregaron tablas, columnas e indices para roles, pedidos, caja, puntos, imagenes, horarios y reservas.
- Se documenta el orden recomendado de importacion en el `README.md`.

### 10. Documentacion y pruebas

Archivos principales:

- `README.md`
- `CHANGELOG.md`
- `docs/pruebas-fase8.md`
- `docs/checklist-demo-escolar.md`
- `docs/checklist-uso-real.md`

Cambios:

- Se agrego documentacion de instalacion en XAMPP.
- Se documentaron modulos, usuarios de prueba y flujo principal.
- Se agrego checklist para demostracion escolar.
- Se agrego checklist para uso real.
- Se documento el resultado de pruebas de Fase 8.
- Se agrego este historial para explicar la evolucion del proyecto en GitHub.

## Archivos legacy eliminados o reemplazados

| Archivo anterior | Reemplazo actual |
| --- | --- |
| `php/menu.php` | `productos/`, `ajax/productos.php`, `ajax/categorias.php` y base de datos |
| `php/pedido.php` | `pedidos/guardar.php`, `pedidos/nuevo.php` y `detalle_pedido` |
| `php/cocina.php` | `cocina/index.php`, `cocina/api_pedidos.php` y `cocina/cocina.js` |
| `php/comandas.php` | `pedidos/index.php`, `pedidos/historial.php` y `cocina/index.php` |
| `php/entregado.php` | `pedidos/actualizar_estado.php` y `php/pedidos_estados.php` |
| `php/index_cliente.php` | `clientes/index.php`, `clientes/perfil.php`, `clientes/login.php` y `clientes/registro.php` |
| `php/productos-index.php` | `productos/index.php` |
| `caja/pedidos.php` | `caja/index.php`, `caja/funciones.php` y `caja/ajax/pedidos.php` |
| `cocina/actualizar_estado.php` | `pedidos/actualizar_estado.php` |

## Como conviene organizar los commits en GitHub

Para que el historial se lea mejor, conviene subir los cambios en grupos chicos. Una posible division seria:

1. `docs: documentar estructura y fases de GenesisBar`
2. `chore: agregar migraciones SQL incrementales`
3. `feat: agregar seguridad, sesiones y roles de empleados`
4. `feat: reorganizar productos con imagenes`
5. `feat: implementar flujo de pedidos y reservas`
6. `feat: agregar pantalla de cocina`
7. `feat: agregar caja, pagos mixtos y tickets`
8. `feat: agregar portal de clientes y puntos`
9. `feat: agregar estadisticas operativas`
10. `style: actualizar estilos por modulo`

## Pendientes recomendados

- Cambiar contrasenas de prueba antes de usar el sistema en un entorno real.
- Probar el flujo completo en celulares reales del local.
- Activar GD/Imagick o definir otra herramienta para optimizar imagenes automaticamente.
- Completar imagenes reales de productos que todavia usan fallback.
- Definir por escrito la politica comercial de canje de puntos.
