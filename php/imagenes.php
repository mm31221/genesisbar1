<?php

function imagen_producto_default()
{
    return "assets/img/productos/producto-default.svg";
}

function imagen_url($ruta)
{
    $ruta = trim((string) $ruta);

    if ($ruta === "") {
        $ruta = imagen_producto_default();
    }

    if (preg_match("/^https?:\/\//i", $ruta)) {
        return $ruta;
    }

    return genesis_url($ruta);
}

function imagen_ruta_local($ruta)
{
    $ruta = ltrim(str_replace(["\\", "\0"], ["/", ""], (string) $ruta), "/");
    $base = realpath(GENESIS_BASE_PATH);
    $destino = realpath(GENESIS_BASE_PATH . "/" . $ruta);

    $base = rtrim($base ?: "", DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $destino = $destino ?: "";

    if ($base === DIRECTORY_SEPARATOR || strpos($destino . DIRECTORY_SEPARATOR, $base) !== 0) {
        return null;
    }

    return $destino;
}

function imagen_producto_subida($archivo, &$error = "")
{
    if (empty($archivo) || !isset($archivo["error"]) || $archivo["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($archivo["error"] !== UPLOAD_ERR_OK) {
        $error = "No se pudo subir la imagen.";
        return false;
    }

    if (($archivo["size"] ?? 0) > 3 * 1024 * 1024) {
        $error = "La imagen no puede superar 3 MB.";
        return false;
    }

    $tmp = $archivo["tmp_name"] ?? "";
    $info = @getimagesize($tmp);

    if (!$info || empty($info["mime"])) {
        $error = "El archivo no parece ser una imagen valida.";
        return false;
    }

    $extensiones = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($extensiones[$info["mime"]])) {
        $error = "Solo se aceptan imagenes JPG, PNG o WEBP.";
        return false;
    }

    $directorio = GENESIS_BASE_PATH . "/assets/img/productos";

    if (!is_dir($directorio) && !mkdir($directorio, 0775, true)) {
        $error = "No se pudo preparar la carpeta de imagenes.";
        return false;
    }

    $nombre = "producto-" . date("YmdHis") . "-" . bin2hex(random_bytes(4)) . "." . $extensiones[$info["mime"]];
    $destino = $directorio . "/" . $nombre;

    if (!move_uploaded_file($tmp, $destino)) {
        $error = "No se pudo guardar la imagen.";
        return false;
    }

    return "assets/img/productos/" . $nombre;
}

?>
