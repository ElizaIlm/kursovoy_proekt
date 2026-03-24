<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrator') {
    header("Location: ../auth/login.php");
    exit;
}

define('ROOT', dirname(__DIR__, 3));

require_once ROOT . "/settings/connect_database.php";

$order_id = (int)($_GET['id'] ?? 0);

if (!$order_id) {
    die("Некорректный заказ");
}

$stmt = $mysql_connection->prepare("
SELECT 
    o.id,
    o.order_datetime,
    o.total_amount,
    c.full_name AS client_name,
    e.full_name AS waiter_name
FROM orders o
LEFT JOIN clients c ON o.client_id = c.id
LEFT JOIN employees e ON o.employee_id = e.id
WHERE o.id = ?
");

$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Заказ не найден");
}

$stmt = $mysql_connection->prepare("
SELECT 
    d.name,
    oi.quantity,
    oi.price_at_order
FROM order_items oi
JOIN dishes d ON oi.dish_id = d.id
WHERE oi.order_id = ?
");

$stmt->bind_param("i", $order_id);
$stmt->execute();

$result = $stmt->get_result();

$items = [];

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказ #<?= $order['id'] ?></title>
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
            <a href="orders.php" class="text-gray-300 hover:text-white">← Назад к заказам</a>
        </div>
    </header>
    <main class="max-w-5xl mx-auto px-6 py-10">
        <h1 class="text-4xl font-bold mb-10 text-center">Заказ №<?= $order['id'] ?></h1>
        <div class="glass p-6 rounded-xl mb-10 grid md:grid-cols-2 gap-6">
            <div>
                <span class="text-gray-400">Дата:</span><br>
                <?= date("d.m.Y H:i", strtotime($order['order_datetime'])) ?>
            </div>
            <div>
                <span class="text-gray-400">Клиент:</span><br>
                <?= htmlspecialchars($order['client_name'] ?? "—") ?>
            </div>
            <div>
                <span class="text-gray-400">Официант:</span><br>
                <?= htmlspecialchars($order['waiter_name'] ?? "—") ?>
            </div>
            <div>
                <span class="text-gray-400">Сумма заказа:</span><br>
                <span class="text-green-400 text-xl font-bold">
                    <?= number_format($order['total_amount'],0,' ',' ') ?> ₽
                </span>
            </div>
        </div>
        <div class="glass rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-800">
                    <tr>
                        <th class="p-4 text-left">Блюдо</th>
                        <th class="p-4">Количество</th>
                        <th class="p-4">Цена</th>
                        <th class="p-4">Итого</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                        <tr class="border-b border-gray-800">
                            <td class="p-4">
                                <?= htmlspecialchars($item['name']) ?>
                            </td>
                            <td class="p-4 text-center">
                                <?= $item['quantity'] ?>
                            </td>
                            <td class="p-4 text-center">
                                <?= number_format($item['price_at_order'],0,' ',' ') ?> ₽
                            </td>
                            <td class="p-4 text-center text-green-400 font-semibold">
                                <?= number_format($item['price_at_order'] * $item['quantity'],0,' ',' ') ?> ₽
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>