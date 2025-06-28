document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const usuario = document.getElementById('usuario').value.trim();
    const clave = document.getElementById('clave').value.trim();
    const errorClave = document.getElementById('errorClave');
    const errorGeneral = document.getElementById('errorGeneral');

    errorClave.textContent = '';
    errorGeneral.textContent = '';

    if (clave.indexOf(' ') !== -1) {
        errorClave.textContent = '❌ La contraseña no debe contener espacios.';
        return;
    }

    fetch('procesar_login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'usuario=' + encodeURIComponent(usuario) + '&clave=' + encodeURIComponent(clave)
    })
    .then(response => response.text())
    .then(data => {
        data = data.trim();
        if (data === 'administrador') {
            window.location.href = 'admin.php';
        } else if (data === 'profesor') {
            window.location.href = 'profesor.php';
        } else if (data === 'jurado') {
            window.location.href = 'jurado.php';
        } else if (data === 'alumno') {
            window.location.href = 'alumno.php';
        } else {
            errorGeneral.textContent = '❌ Usuario o contraseña incorrectos.';
        }
    })
    .catch(error => {
        errorGeneral.textContent = '❌ Error de conexión. Intenta de nuevo.';
    });
});

document.getElementById('togglePassword').addEventListener('click', function () {
    const claveInput = document.getElementById('clave');
    const tipo = claveInput.getAttribute('type') === 'password' ? 'text' : 'password';
    claveInput.setAttribute('type', tipo);
    this.innerHTML = tipo === 'password' ? '&#128065;' : '&#128584;';
});

window.onload = function () {
    document.getElementById('usuario').value = '';
    document.getElementById('clave').value = '';
};
