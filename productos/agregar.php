<?php

require_once "../php/conexion.php";
require_once "../includes/header.php";

// Si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $precio = $_POST["precio"];
    $stock = $_POST["stock"];
    $id_categoria = $_POST["id_categoria"];
    $activo = isset($_POST["activo"]) ? 1 : 0;

    $sql = "INSERT INTO productos
            (nombre, descripcion, precio, stock, activo, id_categoria)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssdiii",
        $nombre,
        $descripcion,
        $precio,
        $stock,
        $activo,
        $id_categoria
    );

    mysqli_stmt_execute($stmt);

    header("Location: index.php");
    exit;
}

// Cargar categorías
$categorias = mysqli_query(
    $conexion,
    "SELECT * FROM categorias ORDER BY nombre ASC"
);

?>

<h2>➕ Nuevo Producto</h2>

<form method="POST">

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