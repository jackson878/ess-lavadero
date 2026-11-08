/**
 * ESS-Lavadero - JavaScript común a todas las páginas.
 *
 * 1. Búsqueda en tablas:
 *      <input data-buscar="#tablaUsuarios">
 *    filtra las filas del <tbody> de esa tabla mientras se escribe.
 *
 * 2. Confirmación antes de enviar un formulario:
 *      <form data-confirmar="¿Seguro que desea desactivar este usuario?">
 *
 * 3. Mostrar / ocultar bloques con radios:
 *      <input type="radio" data-mostrar="#bloqueA" data-ocultar="#bloqueB">
 *
 * 4. Formato de pesos mientras se escribe:
 *      <input data-formato-pesos>   25000 -> 25.000
 */
(function () {
    'use strict';

    // Quita tildes y pasa a minúsculas: "Rodríguez" -> "rodriguez"
    const normalizar = (texto) =>
        texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

    // ---------- 1. Búsqueda en tablas ----------
    document.querySelectorAll('[data-buscar]').forEach((campo) => {
        const tabla = document.querySelector(campo.dataset.buscar);
        if (!tabla) return;

        const filas = Array.from(tabla.querySelectorAll('tbody tr'));
        const columnas = tabla.querySelectorAll('thead th').length || 1;

        // Fila que se muestra cuando no hay coincidencias
        const sinResultados = document.createElement('tr');
        sinResultados.innerHTML = '<td colspan="' + columnas + '" class="text-center text-secondary py-4">' +
            'No hay resultados para la búsqueda.</td>';
        sinResultados.hidden = true;
        tabla.querySelector('tbody').appendChild(sinResultados);

        campo.addEventListener('input', () => {
            const buscado = normalizar(campo.value.trim());
            let visibles = 0;
            filas.forEach((fila) => {
                const coincide = normalizar(fila.textContent).includes(buscado);
                fila.hidden = !coincide;
                if (coincide) visibles++;
            });
            sinResultados.hidden = visibles > 0;
        });
    });

    // ---------- 2. Confirmaciones ----------
    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });

    // ---------- 3. Mostrar / ocultar bloques ----------
    document.querySelectorAll('input[type="radio"][data-mostrar]').forEach((radio) => {
        radio.addEventListener('change', () => {
            if (!radio.checked) return;
            document.querySelectorAll(radio.dataset.mostrar).forEach((el) => { el.hidden = false; });
            if (radio.dataset.ocultar) {
                document.querySelectorAll(radio.dataset.ocultar).forEach((el) => { el.hidden = true; });
            }
        });
    });

    // ---------- 4. Formato de pesos ----------
    document.querySelectorAll('[data-formato-pesos]').forEach((campo) => {
        campo.addEventListener('input', () => {
            const digitos = campo.value.replace(/\D/g, '');
            campo.value = digitos ? Number(digitos).toLocaleString('es-CO') : '';
        });
    });
})();
