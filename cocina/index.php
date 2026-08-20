<?php
require_once("../config/config.php");
require_once("../php/seguridad.php");
requerir_permiso($conexion, "cocina");

$extra_css = ["/genesisbar1/css/cocina.css?v=4"];
$extra_js = ["/genesisbar1/cocina/cocina.js?v=4"];
$body_class = "pantalla-cocina";

require_once("../includes/header.php");
$csrf = token_csrf();
?>

<section class="cocina-panel">

    <div class="cocina-cabecera">
        <div>
            <h2>Panel de Cocina</h2>
            <p>Comandas activas. Avisar al mozo cuando haya que cambiar el estado.</p>
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
    <input type="hidden" id="csrfCocina" value="<?= htmlspecialchars($csrf); ?>">

    <div id="comandas" class="cocina-grid">
        <div class="cocina-vacio">Cargando pedidos...</div>
    </div>

</section>

<?php
require_once("../includes/footer.php");
?>
