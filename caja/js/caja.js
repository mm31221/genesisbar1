document.addEventListener("DOMContentLoaded", function () {
    const contenedor = document.getElementById("contenedorPedidos");
    const actualizado = document.getElementById("cajaActualizado");
    const botonActualizar = document.getElementById("actualizarCaja");
    const botonInicioCaja = document.getElementById("mostrarInicioCaja");
    const formInicioCaja = document.getElementById("formInicioCaja");
    const formCobro = document.getElementById("formCobro");
    const formaPago1 = document.getElementById("id_forma_pago_1");
    const formaPago2 = document.getElementById("id_forma_pago_2");
    const montoPago1 = document.getElementById("monto_pago_1");
    const montoPago2 = document.getElementById("monto_pago_2");
    const grupoPago2 = document.getElementById("grupoPago2");
    const habilitarPagoDividido = document.getElementById("habilitarPagoDividido");
    const grupoEfectivo = document.getElementById("grupoEfectivo");
    const dineroRecibido = document.getElementById("dinero_recibido");
    const vuelto = document.getElementById("vuelto");
    const totalPedido = document.getElementById("totalPedido");
    const resumenPago1 = document.getElementById("resumenPago1");
    const resumenPago2 = document.getElementById("resumenPago2");
    const resumenPagado = document.getElementById("resumenPagado");
    const resumenSaldo = document.getElementById("resumenSaldo");

    if (botonInicioCaja && formInicioCaja) {
        botonInicioCaja.addEventListener("click", function () {
            formInicioCaja.hidden = false;
            botonInicioCaja.hidden = true;
        });
    }

    async function actualizarPedidos() {
        if (!contenedor) {
            return;
        }

        try {
            const respuesta = await fetch("/genesisbar1/caja/ajax/pedidos.php", {
                headers: {"X-Requested-With": "fetch"}
            });
            contenedor.innerHTML = await respuesta.text();

            if (actualizado) {
                actualizado.textContent = "Actualizado " + new Date().toLocaleTimeString("es-AR", {
                    hour: "2-digit",
                    minute: "2-digit",
                    second: "2-digit"
                });
            }
        } catch (error) {
            if (actualizado) {
                actualizado.textContent = "No se pudo actualizar Caja";
            }
        }
    }

    function moneda(valor) {
        return "$" + Number(valor || 0).toLocaleString("es-AR", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    function formaSeleccionadaEsEfectivo(select) {
        if (!select || !select.value) {
            return false;
        }

        const opcion = select.options[select.selectedIndex];
        return opcion && opcion.dataset.efectivo === "1";
    }

    function segundoPagoActivo() {
        return grupoPago2 && !grupoPago2.hidden;
    }

    function montoSegundoPago() {
        return segundoPagoActivo() ? Number(montoPago2 ? montoPago2.value || 0 : 0) : 0;
    }

    function montoPrincipalCalculado() {
        const total = Number(totalPedido ? totalPedido.value || 0 : 0);
        return Math.max(0, total - montoSegundoPago());
    }

    function totalEfectivo(pago1, pago2) {
        let total = 0;

        if (formaSeleccionadaEsEfectivo(formaPago1)) {
            total += pago1;
        }

        if (formaSeleccionadaEsEfectivo(formaPago2)) {
            total += pago2;
        }

        return total;
    }

    function actualizarCampoEfectivo(efectivo) {
        if (!grupoEfectivo || !dineroRecibido) {
            return;
        }

        grupoEfectivo.hidden = efectivo <= 0;
        dineroRecibido.disabled = efectivo <= 0;

        if (efectivo <= 0) {
            dineroRecibido.value = "0";
        }
    }

    function calcularPago() {
        if (!totalPedido || !resumenPagado) {
            return;
        }

        const pago2 = montoSegundoPago();
        const pago1 = montoPrincipalCalculado();
        const efectivo = totalEfectivo(pago1, pago2);
        const recibido = Number(dineroRecibido ? dineroRecibido.value || 0 : 0);
        const total = Number(totalPedido.value || 0);
        const pagado = pago1 + pago2;
        const saldo = Math.max(0, total - pagado);
        const vueltoCalculado = efectivo > 0 ? recibido - efectivo : 0;

        if (montoPago1) {
            montoPago1.value = pago1.toFixed(2);
        }

        actualizarCampoEfectivo(efectivo);

        if (resumenPago1) {
            resumenPago1.textContent = moneda(pago1);
        }

        if (resumenPago2) {
            resumenPago2.textContent = moneda(pago2);
        }

        resumenPagado.textContent = moneda(pagado);

        if (resumenSaldo) {
            resumenSaldo.textContent = moneda(saldo);
            resumenSaldo.classList.toggle("vuelto-negativo", saldo > 0);
        }

        if (vuelto) {
            vuelto.textContent = moneda(vueltoCalculado);
            vuelto.classList.toggle("vuelto-negativo", vueltoCalculado < 0);
        }
    }

    if (botonActualizar) {
        botonActualizar.addEventListener("click", actualizarPedidos);
        setInterval(actualizarPedidos, 10000);
    }

    if (habilitarPagoDividido && grupoPago2) {
        habilitarPagoDividido.addEventListener("click", function () {
            grupoPago2.hidden = false;
            habilitarPagoDividido.hidden = true;

            calcularPago();
        });
    }

    [formaPago1, formaPago2, montoPago1, montoPago2, dineroRecibido].forEach(function (input) {
        if (input) {
            input.addEventListener("input", calcularPago);
            input.addEventListener("change", calcularPago);
        }
    });

    calcularPago();

    if (formCobro) {
        formCobro.addEventListener("submit", function (evento) {
            calcularPago();

            const pago2 = montoSegundoPago();
            const pago1 = montoPrincipalCalculado();
            const efectivo = totalEfectivo(pago1, pago2);
            const recibido = Number(dineroRecibido ? dineroRecibido.value || 0 : 0);
            const total = Number(totalPedido.value || 0);
            const pagado = pago1 + pago2;

            if (!formaPago1 || formaPago1.value === "") {
                evento.preventDefault();
                alert("Selecciona una forma de pago.");
                return;
            }

            if (grupoPago2 && !grupoPago2.hidden && pago2 > 0 && (!formaPago2 || formaPago2.value === "")) {
                evento.preventDefault();
                alert("Selecciona la segunda forma de pago.");
                return;
            }

            if (grupoPago2 && !grupoPago2.hidden && formaPago2 && formaPago2.value !== "" && pago2 <= 0) {
                evento.preventDefault();
                alert("Carga el importe de la segunda forma de pago.");
                return;
            }

            if (pago1 < 0 || pago2 < 0 || recibido < 0) {
                evento.preventDefault();
                alert("Los importes no pueden ser negativos.");
                return;
            }

            if (pagado <= 0) {
                evento.preventDefault();
                alert("Carga al menos un importe de pago.");
                return;
            }

            if (pago2 >= total) {
                evento.preventDefault();
                alert("La segunda forma debe ser menor al total para dejar importe en la forma principal.");
                return;
            }

            if (Math.abs(pagado - total) > 0.01) {
                evento.preventDefault();
                alert("El pago combinado debe coincidir con el total del pedido.");
                return;
            }

            if (efectivo > 0 && recibido < efectivo) {
                evento.preventDefault();
                alert("El dinero recibido en efectivo no alcanza el monto en efectivo.");
                return;
            }

            if (!confirm("Confirmar el cobro del pedido. Esta accion no se puede repetir.")) {
                evento.preventDefault();
            }
        });
    }
});
