<?php
require_once("../config/config.php");
require_once("../php/seguridad.php");
requerir_permiso($conexion, "pedidos");

$extra_css = ["/genesisbar1/css/pedidos.css?v=6"];
$extra_js = ["/genesisbar1/pedidos/pedidos.js?v=3"];

require_once("../includes/header.php");
$csrf = token_csrf();
?>
<div class="contenedor contenedor-pedidos">

    <div class="pedidos-cabecera">
        <div>
            <h2>Pedidos</h2>
            <p>Seguimiento operativo de pedidos activos.</p>
        </div>

        <div class="pedidos-acciones-superiores">
            <span>Actualizado: <strong id="ultimaActualizacionPedidos">--:--:--</strong></span>
            <a href="historial.php?vista=hoy" class="boton boton-secundario">Historial</a>
            <a href="nuevo.php" class="boton boton-nuevo-pedido">Nuevo Pedido</a>
        </div>
    </div>

    <div id="mensajeListadoPedidos" class="mensaje-pedido" role="status" aria-live="polite"></div>
    <input type="hidden" id="csrfPedidosListado" value="<?= htmlspecialchars($csrf); ?>">

    <div class="estadisticas-pedidos filtros-pedidos">

        <button type="button" class="card todos filtro-estado activo" data-estado="Todos">
            <h3 data-contador="Todos">0</h3>
            <p>Activos</p>
        </button>

        <button type="button" class="card amarillo filtro-estado" data-estado="Pendiente">
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

    </div>

    <div id="pedidosGrid" class="pedidos-grid" aria-live="polite">
        <div class="pedido-card">Cargando pedidos...</div>
    </div>

</div>

<?php
require_once("../includes/footer.php");
?>
