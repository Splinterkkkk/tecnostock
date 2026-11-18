/**
 * Validaciones del lado del cliente (respuesta inmediata al usuario).
 * Las mismas reglas se repiten en PHP (includes/funciones.php), porque
 * JavaScript se puede desactivar o saltar: el servidor tiene la última palabra.
 *
 * Uso en el HTML:
 *   <form class="needs-validation" novalidate> ...
 *   <input data-regla="entero" data-etiqueta="El stock actual" required>
 *   Reglas: texto | codigo | email | decimal | entero | cantidad
 */
(function () {
    'use strict';

    /* -------------------- Reglas por campo -------------------- */

    function validarCampo(campo) {
        var valor = campo.value.trim();
        var etiqueta = campo.dataset.etiqueta || 'Este campo';

        // Valor no numérico escrito en un <input type="number">
        if (campo.validity && campo.validity.badInput) {
            return etiqueta + ' debe ser un número válido.';
        }
        if (campo.required && valor === '') {
            return etiqueta + ' es un dato obligatorio.';
        }
        if (valor === '') {
            return '';   // campo opcional vacío
        }

        // Largos mínimo y máximo (atributos minlength / maxlength)
        if (campo.minLength > 0 && valor.length < campo.minLength) {
            return etiqueta + ' debe tener al menos ' + campo.minLength + ' caracteres.';
        }
        if (campo.maxLength > 0 && valor.length > campo.maxLength) {
            return etiqueta + ' no puede superar los ' + campo.maxLength + ' caracteres.';
        }

        switch (campo.dataset.regla) {
            case 'codigo':
                if (!/^[A-Za-z0-9_-]{2,30}$/.test(valor)) {
                    return 'Use de 2 a 30 caracteres: letras, números, guion o guion bajo.';
                }
                break;
            case 'email':
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor)) {
                    return 'Ingresa un correo válido.';
                }
                break;
            case 'decimal':   // precio: no negativo, máximo 2 decimales
                if (!/^\d{1,8}([.,]\d{1,2})?$/.test(valor)) {
                    return etiqueta + ' debe ser un número mayor o igual a 0 (máximo 2 decimales).';
                }
                break;
            case 'entero':    // stock: entero no negativo
                if (!/^\d{1,9}$/.test(valor)) {
                    return etiqueta + ' debe ser un número entero mayor o igual a 0.';
                }
                break;
            case 'cantidad':  // movimiento: entero mayor que 0
                if (!/^\d{1,9}$/.test(valor) || parseInt(valor, 10) < 1) {
                    return etiqueta + ' debe ser un número entero mayor que 0.';
                }
                if (parseInt(valor, 10) > 1000000) {
                    return etiqueta + ' no puede superar 1.000.000 de unidades.';
                }
                break;
        }
        return '';
    }

    /* -------------------- Mostrar / quitar errores -------------------- */

    function mostrarError(campo, mensaje) {
        campo.classList.toggle('is-invalid', mensaje !== '');
        campo.classList.toggle('is-valid', mensaje === '' && campo.value.trim() !== '');
        var aviso = campo.parentElement.querySelector('.invalid-feedback');
        if (aviso) {
            aviso.textContent = mensaje;
        }
    }

    /* -------------------- Regla extra: salida vs. stock -------------------- */

    // En el formulario de movimientos, una salida no puede superar el stock disponible.
    function validarStockMovimiento(form) {
        var selectProducto = form.querySelector('#id_producto');
        var selectTipo = form.querySelector('#tipo');
        var cantidad = form.querySelector('#cantidad');
        var info = form.querySelector('#stock-info');
        if (!selectProducto || !selectTipo || !cantidad) {
            return '';
        }

        var opcion = selectProducto.options[selectProducto.selectedIndex];
        var stock = opcion && opcion.dataset.stock !== undefined ? parseInt(opcion.dataset.stock, 10) : null;

        if (info) {
            info.textContent = stock === null || isNaN(stock)
                ? ''
                : 'Stock disponible: ' + stock + ' unidades.';
        }

        var valor = parseInt(cantidad.value, 10);
        if (selectTipo.value === 'SALIDA' && stock !== null && !isNaN(valor) && valor > stock) {
            return 'Stock insuficiente: hay ' + stock + ' unidades disponibles y se intentan sacar ' + valor + '.';
        }
        return '';
    }

    /* -------------------- Conexión con los formularios -------------------- */

    function validarFormulario(form) {
        var primerError = null;

        form.querySelectorAll('[data-regla]').forEach(function (campo) {
            var mensaje = validarCampo(campo);
            if (mensaje === '' && campo.id === 'cantidad') {
                mensaje = validarStockMovimiento(form);
            }
            mostrarError(campo, mensaje);
            if (mensaje !== '' && primerError === null) {
                primerError = campo;
            }
        });

        // Listas desplegables sin data-regla pero obligatorias
        form.querySelectorAll('select[required]:not([data-regla])').forEach(function (select) {
            var mensaje = select.value === '' ? (select.dataset.etiqueta || 'Este campo') + ' es un dato obligatorio.' : '';
            mostrarError(select, mensaje);
            if (mensaje !== '' && primerError === null) {
                primerError = select;
            }
        });

        return primerError;
    }

    document.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            var primerError = validarFormulario(form);
            if (primerError) {
                evento.preventDefault();
                evento.stopPropagation();
                primerError.focus();
                return;
            }
            // Evita enviar dos veces el mismo formulario
            var boton = form.querySelector('button[type="submit"]');
            if (boton) {
                boton.disabled = true;
            }
        });

        // Validación de un campo (incluye la regla de stock en la cantidad de un movimiento)
        function revalidar(campo) {
            var mensaje = validarCampo(campo);
            if (mensaje === '' && campo.id === 'cantidad') {
                mensaje = validarStockMovimiento(form);
            }
            mostrarError(campo, mensaje);
        }

        // Nota de diseño: no se valida al salir de un campo (blur). Si el mensaje de error
        // aparece justo cuando el usuario hace clic en el botón, el botón se desplaza y el
        // clic se pierde. Por eso se valida (1) al enviar, (2) al elegir en una lista y
        // (3) mientras se escribe, solo en campos que ya tenían un error.
        form.querySelectorAll('[data-regla], select[required]').forEach(function (campo) {
            if (campo.tagName === 'SELECT') {
                campo.addEventListener('change', function () { revalidar(campo); });
            } else {
                campo.addEventListener('input', function () {
                    if (campo.classList.contains('is-invalid')) {
                        revalidar(campo);
                    }
                });
            }
        });

        // En movimientos: al cambiar producto, tipo o cantidad se revisa el stock disponible
        var cantidad = form.querySelector('#cantidad');
        if (cantidad) {
            var revisarStock = function () {
                var mensaje = validarCampo(cantidad);
                if (mensaje === '') {
                    mensaje = validarStockMovimiento(form);
                } else {
                    validarStockMovimiento(form);   // solo actualiza el texto de stock
                }
                if (cantidad.value.trim() !== '') {
                    mostrarError(cantidad, mensaje);
                }
            };
            ['#id_producto', '#tipo'].forEach(function (selector) {
                var elemento = form.querySelector(selector);
                if (elemento) {
                    elemento.addEventListener('change', revisarStock);
                }
            });
            cantidad.addEventListener('input', revisarStock);
            validarStockMovimiento(form);   // muestra el stock del producto preseleccionado
        }
    });

    // Si el usuario vuelve atrás con el navegador, los botones quedan habilitados otra vez.
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form.needs-validation button[type="submit"]').forEach(function (boton) {
            boton.disabled = false;
        });
    });

    /* -------------------- Confirmaciones -------------------- */

    // <form data-confirmar="¿Seguro?"> pide confirmación antes de enviar.
    document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            if (!window.confirm(form.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });
})();
