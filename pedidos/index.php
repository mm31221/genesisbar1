<?php
require_once("../php/conexion.php");
require_once("../includes/header.php");
?>

<div class="contenedor contenedor-pedidos">

    <div class="pedidos-cabecera">
        <div>
            <h2>Pedidos</h2>
            <p>Gestion de pedidos por estado.</p>
        </div>

        <div class="pedidos-acciones-superiores">
            <span>Actualizado: <strong id="ultimaActualizacionPedidos">--:--:--</strong></span>
            <a href="nuevo.php" class="boton boton-nuevo-pedido">Nuevo Pedido</a>
        </div>
    </div>

    <div id="mensajeListadoPedidos" class="mensaje-pedido" role="status" aria-live="polite"></div>

    <div class="estadisticas-pedidos filtros-pedidos">

        <button type="button" class="card amarillo filtro-estado activo" data-estado="Pendiente">
            <h3 data-contador="Pendiente">0</h3>
            <p>Pendientes</p>
        </button>

        <button type="button" class="card azul filtro-estado" data-estado="Preparando">
            <h3 data-contador="Preparando">0</h3>
            <p>En preparacion</p>
        </button>

        <button type="button" class="card verde filtro-estado" data-estado="Listo">
            <h3 data-contador="Listo">0</h3>
            <p>Listos</p>
        </button>

        <button type="button" class="card gris filtro-estado" data-estado="Entregado">
            <h3 data-contador="Entregado">0</h3>
            <p>Entregados</p>
        </button>

    </div>

    <section class="pedidos-nuevos-seccion">
        <h3>Pedidos nuevos</h3>
        <div id="pedidosNuevos" class="pedidos-nuevos-grid">
            <div class="pedido-nuevo-vacio">Cargando pedidos nuevos...</div>
        </div>
    </section>

    <div class="tabla-responsive">
        <table class="tabla-pedidos">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Tipo</th>
                    <th>Mesa / Cliente / Direccion</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Hora</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaPedidosBody">
                <tr>
                    <td colspan="7">Cargando pedidos...</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<script src="/genesisbar1/pedidos/pedidos.js?v=1"></script>

<?php
require_once("../includes/footer.php");
?>
