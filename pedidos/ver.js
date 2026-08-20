document.addEventListener("DOMContentLoaded", function () {
    const acciones = document.querySelector(".acciones-estado");

    if (!acciones) {
        return;
    }

    acciones.addEventListener("click", async function (evento) {
        const boton = evento.target.closest("button[data-estado]");

        if (!boton || boton.disabled) {
            return;
        }

        const mensaje = document.getElementById("mensajeVerPedido");
        const idPedido = Number(acciones.dataset.idPedido);
        const estado = boton.dataset.estado;

        if (estado === "Entregado" && !confirm("Confirmar que el pedido fue entregado.")) {
            return;
        }

        boton.disabled = true;
        mensaje.textContent = "Actualizando estado...";
        mensaje.className = "mensaje-pedido info";

        try {
            const respuesta = await fetch("actualizar_estado.php", {
                method: "POST",
                headers: {"Content-Type": "application/json", "Accept": "application/json"},
                body: JSON.stringify({
                    id_pedido: idPedido,
                    estado,
                    csrf_token: document.getElementById("csrfPedidoDetalle").value
                })
            });
            const datos = await respuesta.json();

            if (!datos.ok) {
                throw new Error(datos.mensaje || "No se pudo actualizar el estado.");
            }

            mensaje.textContent = datos.mensaje;
            mensaje.className = "mensaje-pedido exito";
            window.setTimeout(function () {
                window.location.reload();
            }, 500);
        } catch (error) {
            mensaje.textContent = error.message;
            mensaje.className = "mensaje-pedido error";
            boton.disabled = false;
        }
    });
});
