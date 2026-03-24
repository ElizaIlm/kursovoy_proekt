<?php
    session_start();

    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrator') {
        header("Location: ../auth/login.php");
        exit;
    }

    define('ROOT', dirname(__DIR__, 3));
    require_once ROOT . "/settings/connect_database.php";

    $today = date('Y-m-d');

    $res = $mysql_connection->query("
    SELECT SUM(total_amount) as sum
    FROM orders
    WHERE DATE(order_datetime) = '$today'
    ");
    $revenue_today = $res->fetch_assoc()['sum'] ?? 0;

    $res = $mysql_connection->query("
    SELECT SUM(total_amount) as sum
    FROM orders
    WHERE MONTH(order_datetime) = MONTH(NOW())
    ");
    $revenue_month = $res->fetch_assoc()['sum'] ?? 0;

    $res = $mysql_connection->query("
    SELECT COUNT(*) as cnt
    FROM orders
    WHERE DATE(order_datetime) = '$today'
    ");
    $orders_today = $res->fetch_assoc()['cnt'] ?? 0;

    $res = $mysql_connection->query("SELECT COUNT(*) as cnt FROM orders");
    $orders_total = $res->fetch_assoc()['cnt'] ?? 0;

    $top_dishes = $mysql_connection->query("
    SELECT d.name, SUM(oi.quantity) as total
    FROM order_items oi
    JOIN dishes d ON d.id = oi.dish_id
    GROUP BY d.id
    ORDER BY total DESC
    LIMIT 5
    ");

    $top_waiters = $mysql_connection->query("
    SELECT e.full_name, COUNT(o.id) as orders_count
    FROM orders o
    JOIN employees e ON e.id = o.employee_id
    WHERE e.role = 'waiter'
    GROUP BY e.id
    ORDER BY orders_count DESC
    LIMIT 5
    ");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчёты</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body{
            background:linear-gradient(to bottom right,#0f1419,#080c0f);
        }
        .glass{
            background:rgba(15,20,25,.75);
            backdrop-filter:blur(10px);
            border:1px solid rgba(255,255,255,.08);
        }
    </style>
</head>
<body class="text-gray-100 min-h-screen">
    <header class="bg-gray-900 border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between">
            <span class="text-red-600 text-3xl font-bold">プレミアム寿司</span>
            <div class="flex gap-6">
                <a href="admin.php" class="text-gray-300 hover:text-white">← Назад</a>
            </div>
        </div>
    </header>
    <main class="max-w-7xl mx-auto px-6 py-10">
        <h1 class="text-4xl font-bold text-center mb-10">
            Отчёты ресторана
        </h1>
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="glass p-6 rounded-xl text-center">
            <div class="text-3xl text-red-500 font-bold">
                <?= $orders_today ?>
            </div>
            <div class="text-gray-400">Заказов сегодня</div>
        </div>
        <div class="glass p-6 rounded-xl text-center">
            <div class="text-3xl text-yellow-500 font-bold">
                <?= number_format($revenue_today,0,' ',' ') ?> ₽
            </div>
            <div class="text-gray-400">Выручка сегодня</div>
        </div>
    <div class="glass p-6 rounded-xl text-center">
    <div class="text-3xl text-blue-400 font-bold">
    <?= $orders_total ?>
    </div>
    <div class="text-gray-400">Всего заказов</div>
    </div>
    <div class="glass p-6 rounded-xl text-center">
    <div class="text-3xl text-green-400 font-bold">
    <?= number_format($revenue_month,0,' ',' ') ?> ₽
    </div>
    <div class="text-gray-400">Выручка за месяц</div>
    </div>
    </div>
    <a href="export_report.php" class="inline-block mb-6 px-6 py-3 bg-green-700 hover:bg-green-600 rounded-xl font-semibold">
        Скачать Excel отчёт
    </a>
    <div class="glass p-8 rounded-xl mb-10">
    <h2 class="text-2xl mb-6">Популярные блюда</h2>
    <table class="w-full">
    <thead class="bg-gray-800">
    <tr>
    <th class="p-4 text-left">Блюдо</th>
    <th class="p-4 text-right">Продано</th>
    </tr>
    </thead>
    <tbody>
    <?php while($row = $top_dishes->fetch_assoc()): ?>
    <tr class="border-b border-gray-800">
    <td class="p-4"><?= htmlspecialchars($row['name']) ?></td>
    <td class="p-4 text-right text-yellow-400 font-bold">
    <?= $row['total'] ?>
    </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
    </table>
    </div>
    <div class="glass p-8 rounded-xl">
    <h2 class="text-2xl mb-6">Лучшие официанты</h2>
    <table class="w-full">
    <thead class="bg-gray-800">
    <tr>
    <th class="p-4 text-left">Сотрудник</th>
    <th class="p-4 text-right">Заказов</th>
    </tr>
    </thead>
    <tbody>
    <?php while($row = $top_waiters->fetch_assoc()): ?>
    <tr class="border-b border-gray-800">
    <td class="p-4"><?= htmlspecialchars($row['full_name']) ?></td>
    <td class="p-4 text-right text-green-400 font-bold">
    <?= $row['orders_count'] ?>
    </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
    </table>
    </div>
    </main>
</body>
</html>