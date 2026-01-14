let datosImportacion = null;

function cargarImportador() {
    mostrarSeccion('loading');

    // Obtener token CSRF del meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const csrfField = document.querySelector('#import-csrf-token');
    if (csrfField && csrfToken) {
        csrfField.value = csrfToken;
    }

    fetch('api/importar_vehiculos.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                datosImportacion = data.data;
                mostrarPreview(data.data, data.patentes_existentes);
            } else {
                mostrarError(data.message || 'Error al cargar datos');
            }
        })
        .catch(error => {
            console.error('Error al cargar importador:', error);
            mostrarError('Error al cargar datos de importación: ' + error.message);
        });
}

function mostrarPreview(data, patentesExistentes) {
    // Actualizar información general
    document.getElementById('import-origen').textContent = data.origen || 'Registro Vehículos.xls';
    document.getElementById('import-fecha').textContent = data.fecha_exportacion || '';
    document.getElementById('import-total').textContent = data.total || 0;

    // Calcular estadísticas
    const vehiculos = data.vehiculos || [];
    const nuevos = vehiculos.filter(v => !patentesExistentes.includes(v.patente)).length;
    const existentes = vehiculos.filter(v => patentesExistentes.includes(v.patente)).length;
    const conTitulo = vehiculos.filter(v => v.titulo_dnrpa).length;

    document.getElementById('stat-total').textContent = vehiculos.length;
    document.getElementById('stat-nuevos').textContent = nuevos;
    document.getElementById('stat-existentes').textContent = existentes;
    document.getElementById('stat-con-titulo').textContent = conTitulo;

    // Mostrar preview de primeros 10 vehículos
    const tbody = document.getElementById('import-preview-table');
    tbody.innerHTML = '';

    vehiculos.slice(0, 10).forEach(vehiculo => {
        const existe = patentesExistentes.includes(vehiculo.patente);
        const badge = existe
            ? '<span class="badge badge-warning">Ya existe</span>'
            : '<span class="badge badge-success">Nuevo</span>';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${badge}</td>
            <td><strong>${vehiculo.patente}</strong></td>
            <td>${vehiculo.marca || '-'}</td>
            <td>${vehiculo.modelo || '-'}</td>
            <td>${vehiculo.anio || '-'}</td>
            <td>${vehiculo.titular || '-'}</td>
            <td>${vehiculo.titulo_dnrpa || '-'}</td>
        `;
        tbody.appendChild(tr);
    });

    mostrarSeccion('preview');
}

function ejecutarImportacion() {
    if (!confirm(`¿Está seguro de iniciar la importación?\n\nSe procesarán ${datosImportacion.total} vehículos.`)) {
        return;
    }

    const modo = document.getElementById('import-modo').value;
    mostrarSeccion('progress');

    // Obtener token CSRF del campo oculto o del meta tag
    let csrfToken = document.querySelector('#import-csrf-token')?.value;
    if (!csrfToken) {
        csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('modo', modo);

    fetch('api/importar_vehiculos.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarResultados(data.stats);
            } else {
                mostrarError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al ejecutar la importación');
        });
}

function mostrarResultados(stats) {
    document.getElementById('result-creados').textContent = stats.creados;
    document.getElementById('result-actualizados').textContent = stats.actualizados;
    document.getElementById('result-omitidos').textContent = stats.omitidos;
    document.getElementById('result-errores').textContent = stats.errores.length;

    // Mostrar mensaje de resultado
    const alert = document.getElementById('import-result-alert');
    if (stats.errores.length === 0) {
        alert.className = 'alert alert-success';
        alert.querySelector('h4').textContent = '✅ Importación Completada Exitosamente';
    } else {
        alert.className = 'alert alert-warning';
        alert.querySelector('h4').textContent = '⚠️ Importación Completada con Advertencias';
    }

    const content = document.getElementById('import-result-content');
    content.innerHTML = `
        <p><strong>Resumen de la importación:</strong></p>
        <ul>
            <li>✨ <strong>${stats.creados}</strong> vehículos creados</li>
            <li>♻️ <strong>${stats.actualizados}</strong> vehículos actualizados</li>
            <li>⏭️ <strong>${stats.omitidos}</strong> vehículos omitidos (duplicados)</li>
            ${stats.errores.length > 0 ? `<li>❌ <strong>${stats.errores.length}</strong> errores</li>` : ''}
        </ul>
    `;

    // Mostrar tabla de errores si hay
    if (stats.errores.length > 0) {
        const tbody = document.getElementById('import-errores-table');
        tbody.innerHTML = '';

        stats.errores.forEach(error => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${error.patente}</strong></td>
                <td>${error.error}</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('import-errores').style.display = 'block';
    }

    mostrarSeccion('result');
}

function mostrarError(mensaje) {
    const alert = document.getElementById('import-result-alert');
    alert.className = 'alert alert-danger';
    alert.querySelector('h4').textContent = '❌ Error en la Importación';

    const content = document.getElementById('import-result-content');
    content.innerHTML = `<p>${mensaje}</p>`;

    mostrarSeccion('result');
}

function mostrarSeccion(seccion) {
    document.getElementById('import-loading').style.display = seccion === 'loading' ? 'block' : 'none';
    document.getElementById('import-preview').style.display = seccion === 'preview' ? 'block' : 'none';
    document.getElementById('import-progress').style.display = seccion === 'progress' ? 'block' : 'none';
    document.getElementById('import-result').style.display = seccion === 'result' ? 'block' : 'none';
}
