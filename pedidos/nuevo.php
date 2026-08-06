<?php
require_once("../php/conexion.php");
require_once("../includes/header.php");

$sql_mesas = "SELECT * FROM mesas ORDER BY numero ASC";
$mesas = mysqli_query($conexion, $sql_mesas);

$sql_formas_pago = "SELECT * FROM formas_pago ORDER BY nombre ASC";
$formas_pago = mysqli_query($conexion, $sql_formas_pago);
?>

<h2>Nuevo Pedido</h2>

<div id="mensajePedido" class="mensaje-pedido" role="status" aria-live="polite"></div>

<div class="pedido-contenedor">

    <div class="pedido-izquierda">

        <section class="panel-pedido">

            <h3>Datos del Pedido</h3>

            <div class="pedido-grid">

                <div>
                    <label for="tipo_pedido">Tipo de Pedido</label>
                    <select id="tipo_pedido">
                        <option value="Mesa">Mesa</option>
                        <option value="Take Away">Take Away</option>
                        <option value="Delivery">Delivery</option>
                    </select>
                </div>

                <div>
                    <label for="origen">Origen</label>
                    <select id="origen">
                        <option value="Pagina web">Pagina web</option>
                        <option value="Aplicacion">Aplicacion</option>
                        <option value="Codigo QR">Codigo QR</option>
                    </select>
                </div>

                <div id="grupoMesa">
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

                <div class="grupoCliente" hidden>
                    <label for="nombre_cliente">Nombre del cliente</label>
                    <input id="nombre_cliente" type="text" maxlength="100">
                </div>

                <div class="grupoCliente" hidden>
                    <label for="telefono_cliente">Telefono</label>
                    <input id="telefono_cliente" type="tel" maxlength="20">
                </div>

                <div class="grupoCliente" hidden>
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

                <div id="grupoDireccion" hidden>
                    <label for="direccion_entrega">Direccion de entrega</label>
                    <input id="direccion_entrega" type="text" maxlength="180">
                </div>

            </div>

            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" rows="4" maxlength="500"></textarea>

        </section>

        <section class="panel-pedido">

            <h3>Categorias</h3>

            <div id="categorias" class="categorias">
                <p>Cargando categorias...</p>
            </div>

        </section>

        <section class="panel-pedido">

            <h3>Productos</h3>

            <div id="productos">
                <p>Selecciona una categoria.</p>
            </div>

        </section>

    </div>

    <aside class="pedido-derecha">

        <div class="carrito-titulo">
            <h3>Carrito</h3>
            <strong id="totalCarrito">$0</strong>
        </div>

        <div id="carrito">
            No hay productos.
        </div>

        <button id="confirmarPedido" class="boton-confirmar" type="button">
            Confirmar Pedido
        </button>

    </aside>

</div>

<script src="/genesisbar1/js/pedidos.js?v=3"></script>

<?php
require_once("../includes/footer.php");
?>
