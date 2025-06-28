setTimeout(() => {
    const mensaje = document.getElementById('mensaje');
    if (mensaje) mensaje.style.display = 'none';
}, 10000); // 10 segundos


document.addEventListener('DOMContentLoaded', () => {
    const nombre = document.getElementById('nombre');
    const obraLibre = document.getElementById('obra_libre');
    const btnAgregar = document.getElementById('btn-agregar-integrante');
    const contenedor = document.getElementById('integrantes-container');
    const template = document.getElementById('integrante-template');
    const btnAgregarSuplente = document.getElementById('btn-agregar-suplente');
    const botonesFinales = document.getElementById('botones-finales');

    let contador = 0;
    let maxIntegrantes = 4;
    let tieneSuplente = false;

    const actualizarBoton = () => {
        btnAgregar.disabled = !(nombre.value.trim() && obraLibre.value.trim());
    };

    nombre.addEventListener('input', actualizarBoton);
    obraLibre.addEventListener('input', actualizarBoton);

    btnAgregar.addEventListener('click', () => {
        if (contador >= maxIntegrantes) return;

        const clone = template.content.cloneNode(true);
        const num = contador + 1;

 

        clone.querySelector('.integrante-card').style.display = 'none';

        clone.querySelectorAll('input, select').forEach(el => {
            el.name = el.name.replace('__INDEX__', num);
        });
        clone.querySelector('.num').textContent = num;

        contenedor.appendChild(clone);

        const card = contenedor.lastElementChild;
        slideDown(card, 400);

        contador++;

        if (contador === maxIntegrantes) {
            botonesFinales.style.display = 'flex';
        }
    });

    btnAgregarSuplente.addEventListener('click', () => {
        if (tieneSuplente) return;

        const clone = template.content.cloneNode(true);
        const num = 'S';

        const dniInput = clone.querySelector('.dni-input');
        dniInput.addEventListener('input', () => validarDNIenVivo(dniInput));


        clone.querySelector('.integrante-card').style.display = 'none';

        clone.querySelectorAll('input, select').forEach(el => {
            el.name = el.name.replace('__INDEX__', num);
        });
        clone.querySelector('.num').textContent = 'Suplente';

        contenedor.appendChild(clone);

        const card = contenedor.lastElementChild;
        slideDown(card, 400);

        tieneSuplente = true;
    });

    function slideDown(el, duration = 400) {
        el.style.removeProperty('display');
        let display = window.getComputedStyle(el).display;
        if (display === 'none') display = 'block';
        el.style.display = display;

        let height = el.offsetHeight;
        el.style.overflow = 'hidden';
        el.style.height = 0;
        el.offsetHeight; // force reflow
        el.style.transition = `height ${duration}ms ease-out`;
        el.style.height = height + 'px';

        setTimeout(() => {
            el.style.removeProperty('height');
            el.style.removeProperty('overflow');
            el.style.removeProperty('transition');
        }, duration);
    }
});

document.addEventListener("input", function (e) {
    if (e.target.name.startsWith("dni")) {
        e.target.value = e.target.value.replace(/\D/g, "").slice(0, 8);
    }
});

document.getElementById('nombre').addEventListener('input', function () {
    const nombreInput = this;
    const mensajeError = document.getElementById('nombre-error');
    const btnAgregar = document.getElementById('btn-agregar-integrante');

    const nombre = nombreInput.value.trim();
    if (nombre.length < 3) {
        mensajeError.textContent = '';
        btnAgregar.disabled = true;
        return;
    }

    fetch(`validar_nombre.php?nombre=${encodeURIComponent(nombre)}`)
        .then(res => res.text())
        .then(data => {
            if (data === 'existe') {
                mensajeError.textContent = 'Este nombre ya está en uso. Prueba con otro.';
                btnAgregar.disabled = true;
            } else {
                mensajeError.textContent = '';
                if (nombre !== '' && document.getElementById('obra_libre').value.trim() !== '') {
                    btnAgregar.disabled = false;
                }
            }
        });
});

document.getElementById('obra_libre').addEventListener('input', function () {
    const nombre = document.getElementById('nombre').value.trim();
    const obra = this.value.trim();
    const mensajeError = document.getElementById('nombre-error');
    const btnAgregar = document.getElementById('btn-agregar-integrante');

    if (nombre !== '' && obra !== '' && mensajeError.textContent === '') {
        btnAgregar.disabled = false;
    } else {
        btnAgregar.disabled = true;
    }
});


document.addEventListener('DOMContentLoaded', () => {
    const contenedor = document.getElementById('integrantes-container');

    // Escuchar cambios en instrumentos y en DNIs usando delegación de eventos
    contenedor.addEventListener('input', (e) => {
        // Validación en vivo del DNI
        if (e.target.classList.contains('dni-input')) {
            const input = e.target;
            const dni = input.value.replace(/\D/g, "").slice(0, 8); // solo números
            input.value = dni;

            const errorSpan = input.closest('.integrante-card').querySelector('.dni-error');

            if (dni.length < 8) {
                errorSpan.textContent = 'El DNI debe tener 8 dígitos.';
                return;
            }

            fetch(`validar_dni.php?dni=${dni}`)
                .then(res => res.text())
                .then(data => {
                    if (data === 'repetido') {
                        errorSpan.textContent = 'Este DNI ya está registrado en otro cuarteto.';
                    } else if (data === 'ok') {
                        errorSpan.textContent = '';
                    } else if (data === 'invalido') {
                        errorSpan.textContent = 'Formato de DNI inválido.';
                    } else {
                        errorSpan.textContent = '';
                    }
                });
        }

        // Validación de instrumentos en tiempo real
        if (e.target.tagName === 'SELECT') {
            validarFormacionInstrumental();
        }
    });
});

function validarFormacionInstrumental() {
    const selects = document.querySelectorAll('#integrantes-container select');
    let violin = 0, viola = 0, chelo = 0, reemplazo = 0;

    selects.forEach(select => {
        const val = select.value.toLowerCase();
        if (val === 'violín' || val === 'violin') violin++;
        else if (val === 'viola') viola++;
        else if (val === 'violonchelo' || val === 'chelo') chelo++;
        else if (['flauta', 'arpa', 'piano'].includes(val)) reemplazo++;
    });

    const totalViolines = violin + reemplazo;
    const errores = [];

    if (totalViolines !== 2) {
        errores.push('Debe haber exactamente 2 violines o reemplazos (flauta, arpa o piano).');
    }
    if (viola !== 1) {
        errores.push('Debe haber exactamente 1 viola.');
    }
    if (chelo !== 1) {
        errores.push('Debe haber exactamente 1 chelo.');
    }

    const divError = document.getElementById('error-formacion');
    divError.textContent = errores.length > 0 ? errores.join(' ') : '';
}

function validarDNIenVivo(input) {
    const dni = input.value.replace(/\D/g, "").slice(0, 8); // solo números
    input.value = dni;

    const errorSpan = input.closest('.integrante-card').querySelector('.dni-error');

    if (dni.length < 8) {
        errorSpan.textContent = 'El DNI debe tener 8 dígitos.';
        return;
    }

    fetch(`validar_dni.php?dni=${dni}`)
        .then(res => res.text())
        .then(data => {
            if (data === 'repetido') {
                errorSpan.textContent = 'Este DNI ya está registrado en otro cuarteto.';
            } else if (data === 'ok') {
                errorSpan.textContent = '';
            } else if (data === 'invalido') {
                errorSpan.textContent = 'Formato de DNI inválido.';
            } else {
                errorSpan.textContent = '';
            }
        });
}
