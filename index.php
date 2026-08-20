<?php
require_once "config/config.php";
require_once "php/seguridad.php";

$empleado_inicio = empleado_actual($conexion);

if (!$empleado_inicio) {
    header("Location: " . genesis_url("empleados/login.php"));
    exit;
}

if (rol_normalizado($empleado_inicio["rol"] ?? "") !== "Administrador") {
    header("Location: " . empleado_inicio_url($empleado_inicio));
    exit;
}

require_once "includes/header.php";
?>

<h2>Panel de Administracion</h2>

<p>Gestion general de GenesisBar 1.0.</p>

<div class="contenedor">
    <div class="menu">
        <?php if (nav_permitido($empleado_nav, "pedidos")) { ?>
            <a href="pedidos/nuevo.php" class="boton">
                <span class="menu-icon" aria-hidden="true">+</span>
                <span>Nuevo Pedido</span>
            </a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "productos")) { ?>
            <a href="productos/index.php" class="boton">
                <span class="menu-icon" aria-hidden="true">PR</span>
                <span>Productos</span>
            </a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "cocina")) { ?>
            <a href="cocina/index.php" class="boton">
                <span class="menu-icon" aria-hidden="true">CO</span>
                <span>Cocina</span>
                <small>Panel</small>
            </a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "clientes")) { ?>
            <a href="clientes/admin.php" class="boton">
                <span class="menu-icon" aria-hidden="true">CL</span>
                <span>Clientes</span>
            </a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "caja")) { ?>
            <a href="caja/index.php" class="boton">
                <span class="menu-icon" aria-hidden="true">$</span>
                <span>Caja</span>
            </a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "empleados")) { ?>
            <a href="empleados/index.php" class="boton">
                <span class="menu-icon" aria-hidden="true">US</span>
                <span>Empleados</span>
                <small>Roles</small>
            </a>
        <?php } ?>

        <?php if (nav_permitido($empleado_nav, "estadisticas")) { ?>
            <a href="estadisticas/index.php" class="boton">
                <span class="menu-icon" aria-hidden="true">ES</span>
                <span>Estadisticas</span>
            </a>
        <?php } ?>
    </div>
</div>

<?php
require_once "includes/footer.php";
?>
