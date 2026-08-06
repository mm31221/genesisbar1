//=========================================
// GENESISBAR 1.0
// caja.js
//=========================================

// Cuando carga la página
document.addEventListener("DOMContentLoaded", function () {

    actualizarPedidos();

    // Actualizar la lista cada 10 segundos
    setInterval(actualizarPedidos, 10000);

});

//=========================================
// ACTUALIZAR PEDIDOS
//=========================================

function actualizarPedidos() {

    let contenedor = document.getElementById("contenedorPedidos");

    if (contenedor == null) {
        return;
    }

    fetch("ajax/pedidos.php")

    .then(function(respuesta){

        return respuesta.text();

    })

    .then(function(html){

        contenedor.innerHTML = html;

    })

    .catch(function(error){

        console.log(error);

    });

}

//=========================================
// CONFIRMAR COBRO
//=========================================

function confirmarCobro(){

    return confirm("¿Confirmar el cobro del pedido?");

}