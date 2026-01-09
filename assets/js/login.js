document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const captchaBox = document.getElementById('captcha-box');
    const captchaInput = document.getElementById('captcha');
    const refreshBtn = document.querySelector('.refresh-captcha');
    const loading = document.getElementById('loading');
    const btnSubmit = document.getElementById('btnSubmit');

    function loadCaptcha() {
        fetch('api/refresh_captcha.php')
            .then(response => response.json())
            .then(data => {
                captchaBox.textContent = `${data.num1} ${data.operator} ${data.num2} = ?`;
                captchaInput.value = '';
            });
    }

    loadCaptcha();

    captchaBox.addEventListener('click', loadCaptcha);
    refreshBtn.addEventListener('click', loadCaptcha);

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '⏳ Procesando...';
        loading.style.display = 'block';

        fetch('api/login_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'index.php';
            } else {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '🔐 Iniciar Sesión';
                loading.style.display = 'none';
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-error';
                errorDiv.textContent = data.message;
                loginForm.insertBefore(errorDiv, loginForm.firstChild);
                
                setTimeout(() => errorDiv.remove(), 5000);
                loadCaptcha();
                captchaInput.value = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '🔐 Iniciar Sesión';
            loading.style.display = 'none';
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-error';
            errorDiv.textContent = 'Error de conexión. Por favor, intente nuevamente.';
            loginForm.insertBefore(errorDiv, loginForm.firstChild);
            
            setTimeout(() => errorDiv.remove(), 5000);
        });
    });

    document.getElementById('email').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('password').focus();
        }
    });

    document.getElementById('password').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('captcha').focus();
        }
    });
});
