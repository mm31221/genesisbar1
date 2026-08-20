<?php

$servidor = defined("DB_HOST") ? DB_HOST : "localhost";
$usuario = defined("DB_USER") ? DB_USER : "root";
$password = defined("DB_PASS") ? DB_PASS : "";
$base_datos = defined("DB_NAME") ? DB_NAME : "genesisbar1";

mysqli_report(MYSQLI_REPORT_OFF);

$conexion = @mysqli_connect(
    $servidor,
    $usuario,
    $password,
    $base_datos
);

if (!$conexion) {
    http_response_code(500);
    die("No se pudo conectar con la base de datos.");
}

mysqli_set_charset($conexion, defined("DB_CHARSET") ? DB_CHARSET : "utf8mb4");

function tabla_existe($conexion, $tabla)
{
    $tabla = mysqli_real_escape_string($conexion, $tabla);
    $resultado = mysqli_query($conexion, "SHOW TABLES LIKE '$tabla'");

    return $resultado && mysqli_num_rows($resultado) > 0;
}

function columna_existe($conexion, $tabla, $columna)
{
    $tabla = mysqli_real_escape_string($conexion, $tabla);
    $columna = mysqli_real_escape_string($conexion, $columna);
    $resultado = mysqli_query($conexion, "SHOW COLUMNS FROM `$tabla` LIKE '$columna'");

    return $resultado && mysqli_num_rows($resultado) > 0;
}

?>
