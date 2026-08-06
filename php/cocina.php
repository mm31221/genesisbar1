<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

include("conexion.php");
include("helpers.php");
include("menu.php");

$sql = "SELECT * FROM pedidos WHERE estado = 'Pendiente' ORDER BY id ASC";
$resultado = mysqli_query($conexion, $sql);
$sectores = [
    "piezas_frias" => [],
    "horno_freidora" => [],
    "barra" => [],
    "otros" => []
];
$total_pendientes = 0;

if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $sector = sector_producto($fila["producto"]);

        if (!array_key_exists($sector, $sectores)) {
            $sector = "otros";
        }

        $sectores[$sector][] = $fila;
        $total_pendientes++;
    }
}

function datos_tiempo_pedido($hora)
{
    $minutos_espera = floor((time() - strtotime($hora)) / 60);

    if ($minutos_espera <= 10) {
        return [$minutos_espera, "a-tiempo", "A tiempo"];
    }

    if ($minutos_espera <= 30) {
        return [$minutos_espera, "demorado", "Demorado"];
    }

    return [$minutos_espera, "atrasado", "Muy atrasado"];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5">
    <title>Cocina - Genesis Bar</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>

<div class="contenedor contenedor-cocina">

    <h1>Pantalla de Cocina</h1>
    <p>Pedidos pendientes separados por sector. La pantalla se actualiza automaticamente cada 5 segundos.</p>

    <div class="acciones">
        <a href="../index.php">Nueva comanda</a>
        <a href="comandas.php">Ver todas las comandas</a>
    </div>

    <?php if ($total_pendientes > 0): ?>
        <?php foreach ($sectores as $sector => $pedidos): ?>
            <section class="cocina-sector">
                <div class="sector-encabezado">
                    <h2><?php echo htmlspecialchars(texto_sector_cocina($sector)); ?></h2>
                    <span><?php echo count($pedidos); ?> pendientes</span>
                </div>

                <?php if (count($pedidos) > 0): ?>
                    <div class="cocina-grid">
                        <?php foreach ($pedidos as $fila): ?>
                            <?php [$minutos_espera, $clase_estado, $texto_estado] = datos_tiempo_pedido($fila["hora"]); ?>

                            <div class="comanda tarjeta-cocina <?php echo $clase_estado; ?>">
                                <div class="tarjeta-encabezado">
                                    <h2>Comanda #<?php echo htmlspecialchars($fila['id']); ?></h2>
                                    <span class="estado-tiempo"><?php echo htmlspecialchars($texto_estado); ?></span>
                                </div>

                                <p><strong>Producto:</strong> <?php echo htmlspecialchars(nombre_producto($fila['producto'])); ?></p>
                                <?php if (descripcion_producto($fila['producto']) !== ""): ?>
                                    <p><strong>Detalle:</strong> <?php echo htmlspecialchars(descripcion_producto($fila['producto'])); ?></p>
                                <?php endif; ?>
                                <p><strong>Cantidad:</strong> <?php echo htmlspecialchars($fila['cantidad']); ?></p>
                                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($fila['nombre_cliente'] ?: 'Sin cliente'); ?></p>
                                <p><strong>Telefono:</strong> <?php echo htmlspecialchars($fila['telefono_cliente'] ?: '-'); ?></p>
                                <p><strong>Tipo:</strong> <?php echo htmlspecialchars(texto_tipo_pedido($fila['tipo_pedido'] ?? 'salon')); ?></p>
                                <p><strong>Destino:</strong> <?php echo htmlspecialchars(detalle_entrega($fila)); ?></p>
                                <p><strong>Hora:</strong> <?php echo htmlspecialchars($fila['hora']); ?></p>
                                <p><strong>Espera:</strong> <?php echo htmlspecialchars($minutos_espera); ?> minutos</p>
                                <p><strong>Total:</strong> $<?php echo number_format($fila['total'], 0, ',', '.'); ?></p>

                                <a href="entregado.php?id=<?php echo urlencode($fila['id']); ?>&volver=cocina">Marcar entregado</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="sector-vacio">No hay pedidos para este sector.</p>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="comanda">
            <h2>No hay pedidos pendientes</h2>
            <p>Cuando se registre una nueva comanda, va a aparecer aca.</p>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
