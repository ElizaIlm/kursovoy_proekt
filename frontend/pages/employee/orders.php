<?php
    session_start();

    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrator') {
        header("Location: ../auth/login.php");
        exit;
    }

    define('ROOT', dirname(__DIR__, 3));

    require_once ROOT . "/settings/connect_database.php";


    $sql = "
    SELECT 
        o.id,
        o.order_datetime,
        o.total_amount,
        c.full_name AS client_name,
        e.full_name AS waiter_name
    FROM orders o
    LEFT JOIN clients c ON o.client_id = c.id
    LEFT JOIN employees e ON o.employee_id = e.id
    ORDER BY o.order_datetime DESC
    ";

    $result = $mysql_connection->query($sql);

    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы</title>
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
            Список заказов
        </h1>
        <div class="glass rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-800">
                    <tr>
                        <th class="p-4">№</th>
                        <th class="p-4">Дата</th>
                        <th class="p-4">Клиент</th>
                        <th class="p-4">Официант</th>
                        <th class="p-4">Сумма</th>
                        <th class="p-4">Подробнее</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $order): ?>
                        <tr class="border-b border-gray-800 hover:bg-gray-800/40">
                            <td class="p-4">
                                <?= $order['id'] ?>
                            </td>
                            <td class="p-4">
                                <?= date("d.m.Y H:i", strtotime($order['order_datetime'])) ?>
                            </td>
                            <td class="p-4">
                                <?= htmlspecialchars($order['client_name'] ?? "—") ?>
                            </td>
                            <td class="p-4">
                                <?= htmlspecialchars($order['waiter_name'] ?? "—") ?>
                            </td>
                            <td class="p-4 text-green-400 font-semibold">
                                <?= number_format($order['total_amount'],0,' ',' ') ?> ₽
                            </td>
                            <td class="p-4">
                                <a href="order_details.php?id=<?= $order['id'] ?>" class="text-blue-400 hover:text-blue-300">Открыть</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>