<?php
require_once("../config/config.php");
require_once("../php/seguridad.php");

iniciar_sesion_segura();
$empleado = empleado_actual($conexion);

if (isset($_GET["todos"])) {
    unset($_SESSION["empleado_id"], $_SESSION["empleado_rol_activo"], $_SESSION["empleados_por_rol"]);
} elseif ($empleado) {
    $rol = rol_normalizado($empleado["rol"] ?? "");

    if ($rol !== "" && isset($_SESSION["empleados_por_rol"][$rol])) {
        unset($_SESSION["empleados_por_rol"][$rol]);
    }

    unset($_SESSION["empleado_id"], $_SESSION["empleado_rol_activo"]);

    if (!empty($_SESSION["empleados_por_rol"]) && is_array($_SESSION["empleados_por_rol"])) {
        foreach ($_SESSION["empleados_por_rol"] as $rol_activo => $id_empleado) {
            $_SESSION["empleado_id"] = (int) $id_empleado;
            $_SESSION["empleado_rol_activo"] = $rol_activo;
            break;
        }
    }
} else {
    unset($_SESSION["empleado_id"], $_SESSION["empleado_rol_activo"]);
}

session_regenerate_id(true);

header("Location: /genesisbar1/empleados/login.php");
exit;
?>
