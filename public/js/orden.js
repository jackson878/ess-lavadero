/**
 * ESS-Lavadero - Formulario "Nuevo servicio" (RF-04, RNF-04).
 *
 *  - Al escribir la placa consulta el servidor (?c=vehiculo&a=buscar):
 *      · si existe, muestra el vehículo y su dueño y oculta los campos de vehículo nuevo;
 *      · si no existe, muestra los campos para registrar vehículo y cliente.
 *  - Muestra solo los servicios del tipo de vehículo (carro o moto).
 *  - Calcula el total y el tiempo estimado mientras se eligen servicios.
 *    (Es solo informativo: el total real lo calcula el servidor.)
 */
(function () {
    'use strict';

    const form = document.getElementById('formOrden');
    if (!form) return;

    const campoPlaca = document.getElementById('placa');
    const cajaEncontrado = document.getElementById('vehiculoEncontrado');
    const cajaNuevo = document.getElementById('vehiculoNuevo');
    const resumenVehiculo = document.getElementById('resumenVehiculo');
    const resumenServicios = document.getElementById('resumenServicios');
    const resumenTotal = document.getElementById('resumenTotal');
    const resumenTiempo = document.getElementById('resumenTiempo');

    const normalizar = (t) => t.toUpperCase().replace(/[^A-Z0-9]/g, '');
    const formatearPlaca = (p) => p.length === 6 ? p.slice(0, 3) + ' ' + p.slice(3) : p;
    const pesos = (v) => '$ ' + Math.round(v).toLocaleString('es-CO');
    const duracion = (m) => m < 60 ? m + ' min' : Math.floor(m / 60) + ' h' + (m % 60 ? ' ' + (m % 60) + ' min' : '');
    const telefono = (t) => t && t.length === 10 ? t.slice(0, 3) + ' ' + t.slice(3, 6) + ' ' + t.slice(6) : (t || '');

    function crear(etiqueta, clase, texto) {
        const el = document.createElement(etiqueta);
        if (clase) el.className = clase;
        if (texto !== undefined) el.textContent = texto;
        return el;
    }

    /** Tipo de vehículo elegido (carro / moto) */
    const tipoActual = () => (form.querySelector('input[name="tipo"]:checked') || {}).value || 'carro';

    /** Muestra solo los servicios del tipo y desmarca los del otro tipo */
    function filtrarServicios() {
        const tipo = tipoActual();
        form.querySelectorAll('[data-tipo-servicio]').forEach((col) => {
            const visible = col.dataset.tipoServicio === tipo;
            col.hidden = !visible;
            if (!visible) col.querySelector('input').checked = false;
        });
        actualizarResumen();
    }

    /** Total y tiempo estimado según los servicios marcados */
    function actualizarResumen() {
        let total = 0;
        let minutos = 0;
        resumenServicios.replaceChildren();
        form.querySelectorAll('input[name="servicios[]"]:checked').forEach((chk) => {
            total += Number(chk.dataset.precio);
            minutos += Number(chk.dataset.duracion);
            const li = crear('li', 'd-flex justify-content-between');
            li.append(crear('span', '', chk.nextElementSibling.querySelector('.fw-bold').textContent),
                      crear('span', '', pesos(chk.dataset.precio)));
            resumenServicios.append(li);
        });
        resumenTotal.textContent = pesos(total);
        resumenTiempo.textContent = 'Tiempo estimado: ' + (minutos ? duracion(minutos) : '—');
    }

    /** Marca el tipo de vehículo (carro / moto) */
    function elegirTipo(tipo) {
        const radio = document.getElementById('tipo-' + tipo);
        if (radio && !radio.checked) {
            radio.checked = true;
        }
        filtrarServicios();
    }

    /** Pinta la tarjeta del vehículo encontrado */
    function mostrarEncontrado(datos) {
        const v = datos.vehiculo;
        const c = datos.cliente;
        cajaEncontrado.replaceChildren(
            crear('div', 'placa fs-4', formatearPlaca(v.placa)),
            crear('div', 'text-secondary', [v.tipo, v.marca, v.color].filter(Boolean).join(' · ')),
            crear('div', 'fw-semibold mt-1', c.nombre + ' — ' + telefono(c.telefono))
        );
        // Aviso inmediato si el vehículo ya tiene una orden sin entregar
        if (datos.orden_activa) {
            cajaEncontrado.append(crear('div', 'text-danger fw-semibold mt-2',
                'Este vehículo ya está en el lavadero (orden #' + datos.orden_activa + ').'));
        }
        cajaEncontrado.hidden = false;
        cajaNuevo.hidden = true;
        resumenVehiculo.textContent = formatearPlaca(v.placa) + ' · ' + c.nombre;
        elegirTipo(v.tipo);
    }

    /** Placa no registrada: se piden los datos del vehículo y del cliente */
    function mostrarNuevo(placa) {
        cajaEncontrado.hidden = true;
        cajaNuevo.hidden = false;
        resumenVehiculo.textContent = formatearPlaca(placa) + ' · vehículo nuevo';
        filtrarServicios();
    }

    let temporizador = null;
    let ultimaConsulta = '';

    async function buscarPlaca() {
        const placa = normalizar(campoPlaca.value);
        if (placa === ultimaConsulta) return;
        ultimaConsulta = placa;

        if (placa.length < 5) {
            cajaEncontrado.hidden = true;
            cajaNuevo.hidden = true;
            resumenVehiculo.textContent = 'Escriba la placa…';
            return;
        }

        try {
            const respuesta = await fetch(campoPlaca.dataset.url + '&placa=' + encodeURIComponent(placa), {
                headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
            });
            const datos = await respuesta.json();
            if (normalizar(campoPlaca.value) !== placa) return;   // el usuario siguió escribiendo
            datos.encontrado ? mostrarEncontrado(datos) : mostrarNuevo(placa);
        } catch (e) {
            resumenVehiculo.textContent = 'No se pudo consultar la placa.';
        }
    }

    campoPlaca.addEventListener('input', () => {
        const pos = campoPlaca.selectionStart;
        campoPlaca.value = campoPlaca.value.toUpperCase();
        campoPlaca.setSelectionRange(pos, pos);
        clearTimeout(temporizador);
        temporizador = setTimeout(buscarPlaca, 300);
    });

    form.querySelectorAll('input[name="tipo"]').forEach((r) => r.addEventListener('change', filtrarServicios));
    form.querySelectorAll('input[name="servicios[]"]').forEach((c) => c.addEventListener('change', actualizarResumen));

    // Evita registrar dos veces por doble clic
    form.addEventListener('submit', () => {
        const boton = form.querySelector('button[type="submit"]');
        setTimeout(() => { boton.disabled = true; boton.textContent = 'Registrando…'; }, 0);
    });

    // Al volver con errores (o con ?placa=), se repite la búsqueda para restaurar el estado
    actualizarResumen();
    if (campoPlaca.value) buscarPlaca();
})();
