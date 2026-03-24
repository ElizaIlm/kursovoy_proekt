<?php
    session_start();

    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrator') {
        header("Location: ../auth/login.php");
        exit;
    }

    define('ROOT', dirname(__DIR__, 3));

    require_once ROOT . "/settings/connect_database.php";

    $client_id = (int)($_GET['id'] ?? 0);

    if (!$client_id) {
        header("Location: clients.php");
        exit;
    }

    $stmt = $mysql_connection->prepare("
    SELECT full_name, phone, email
    FROM clients
    WHERE id=?
    ");

    $stmt->bind_param("i", $client_id);
    $stmt->execute();

    $client = $stmt->get_result()->fetch_assoc();

    if (!$client) {
        header("Location: clients.php");
        exit;
    }

    $stmt = $mysql_connection->prepare("
    SELECT id, order_datetime, total_amount
    FROM orders
    WHERE client_id=?
    ORDER BY order_datetime DESC
    ");

    $stmt->bind_param("i", $client_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы клиента</title>
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
            <a href="clients.php" class="text-3xl font-bold">
                <span class="text-red-600">プレミアム寿司</span>
            </a>
            <div class="flex gap-6">
                <a href="clients.php" class="text-gray-300 hover:text-white">← Назад</a>
                <a href="../auth/login.php?logout=1" class="text-red-400 hover:text-red-300">Выйти</a>
            </div>
        </div>
    </header>
    <main class="max-w-7xl mx-auto px-6 py-10">
        <h1 class="text-4xl font-bold text-center mb-4">
            Заказы клиента
        </h1>
        <p class="text-center text-gray-400 mb-10">
            <?= htmlspecialchars($client['full_name']) ?> • <?= htmlspecialchars($client['phone']) ?>
        </p>
        <div class="glass rounded-xl overflow-hidden">
        <?php if (empty($orders)): ?>
            <div class="p-10 text-center text-gray-400">
                У клиента нет заказов
            </div>
        <?php else: ?>
            <table class="w-full text-left">
                <thead class="bg-gray-800">
                    <tr>
                        <th class="p-4">№ заказа</th>
                        <th class="p-4">Дата</th>
                        <th class="p-4 text-right">Сумма</th>
                        <th class="p-4 text-center">Детали</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr class="border-b border-gray-800 hover:bg-gray-800/40">
                        <td class="p-4 font-bold">
                            #<?= $order['id'] ?>
                        </td>
                        <td class="p-4">
                            <?= date("d.m.Y H:i", strtotime($order['order_datetime'])) ?>
                        </td>
                        <td class="p-4 text-right text-yellow-500 font-bold">
                            <?= number_format($order['total_amount'], 0, '', ' ') ?> ₽
                        </td>
                        <td class="p-4 text-center">
                            <a href="order_details.php?id=<?= $order['id'] ?>" class="text-blue-400 hover:text-blue-300">
                                Открыть
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        </div>
    </main>
</body>
</html>