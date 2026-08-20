<?php
date_default_timezone_set("America/Argentina/Buenos_Aires");
require_once(__DIR__ . "/../php/seguridad.php");

$empleado_nav = isset($conexion) ? empleado_actual($conexion) : null;
$inicio_nav = $empleado_nav ? empleado_inicio_url($empleado_nav) : genesis_url("index.php");

function nav_permitido($empleado, $permiso)
{
    return $empleado && empleado_tiene_permiso($empleado, $permiso);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <link rel="stylesheet" href="/genesisbar1/css/estilos.css?v=3">

<?php
if (!empty($extra_css) && is_array($extra_css)) {
    foreach ($extra_css as $css) {
?>
 <link rel="stylesheet" href="<?= htmlspecialchars($css); ?>">
<?php
    }
}
?>

</head>

<body class="<?= htmlspecialchars($body_class ?? ""); ?>">

<header>
<h1>
    <img src="/genesisbar1/assets/img/logo/genesis-logo.jpeg" alt="GenesisBar" class="logo-header"> 
</h1>
    <nav>

        <a href="<?= htmlspecialchars($inicio_nav); ?>">Inicio</a>

        <?php if (nav_permitido($empleado_nav, "productos")) { ?>
            <a href="/genesisbar1/productos/index.php">Productos</a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "pedidos")) { ?>
            <a href="/genesisbar1/pedidos/index.php">Pedidos</a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "cocina")) { ?>
            <a href="/genesisbar1/cocina/index.php">Cocina</a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "clientes")) { ?>
            <a href="/genesisbar1/clientes/admin.php">Clientes</a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "caja")) { ?>
            <a href="/genesisbar1/caja/index.php">Caja</a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "empleados")) { ?>
            <a href="/genesisbar1/empleados/index.php">Empleados</a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "estadisticas")) { ?>
            <a href="/genesisbar1/estadisticas/index.php">Estadísticas</a>
        <?php } ?>

        <?php if ($empleado_nav) { ?>
            <a href="/genesisbar1/empleados/logout.php">Salir (<?= htmlspecialchars($empleado_nav["rol"]); ?>)</a>
        <?php } else { ?>
            <a href="/genesisbar1/empleados/login.php">Empleados</a>
        <?php } ?>

    </nav>

</header>

<main>
