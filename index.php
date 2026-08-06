<?php
require_once "includes/header.php";
?>

<h2>Bienvenido a GenesisBar 1.0</h2>

<p>Seleccione una opción del menú principal.</p>

<div class="contenedor">

    <div class="menu">

        <a href="pedidos/index.php" class="boton">
            🍔<br><br>
            Nuevo Pedido
        </a>

        <a href="productos/index.php" class="boton">
            📋<br><br>
            Productos
        </a>

        <a href="cocina/index.php" class="boton">
            Cocina<br><br>
            Panel
        </a>

        <a href="clientes/index.php" class="boton">
            👥<br><br>
            Clientes
        </a>

        <a href="caja/index.php" class="boton">
            💰<br><br>
            Caja
        </a>

        <a href="estadisticas/index.php" class="boton">
            📊<br><br>
            Estadísticas
        </a>

    </div>

</div>

<?php
require_once "includes/footer.php";
?>
