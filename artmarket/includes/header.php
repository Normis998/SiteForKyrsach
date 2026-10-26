<header>
    <a href="index.php" class="logo">АртМаркет</a>
    <nav>
        <?php if (isLoggedIn()): ?>
            <a href="dashboard.php" class="nav-btn">Личный кабинет</a>
            <a href="orders.php" class="nav-btn">Мои заказы</a>
            <a href="logout.php" class="nav-btn primary">Выйти</a>
        <?php else: ?>
            <a href="login.php" class="nav-btn">Войти</a>
            <a href="register.php" class="nav-btn primary">Регистрация</a>
        <?php endif; ?>
        
        <!-- Кнопка переключения темы -->
        <button id="theme-toggle" class="nav-btn theme-btn" style="background: transparent; border: none; font-size: 18px; cursor: pointer; padding: 8px;"></button>
    </nav>
</header>