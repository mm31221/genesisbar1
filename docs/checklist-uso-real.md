# Checklist para uso real en Genesis Bar

## Antes de operar

- [ ] Cambiar las contrasenas de prueba de todos los usuarios.
- [ ] Crear un usuario individual para cada empleado real.
- [ ] Desactivar usuarios que no trabajen en el local.
- [ ] Confirmar que cada empleado entra solo a su modulo.
- [ ] Revisar productos, precios, stock y categorias.
- [ ] Cargar imagenes reales para productos sin foto.
- [ ] Confirmar formas de pago activas.
- [ ] Revisar mesas y nombres usados por el salon.
- [ ] Hacer backup de la base de datos.

## Prueba de apertura diaria

- [ ] Abrir `http://localhost/genesisbar1/`.
- [ ] Iniciar sesion como Mozo.
- [ ] Crear un pedido de prueba chico.
- [ ] Confirmar que aparece en Cocina.
- [ ] Pasar el pedido por los estados operativos.
- [ ] Cobrarlo desde Caja.
- [ ] Confirmar que aparece en ticket e historial.
- [ ] Confirmar que no se puede cobrar dos veces.
- [ ] Anular o limpiar el pedido de prueba si corresponde al procedimiento interno.

## Operacion durante el turno

- [ ] Mozo usa `pedidos/nuevo.php` para crear pedidos.
- [ ] Cocina mantiene abierto `cocina/index.php`.
- [ ] Caja revisa `caja/index.php` para cobrar pedidos entregados.
- [ ] Administracion revisa clientes, empleados, productos y estadisticas fuera del pico de trabajo.
- [ ] Si hay error de red o XAMPP, no reenviar cobros sin revisar Caja/Historial primero.

## Control de puntos

- [ ] Confirmar que la regla activa es 1 punto cada $100.
- [ ] Revisar el historial de puntos de un cliente despues de cobrar.
- [ ] No ajustar puntos sin dejar descripcion clara.
- [ ] Definir por escrito premios, canjes y condiciones antes de activar promociones.

## Seguridad y mantenimiento

- [ ] Hacer backup de base de datos al cierre o con la frecuencia definida.
- [ ] No compartir el usuario administrador.
- [ ] No usar contrasenas simples en produccion.
- [ ] Mantener XAMPP solo accesible desde la red necesaria.
- [ ] Revisar que `assets/img/productos/` no tenga archivos desconocidos.
- [ ] Probar la interfaz en al menos un celular Android y una computadora del local.

## Senales de alerta

- [ ] Un pedido aparece duplicado.
- [ ] Un cobro no aparece en historial.
- [ ] Los puntos suben dos veces por el mismo pedido.
- [ ] Una pantalla muestra errores PHP.
- [ ] Un rol puede entrar a un modulo que no corresponde.
- [ ] Una imagen subida no se ve o rompe la tarjeta del producto.

Si ocurre cualquiera de esas senales, detener el uso real de esa parte y revisar antes de continuar.
