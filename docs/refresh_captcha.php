<?php
// api/refresh_captcha.php - Genera un nuevo código CAPTCHA
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ⚠️ ADVERTENCIA DE SEGURIDAD:
// Este CAPTCHA es un número de 4 dígitos, lo cual es muy débil y puede ser fácilmente
// descifrado por un ataque de fuerza bruta. Se recomienda encarecidamente reemplazarlo
// por una solución más segura, como una librería que genere imágenes (ej. gregwar/captcha)
// o un servicio como Google reCAPTCHA / hCaptcha.
// Generar un número aleatorio de 4 dígitos
$captcha_code = rand(1000, 9999);

// Guardar el código en la sesión para validarlo en el login_handler.php
$_SESSION['captcha'] = $captcha_code;

// Devolver el código para que JavaScript lo muestre en la página
echo $captcha_code;
?>