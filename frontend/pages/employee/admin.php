<?php
    session_start();

    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrator') {
        header("Location: ../auth/login.php");
        exit;
    }

    define('ROOT', dirname(__DIR__, 3));

    require_once ROOT . "/settings/connect_database.php";
    require_once ROOT . "/backend/Controllers/DishController.php";
    require_once ROOT . "/backend/Controllers/OrderController.php";

    $dishCtrl = new DishController($mysql_connection);
    $orderCtrl = new OrderController($mysql_connection);

    $today = date('Y-m-d');

    $res = $mysql_connection->query("SELECT COUNT(*) as cnt FROM dishes");
    $dishes_total = $res->fetch_assoc()['cnt'] ?? 0;

    $res = $mysql_connection->query("
        SELECT COUNT(*) as cnt
        FROM orders
        WHERE DATE(order_datetime) = '$today'
    ");
    $orders_today = $res->fetch_assoc()['cnt'] ?? 0;

    $res = $mysql_connection->query("SELECT COUNT(*) as cnt FROM orders");
    $orders_total = $res->fetch_assoc()['cnt'] ?? 0;

    $res = $mysql_connection->query("SELECT COUNT(*) as cnt FROM clients");
    $clients_total = $res->fetch_assoc()['cnt'] ?? 0;

    $res = $mysql_connection->query("
        SELECT COUNT(*) as cnt
        FROM employees
        WHERE role = 'waiter'
    ");
    $waiters_total = $res->fetch_assoc()['cnt'] ?? 0;

    $res = $mysql_connection->query("
        SELECT SUM(total_amount) as sum
        FROM orders
        WHERE DATE(order_datetime) = '$today'
    ");
    $revenue_today = $res->fetch_assoc()['sum'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ панель</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body{
            background:linear-gradient(to bottom right,#0f1419,#080c0f);
        }
        .glass{
            background:rgba(15,20,25,.7);
            backdrop-filter:blur(10px);
            border:1px solid rgba(255,255,255,.08);
        }
    </style>
</head>
<body class="text-gray-100 min-h-screen">
<header class="bg-gray-900/80 border-b border-gray-800 sticky top-0 z-50 backdrop-blur">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">

    <div class="text-3xl font-bold">
        <span class="text-red-600">プレミアム寿司</span>
    </div>

    <div class="flex items-center gap-6">
        <span>Администратор: <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="../auth/login.php?logout=1" class="text-red-400 hover:text-red-300">Выйти</a>
    </div>

    </div>
</header>
<main class="max-w-7xl mx-auto px-6 py-10">
    
    <h1 class="text-4xl font-bold text-center mb-10">
        Панель администратора
    </h1>
    
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-12">
    
        <div class="glass rounded-xl p-6 text-center">
            <div class="text-4xl font-bold text-red-500"><?= $orders_today ?></div>
            <div class="text-gray-400 mt-2">Заказов сегодня</div>
        </div>

        <div class="glass rounded-xl p-6 text-center">
            <div class="text-4xl font-bold text-yellow-500"><?= number_format($revenue_today,0,' ',' ') ?> ₽</div>
            <div class="text-gray-400 mt-2">Выручка сегодня</div>
        </div>

        <div class="glass rounded-xl p-6 text-center">
            <div class="text-4xl font-bold text-green-400"><?= $dishes_total ?></div>
            <div class="text-gray-400 mt-2">Блюд в меню</div>
        </div>

        <div class="glass rounded-xl p-6 text-center">
            <div class="text-4xl font-bold text-blue-400"><?= $clients_total ?></div>
            <div class="text-gray-400 mt-2">Клиентов</div>
        </div>

        <div class="glass rounded-xl p-6 text-center">
            <div class="text-4xl font-bold text-purple-400"><?= $waiters_total ?></div>
            <div class="text-gray-400 mt-2">Официантов</div>
        </div>

        <div class="glass rounded-xl p-6 text-center">
            <div class="text-4xl font-bold text-orange-400"><?= $orders_total ?></div>
            <div class="text-gray-400 mt-2">Всего заказов</div>
        </div>
    
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    
        <a href="dishes.php" class="glass p-8 rounded-xl text-center hover:bg-gray-800 transition">
            <h3 class="text-xl font-semibold mb-2">Блюда</h3>
            <p class="text-gray-400">
                Добавление, редактирование и удаление блюд
            </p>
        </a>

        <a href="orders.php" class="glass p-8 rounded-xl text-center hover:bg-gray-800 transition">
            <h3 class="text-xl font-semibold mb-2">Заказы</h3>
            <p class="text-gray-400">
                Просмотр всех заказов
            </p>
        </a>

        <a href="employees.php" class="glass p-8 rounded-xl text-center hover:bg-gray-800 transition">
            <h3 class="text-xl font-semibold mb-2">Сотрудники</h3>
            <p class="text-gray-400">
                Управление официантами
            </p>
        </a>

        <a href="clients.php" class="glass p-8 rounded-xl text-center hover:bg-gray-800 transition">
            <h3 class="text-xl font-semibold mb-2">Клиенты</h3>
            <p class="text-gray-400">
                Просмотр зарегистрированных клиентов
            </p>
        </a>

        <a href="reports.php" class="glass p-8 rounded-xl text-center hover:bg-gray-800 transition">
            <h3 class="text-xl font-semibold mb-2">Отчёты</h3>
            <p class="text-gray-400">
                Статистика ресторана
            </p>
        </a>
    </div>
</main>

<footer class="text-center py-8 text-gray-500 border-t border-gray-800 mt-12">
    © <?= date("Y") ?> Лучший суши-ресторан
</footer>

</body>
</html>