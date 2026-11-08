/**
 * ESS-Lavadero - Búsqueda por placa con autocompletado (RF-02).
 *
 * Mientras se escribe la placa se consulta al servidor:
 *     index.php?c=vehiculo&a=buscar&placa=ABC123   ->   JSON
 *
 * Dos usos:
 *  1. data-placa-consulta="#destino"  (lista de vehículos)
 *     Muestra de quién es el vehículo, o un enlace para registrarlo.
 *  2. data-placa-aviso="#destino"     (formulario de vehículo)
 *     Avisa si la placa ya está registrada, antes de guardar.
 *
 * El texto que llega del servidor se inserta con textContent (nunca con
 * innerHTML), así un nombre con etiquetas HTML no se ejecuta (XSS).
 */
(function () {
    'use strict';

    /** Deja solo letras y números en mayúscula: "abc-123" -> "ABC123" */
    const normalizar = (texto) => texto.toUpperCase().replace(/[^A-Z0-9]/g, '');

    /** "ABC123" -> "ABC 123" */
    const formatear = (placa) => placa.length === 6 ? placa.slice(0, 3) + ' ' + placa.slice(3) : placa;

    /** "3104567821" -> "310 456 7821" */
    const telefono = (t) => t && t.length === 10 ? t.slice(0, 3) + ' ' + t.slice(3, 6) + ' ' + t.slice(6) : (t || '');

    /** Crea un elemento con clase y texto (seguro frente a XSS) */
    function crear(etiqueta, clase, texto) {
        const el = document.createElement(etiqueta);
        if (clase) el.className = clase;
        if (texto !== undefined) el.textContent = texto;
        return el;
    }

    /** Espera a que el usuario deje de escribir (300 ms) antes de consultar */
    function alEscribir(campo, accion) {
        let temporizador = null;
        campo.addEventListener('input', () => {
            const posicion = campo.selectionStart;
            campo.value = campo.value.toUpperCase();
            campo.setSelectionRange(posicion, posicion);
            clearTimeout(temporizador);
            temporizador = setTimeout(() => accion(normalizar(campo.value)), 300);
        });
    }

    /** Consulta la placa en el servidor. Devuelve el JSON o null si falla. */
    async function consultar(url, placa) {
        try {
            const respuesta = await fetch(url + '&placa=' + encodeURIComponent(placa), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!respuesta.ok) return null;
            return await respuesta.json();
        } catch (error) {
            return null;
        }
    }

    // ---------- 1. Consulta rápida (lista de vehículos) ----------
    document.querySelectorAll('[data-placa-consulta]').forEach((campo) => {
        const destino = document.querySelector(campo.dataset.placaConsulta);
        const textoInicial = destino.textContent;

        alEscribir(campo, async (placa) => {
            destino.replaceChildren();
            if (placa.length < 5) {
                destino.textContent = textoInicial;
                return;
            }

            const datos = await consultar(campo.dataset.url, placa);
            if (!datos) {
                destino.textContent = 'No se pudo consultar. Intente de nuevo.';
                return;
            }

            if (datos.encontrado) {
                const v = datos.vehiculo;
                const c = datos.cliente;
                const tarjeta = crear('div', 'resultado-encontrado');
                tarjeta.append(
                    crear('div', 'placa fs-5', formatear(v.placa)),
                    crear('div', 'small text-secondary',
                        [v.tipo, v.marca, v.color].filter(Boolean).join(' · ')),
                    crear('div', 'fw-semibold mt-1', c.nombre + ' — ' + telefono(c.telefono))
                );
                const enlace = crear('a', 'btn btn-sm btn-ess mt-2', 'Ver cliente');
                enlace.href = campo.dataset.urlCliente + '&id=' + c.id;
                tarjeta.append(enlace);
                destino.append(tarjeta);
            } else {
                destino.append(crear('span', '', 'La placa ' + formatear(placa) + ' no está registrada. '));
                const enlace = crear('a', 'fw-semibold', 'Registrar vehículo');
                enlace.href = campo.dataset.urlNuevo + '&placa=' + encodeURIComponent(placa);
                destino.append(enlace);
            }
        });
    });

    // ---------- 2. Aviso de placa repetida (formulario de vehículo) ----------
    document.querySelectorAll('[data-placa-aviso]').forEach((campo) => {
        const destino = document.querySelector(campo.dataset.placaAviso);
        const excluirId = parseInt(campo.dataset.excluirId || '0', 10);

        alEscribir(campo, async (placa) => {
            destino.replaceChildren();
            if (placa.length < 5) return;

            const datos = await consultar(campo.dataset.url, placa);
            if (!datos || !datos.encontrado || datos.vehiculo.id === excluirId) return;

            const aviso = crear('div', 'aviso aviso-alerta mt-2');
            aviso.append(crear('span', '',
                'Esta placa ya está registrada a nombre de ' + datos.cliente.nombre + '. '));
            const enlace = crear('a', '', 'Abrir ese vehículo');
            enlace.href = campo.dataset.urlEditar + '&id=' + datos.vehiculo.id;
            aviso.append(enlace);
            destino.append(aviso);
        });
    });
})();
