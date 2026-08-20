<?php
require_once("../config/config.php");
require_once("../php/imagenes.php");

header("Content-Type: application/json; charset=utf-8");

$id_categoria = isset($_GET["categoria"]) ? (int) $_GET["categoria"] : 0;

if ($id_categoria < 1) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Selecciona una categoria."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT
            id_producto,
            nombre,
            descripcion,
            imagen,
            precio,
            stock,
            sector,
            tiempo_preparacion,
            id_categoria
        FROM productos
        WHERE id_categoria = ?
        AND activo = 1
        ORDER BY nombre ASC";

$stmt = mysqli_prepare($conexion, $sql);
$productos = [];

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_categoria);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $productos[] = [
            "id_producto" => (int) $fila["id_producto"],
            "nombre" => $fila["nombre"],
            "descripcion" => $fila["descripcion"] ?? "",
            "imagen" => $fila["imagen"] ?? "",
            "imagen_url" => imagen_url($fila["imagen"] ?? ""),
            "precio" => (float) $fila["precio"],
            "stock" => (int) ($fila["stock"] ?? 0),
            "sector" => $fila["sector"] ?? "",
            "tiempo_preparacion" => (int) ($fila["tiempo_preparacion"] ?? 0),
            "id_categoria" => (int) $fila["id_categoria"]
        ];
    }

    mysqli_stmt_close($stmt);
}

echo json_encode([
    "ok" => true,
    "productos" => $productos
], JSON_UNESCAPED_UNICODE);
?>
