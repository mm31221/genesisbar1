<?php
require_once("../php/seguridad.php");

iniciar_sesion_segura();

unset($_SESSION["cliente_id"]);
session_regenerate_id(true);

header("Location: index.php");
exit;
