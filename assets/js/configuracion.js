const csrfTokenConfig = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

document.addEventListener('DOMContentLoaded', function() {
    cargarConfigEmpresa();
    cargarSucursales();
});

function cargarConfigEmpresa() {
    const config = JSON.parse(localStorage.getItem('secmautos_config') || '{}');
    document.getElementById('nombre_empresa').value = config.nombre_empresa || '';
    document.getElementById('direccion_empresa').value = config.direccion_empresa || '';
    document.getElementById('telefono_empresa').value = config.telefono_empresa || '';
    document.getElementById('email_empresa').value = config.email_empresa || '';
}

function guardarConfigEmpresa() {
    const nombre = document.getElementById('nombre_empresa').value;
    const direccion = document.getElementById('direccion_empresa').value;
    const telefono = document.getElementById('telefono_empresa').value;
    const email = document.getElementById('email_empresa').value;

    const config = { nombre_empresa: nombre, direccion_empresa: direccion, telefono_empresa: telefono, email_empresa: email };
    localStorage.setItem('secmautos_config', JSON.stringify(config));

    alert('✅ Configuración de empresa guardada correctamente');
    actualizarHeader();
}

function cargarSucursales() {
    const sucursales = JSON.parse(localStorage.getItem('secmautos_sucursales') || '[]');
    const lista = document.getElementById('sucursales-list');
    lista.innerHTML = '';

    if (sucursales.length === 0) {
        lista.innerHTML = '<li class="list-group-item text-muted">No hay sucursales configuradas</li>';
        return;
    }

    sucursales.forEach((sucursal, index) => {
        lista.innerHTML += `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                ${sucursal}
                <button class="btn btn-sm btn-danger" onclick="eliminarSucursal(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            </li>
        `;
    });
}

function agregarSucursal() {
    const input = document.getElementById('nueva-sucursal');
    const nombre = input.value.trim();

    if (!nombre) {
        alert('❌ Por favor, ingrese un nombre para la sucursal');
        return;
    }

    const sucursales = JSON.parse(localStorage.getItem('secmautos_sucursales') || '[]');

    if (sucursales.includes(nombre)) {
        alert('❌ Esa sucursal ya existe');
        return;
    }

    sucursales.push(nombre);
    localStorage.setItem('secmautos_sucursales', JSON.stringify(sucursales));

    input.value = '';
    cargarSucursales();
    alert('✅ Sucursal agregada correctamente');
}

function eliminarSucursal(index) {
    if (!confirm('¿Está seguro de eliminar esta sucursal?')) {
        return;
    }

    const sucursales = JSON.parse(localStorage.getItem('secmautos_sucursales') || '[]');
    sucursales.splice(index, 1);
    localStorage.setItem('secmautos_sucursales', JSON.stringify(sucursales));

    cargarSucursales();
}

function cambiarPassword() {
    const actual = document.getElementById('nueva-password-actual').value;
    const nueva = document.getElementById('nueva-password-nueva').value;
    const confirmar = document.getElementById('nueva-password-confirmar').value;

    if (!actual || !nueva || !confirmar) {
        alert('❌ Complete todos los campos');
        return;
    }

    if (nueva !== confirmar) {
        alert('❌ Las contraseñas nuevas no coinciden');
        return;
    }

    if (nueva.length < 6) {
        alert('❌ La contraseña debe tener al menos 6 caracteres');
        return;
    }

    const usuarioId = window.phpUsuarioId || null;
    if (!usuarioId) {
        alert('❌ No se puede obtener el ID de usuario actual');
        return;
    }

    fetch('api/usuarios.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            id: usuarioId,
            password: nueva,
            csrf_token: csrfTokenConfig
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ Contraseña cambiada correctamente');
            document.getElementById('nueva-password-actual').value = '';
            document.getElementById('nueva-password-nueva').value = '';
            document.getElementById('nueva-password-confirmar').value = '';
        } else {
            alert('❌ Error al cambiar contraseña: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error al cambiar contraseña');
    });
}

function actualizarHeader() {
    const config = JSON.parse(localStorage.getItem('secmautos_config') || '{}');
    const headerTitle = document.querySelector('.header h1');
    if (headerTitle && config.nombre_empresa) {
        headerTitle.textContent = '🚗 ' + config.nombre_empresa;
    }
}
