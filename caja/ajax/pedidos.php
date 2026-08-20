<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");

require_once("../../config/config.php");
require_once("../funciones.php");
require_once("../../php/seguridad.php");

header("Content-Type: text/html; charset=utf-8");

if (!empleado_tiene_permiso(empleado_actual($conexion), "caja")) {
    http_response_code(403);
    echo '<div class="caja-vacio">No autorizado.</div>';
    exit;
}

caja_render_pedidos_cobrables($conexion);
?>
