document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;
    
    // Получаем сохраненную тему или ставим светлую по умолчанию
    const savedTheme = localStorage.getItem('theme') || 'light';
    htmlElement.setAttribute('data-theme', savedTheme);
    updateButtonIcon(themeToggleBtn, savedTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateButtonIcon(themeToggleBtn, newTheme);
        });
    }

    function updateButtonIcon(btn, theme) {
        if (theme === 'dark') {
            btn.innerHTML = '☀️'; // Солнышко для переключения на светлую
            btn.title = "Включить светлую тему";
        } else {
            btn.innerHTML = '🌙'; // Луна для переключения на темную
            btn.title = "Включить темную тему";
        }
    }
});