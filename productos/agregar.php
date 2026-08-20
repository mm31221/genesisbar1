<?php

require_once "../config/config.php";
require_once "../php/seguridad.php";
require_once "../php/imagenes.php";
requerir_permiso($conexion, "productos");

$csrf = token_csrf();
$mensaje = "";

// Si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!validar_csrf($_POST["csrf_token"] ?? "")) {
        die("La sesion vencio. Volve a intentar.");
    }

    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $precio = $_POST["precio"];
    $stock = $_POST["stock"];
    $id_categoria = $_POST["id_categoria"];
    $activo = isset($_POST["activo"]) ? 1 : 0;
    $error_imagen = "";
    $imagen = imagen_producto_subida($_FILES["imagen"] ?? null, $error_imagen);

    if ($imagen === false) {
        $mensaje = $error_imagen;
    } else {
        $sql = "INSERT INTO productos
                (nombre, descripcion, imagen, precio, stock, activo, id_categoria)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conexion, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "sssdiii",
            $nombre,
            $descripcion,
            $imagen,
            $precio,
            $stock,
            $activo,
            $id_categoria
        );

        mysqli_stmt_execute($stmt);

        header("Location: index.php");
        exit;
    }
}

// Cargar categorías
$categorias = mysqli_query(
    $conexion,
    "SELECT * FROM categorias ORDER BY nombre ASC"
);

$extra_css = ["/genesisbar1/css/productos.css?v=2"];
require_once "../includes/header.php";

?>

<h2>➕ Nuevo Producto</h2>

<?php if ($mensaje !== "") { ?>
    <div class="mensaje-formulario error"><?= htmlspecialchars($mensaje); ?></div>
<?php } ?>

<form method="POST" enctype="multipart/form-data" class="producto-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">

    <p><strong>Nombre</strong></p>

    <input
        type="text"
        name="nombre"
        required
        style="width:100%;padding:10px;"
    >

    <br><br>

    <p><strong>Descripción</strong></p>

    <textarea
        name="descripcion"
        rows="4"
        style="width:100%;padding:10px;"
    ></textarea>

    <br><br>

    <p><strong>Imagen</strong></p>

    <input
        type="file"
        name="imagen"
        accept="image/jpeg,image/png,image/webp"
    >

    <small class="ayuda-campo">JPG, PNG o WebP. Maximo 3 MB.</small>

    <br><br>

    <p><strong>Categoría</strong></p>

    <select
        name="id_categoria"
        required
        style="width:100%;padding:10px;"
    >

        <?php while($cat=mysqli_fetch_assoc($categorias)){ ?>

            <option value="<?= $cat["id_categoria"] ?>">

                <?= htmlspecialchars($cat["nombre"]) ?>

            </option>

        <?php } ?>

    </select>

    <br><br>

    <p><strong>Precio</strong></p>

    <input
        type="number"
        step="0.01"
        name="precio"
        required
        style="width:100%;padding:10px;"
    >

    <br><br>

    <p><strong>Stock</strong></p>

    <input
        type="number"
        name="stock"
        value="0"
        style="width:100%;padding:10px;"
    >

    <br><br>

    <label>

        <input
            type="checkbox"
            name="activo"
            checked
        >

        Producto activo

    </label>

    <br><br>

    <button class="boton" type="submit">

        💾 Guardar Producto

    </button>

    <a
        href="index.php"
        class="boton"
    >

        ← Cancelar

    </a>

</form>

<?php

require_once "../includes/footer.php";

?>
