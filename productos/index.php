<?php

require_once "../config/config.php";
require_once "../php/seguridad.php";
require_once "../php/imagenes.php";
requerir_permiso($conexion, "productos");

$sql = "SELECT productos.*, categorias.nombre AS categoria
        FROM productos
        LEFT JOIN categorias
        ON productos.id_categoria = categorias.id_categoria
        ORDER BY productos.nombre";

$resultado = mysqli_query($conexion, $sql);
$csrf = token_csrf();
$extra_css = ["/genesisbar1/css/productos.css?v=2"];

require_once "../includes/header.php";

?>

<section class="contenedor">
    <div class="pedidos-cabecera">
        <div>
            <h2>Productos</h2>
            <p>Gestion de productos y categorias comerciales.</p>
        </div>
        <a class="boton" href="agregar.php">Nuevo Producto</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagen</th>
                <th>Producto</th>
                <th>Categoria</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultado && mysqli_num_rows($resultado) > 0) { ?>
                <?php while ($fila = mysqli_fetch_assoc($resultado)) { ?>
                    <tr>
                        <td><?= (int) $fila["id_producto"]; ?></td>
                        <td><img class="producto-thumb" src="<?= htmlspecialchars(imagen_url($fila["imagen"] ?? "")); ?>" alt="<?= htmlspecialchars($fila["nombre"]); ?>"></td>
                        <td><?= htmlspecialchars($fila["nombre"]); ?></td>
                        <td><?= htmlspecialchars($fila["categoria"] ?? "Sin categoria"); ?></td>
                        <td>$ <?= htmlspecialchars(number_format((float) $fila["precio"], 2, ",", ".")); ?></td>
                        <td><?= (int) $fila["stock"]; ?></td>
                        <td><?= !empty($fila["activo"]) ? "Activo" : "Inactivo"; ?></td>
                        <td>
                            <a class="boton boton-secundario" href="editar.php?id=<?= (int) $fila["id_producto"]; ?>">Editar</a>
                            <form class="form-inline" action="eliminar.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="id" value="<?= (int) $fila["id_producto"]; ?>">
                                <button class="boton boton-peligro" type="submit" onclick="return confirm('Eliminar este producto?');">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="8">No hay productos cargados.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>

<?php require_once "../includes/footer.php"; ?>
