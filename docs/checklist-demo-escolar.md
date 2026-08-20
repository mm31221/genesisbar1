# Checklist de demostracion escolar

## Preparacion

- [ ] Iniciar Apache y MySQL en XAMPP.
- [ ] Abrir `http://localhost/genesisbar1/`.
- [ ] Confirmar que no aparecen errores PHP.
- [ ] Tener a mano los usuarios de prueba: `admin`, `mozo`, `cocina`, `cajero`.
- [ ] Confirmar que hay productos activos cargados.

## Presentacion sugerida

- [ ] Mostrar el inicio de GenesisBar 1.0.
- [ ] Iniciar sesion como Administrador.
- [ ] Mostrar la navegacion por modulos.
- [ ] Abrir Empleados y explicar roles/permisos.
- [ ] Abrir Productos y mostrar imagenes/fallback.
- [ ] Abrir Clientes y mostrar puntos/historial.
- [ ] Abrir Estadisticas y mostrar filtros/graficos.
- [ ] Cerrar sesion.

## Flujo operativo

- [ ] Iniciar sesion como Mozo.
- [ ] Crear un pedido nuevo desde `pedidos/nuevo.php`.
- [ ] Agregar productos al carrito.
- [ ] Enviar el pedido a cocina.
- [ ] Ver el pedido en listado de pedidos.
- [ ] Iniciar sesion como Cocina y mostrar la comanda.
- [ ] Volver con Mozo o Caja y avanzar estados: Pendiente, Preparando, Listo, Entregado.
- [ ] Iniciar sesion como Cajero.
- [ ] Cobrar el pedido.
- [ ] Abrir el ticket.
- [ ] Mostrar historial de caja.
- [ ] Mostrar que el pedido ya no puede cobrarse dos veces.

## Seguridad para explicar

- [ ] El rol visual del login no da permisos por si solo.
- [ ] El servidor valida el rol real de la base.
- [ ] Las paginas protegidas revisan permisos antes de mostrar datos.
- [ ] Los formularios importantes usan CSRF.
- [ ] Las contrasenas estan hasheadas.
- [ ] Los cobros y puntos se guardan en transaccion.

## Cierre

- [ ] Mostrar el README.
- [ ] Explicar fases realizadas.
- [ ] Explicar pendientes reales: contrasenas, imagenes faltantes, pruebas en celulares y regla de canje de puntos.
