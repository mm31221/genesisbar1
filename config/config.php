<?php

date_default_timezone_set("America/Argentina/Buenos_Aires");

define("GENESIS_BASE_PATH", dirname(__DIR__));
define("GENESIS_BASE_URL", "/genesisbar1");

define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "genesisbar1");
define("DB_CHARSET", "utf8mb4");

define("PUNTOS_POR_PESOS", 100);

function genesis_url($ruta = "")
{
    return rtrim(GENESIS_BASE_URL, "/") . "/" . ltrim((string) $ruta, "/");
}

require_once GENESIS_BASE_PATH . "/php/conexion.php";
