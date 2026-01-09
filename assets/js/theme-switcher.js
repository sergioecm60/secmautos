/**
 * theme-switcher.js
 * Lógica para cambiar entre temas (claro, oscuro, etc.) y guardar la preferencia del usuario.
 */
document.addEventListener('DOMContentLoaded', () => {
    const themeButtons = document.querySelectorAll('.theme-btn');
    const THEME_KEY = 'secmagencias_theme';

    // Función para aplicar un tema
    const applyTheme = (theme) => {
        if (theme === 'auto') {
            // Si es 'auto', usamos la preferencia del sistema operativo
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.body.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
        } else {
            document.body.setAttribute('data-theme', theme);
        }
        // Actualizar el botón activo
        themeButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.theme === theme);
        });
    };

    // Función para guardar la preferencia
    const saveThemePreference = (theme) => {
        localStorage.setItem(THEME_KEY, theme);
    };

    // Al cargar la página, aplicar el tema guardado o el de 'auto' por defecto
    const savedTheme = localStorage.getItem(THEME_KEY) || 'auto';
    applyTheme(savedTheme);

    // Añadir listeners a los botones
    themeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const selectedTheme = button.dataset.theme;
            applyTheme(selectedTheme);
            saveThemePreference(selectedTheme);
        });
    });

    // Listener para el cambio de preferencia del sistema operativo (para el modo 'auto')
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
        const currentPreference = localStorage.getItem(THEME_KEY);
        if (currentPreference === 'auto') {
            applyTheme('auto');
        }
    });
});