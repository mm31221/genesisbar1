<?php
require_once("../php/conexion.php");
require_once("../includes/header.php");
?>

<section class="cocina-panel">

    <div class="cocina-cabecera">
        <div>
            <h2>Panel de Cocina</h2>
            <p>Comandas activas enfocadas en productos, cantidades y tiempos de preparacion.</p>
        </div>

        <div class="cocina-actualizacion">
            <span>Actualizado</span>
            <strong id="ultimaActualizacion">--:--:--</strong>
        </div>
    </div>

    <div class="cocina-contadores">
        <div class="contador pendiente">
            <strong id="contadorPendientes">0</strong>
            <span>Pendientes</span>
        </div>

        <div class="contador preparando">
            <strong id="contadorPreparando">0</strong>
            <span>En preparacion</span>
        </div>

        <div class="contador listo">
            <strong id="contadorListos">0</strong>
            <span>Listos</span>
        </div>

        <div class="contador total">
            <strong id="contadorTotal">0</strong>
            <span>Activos</span>
        </div>
    </div>

    <div id="mensajeCocina" class="mensaje-pedido" role="status" aria-live="polite"></div>

    <div id="comandas" class="cocina-grid">
        <div class="cocina-vacio">Cargando pedidos...</div>
    </div>

</section>

<script src="/genesisbar1/cocina/cocina.js?v=1"></script>

<?php
require_once("../includes/footer.php");
?>
