<?php
require_once("../config/config.php");
require_once("../php/seguridad.php");
requerir_permiso($conexion, "pedidos");

$extra_css = ["/genesisbar1/css/pedidos.css?v=7"];
$extra_js = ["/genesisbar1/js/pedidos.js?v=7"];

require_once("../includes/header.php");
$csrf = token_csrf();

$sql_mesas = "SELECT * FROM mesas ORDER BY numero ASC";
$mesas = mysqli_query($conexion, $sql_mesas);

$sql_formas_pago = "SELECT * FROM formas_pago ORDER BY nombre ASC";
$formas_pago = mysqli_query($conexion, $sql_formas_pago);
?>

<section class="nuevo-pedido-page">
    <div class="pedidos-cabecera">
        <div>
            <h2>Nuevo Pedido</h2>
            <p>Arma la comanda y enviala a cocina.</p>
        </div>
        <a class="boton boton-secundario" href="/genesisbar1/pedidos/index.php">Volver</a>
    </div>

    <div id="mensajePedido" class="mensaje-pedido" role="status" aria-live="polite"></div>
    <input type="hidden" id="csrfPedido" value="<?= htmlspecialchars($csrf); ?>">

    <div class="pedido-layout">
        <details class="pedido-datos-shell" open>
            <summary>Datos del pedido</summary>
            <aside class="panel-pedido pedido-datos-panel" id="pedidoDatosPanel">
            <h3>Datos del pedido</h3>

            <div class="pedido-form-grid">
                <div class="campo-pedido">
                    <label for="tipo_pedido">Tipo de Pedido</label>
                    <select id="tipo_pedido">
                        <option value="Mesa">Mesa</option>
                        <option value="Take Away">Take Away</option>
                        <option value="Delivery">Delivery</option>
                    </select>
                </div>

                <div class="campo-pedido">
                    <label for="tipo_reserva">Reserva</label>
                    <select id="tipo_reserva">
                        <option value="Ninguna">Sin reserva</option>
                        <option value="Mesa">Reserva de mesa</option>
                        <option value="Pedido">Pedido programado</option>
                    </select>
                </div>

                <div class="campo-pedido">
                    <label for="origen">Origen</label>
                    <select id="origen">
                        <option value="Salon">Salon</option>
                        <option value="Pagina web">Web</option>
                        <option value="Aplicacion">Aplicacion</option>
                        <option value="Codigo QR">Codigo QR</option>
                    </select>
                </div>

                <div id="grupoMesa" class="campo-pedido">
                    <label for="mesa">Mesa</label>
                    <select id="mesa">
                        <option value="">Seleccionar mesa</option>

                        <?php while ($mesa = mysqli_fetch_assoc($mesas)) { ?>
                            <option
                                value="<?= (int) $mesa["id_mesa"]; ?>"
                                data-numero="<?= htmlspecialchars($mesa["numero"]); ?>">
                                Mesa <?= htmlspecialchars($mesa["numero"]); ?>
                            </option>
                        <?php } ?>

                    </select>
                </div>

                <div class="campo-pedido grupoCliente" hidden>
                    <label for="nombre_cliente">Nombre del cliente</label>
                    <input id="nombre_cliente" type="text" maxlength="100">
                </div>

                <div class="campo-pedido grupoCliente" hidden>
                    <label for="telefono_cliente">Telefono</label>
                    <input id="telefono_cliente" type="tel" maxlength="20">
                </div>

                <div class="campo-pedido grupoCliente" hidden>
                    <label for="id_forma_pago">Forma de pago</label>
                    <select id="id_forma_pago">
                        <option value="">Seleccionar forma de pago</option>

                        <?php while ($forma = mysqli_fetch_assoc($formas_pago)) { ?>
                            <option value="<?= (int) $forma["id_forma_pago"]; ?>">
                                <?= htmlspecialchars($forma["nombre"]); ?>
                            </option>
                        <?php } ?>

                    </select>
                </div>

                <div id="grupoDireccion" class="campo-pedido" hidden>
                    <label>Direccion de entrega</label>
                    <div class="direccion-grid">
                        <input id="direccion_calle" type="text" maxlength="120" placeholder="Calle">
                        <input id="direccion_altura" type="text" maxlength="20" placeholder="Altura">
                    </div>
                    <input id="direccion_entrega" type="hidden">
                </div>

                <div class="campo-pedido">
                    <label for="horario_entrega">Horario de entrega</label>
                    <input id="horario_entrega" type="datetime-local">
                </div>
            </div>

            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" rows="3" maxlength="500"></textarea>
            </aside>
        </details>

        <section class="pedido-productos-panel">
            <div class="panel-pedido pedido-categorias-panel">
                <h3>Categorias</h3>
                <input id="buscarProducto" class="buscador-productos" type="search" placeholder="Buscar producto">
            </div>
            <div id="categorias" class="categorias">
                <p>Cargando categorias...</p>
            </div>

            <div id="productos" class="productos-grid-pedido">
                <p>Selecciona una categoria.</p>
            </div>
        </section>

        <aside class="pedido-derecha" id="panelCarritoPedido">
            <div class="carrito-titulo">
            <h3>Carrito</h3>
            <strong id="totalCarrito">$0</strong>
            </div>

            <div id="carrito">
                No hay productos.
            </div>

            <button id="confirmarPedido" class="boton-confirmar" type="button">
                Enviar a cocina
            </button>
        </aside>
    </div>

    <button id="abrirCarritoMobile" class="carrito-mobile-bar" type="button">
        Ver carrito (0) - $0
    </button>
</section>

<?php
require_once("../includes/footer.php");
?>
