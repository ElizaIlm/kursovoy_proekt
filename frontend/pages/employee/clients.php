<?php
    session_start();

    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrator') {
        header("Location: ../auth/login.php");
        exit;
    }

    define('ROOT', dirname(__DIR__, 3));

    require_once ROOT . "/settings/connect_database.php";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {

            $id = (int)$_POST['id'];

            $stmt = $mysql_connection->prepare("DELETE FROM clients WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            header("Location: clients.php?msg=deleted");
            exit;
        }
    }

    $result = $mysql_connection->query("
    SELECT 
        c.id,
        c.full_name,
        c.phone,
        c.email,
        COUNT(o.id) as orders_count,
        COALESCE(SUM(o.total_amount),0) as total_spent
    FROM clients c
    LEFT JOIN orders o ON o.client_id = c.id
    GROUP BY c.id
    ORDER BY c.full_name
    ");

    $clients = [];

    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }

    $message = '';

    if (isset($_GET['msg'])) {
        if ($_GET['msg'] === 'deleted') $message = "Клиент удален";
    }
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Клиенты</title>
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
            <a href="admin.php" class="text-3xl font-bold">
                <span class="text-red-600">プレミアム寿司</span>
            </a>
            <div class="flex gap-6">
                <a href="admin.php" class="text-gray-300 hover:text-white">← Назад</a>
            </div>
        </div>
    </header>
    <main class="max-w-7xl mx-auto px-6 py-10">
        <h1 class="text-4xl font-bold text-center mb-10">
            Клиенты
        </h1>
        <?php if ($message): ?>
            <div class="bg-green-800/40 border border-green-700 p-4 rounded mb-6 text-center">
                <?= $message ?>
            </div>
        <?php endif; ?>
        <div class="glass rounded-xl overflow-hidden">
            <?php if (empty($clients)): ?>
                <div class="p-10 text-center text-gray-400">
                    Клиентов пока нет
                </div>
            <?php else: ?>
            <table class="w-full text-left">
                <thead class="bg-gray-800">
                    <tr>
                        <th class="p-4">ФИО</th>
                        <th class="p-4">Телефон</th>
                        <th class="p-4">Email</th>
                        <th class="p-4 text-center">Заказы</th>
                        <th class="p-4 text-right">Потрачено</th>
                        <th class="p-4 text-center">Действия</th>
                    </tr>
                </thead>
                    <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr class="border-b border-gray-800 hover:bg-gray-800/40">
                            <td class="p-4">
                                <?= htmlspecialchars($client['full_name']) ?>
                            </td>
                            <td class="p-4">
                                <?= htmlspecialchars($client['phone']) ?>
                            </td>
                            <td class="p-4">
                                <?= htmlspecialchars($client['email']) ?>
                            </td>
                            <td class="p-4 text-center">
                                <a href="client_orders.php?id=<?= $client['id'] ?>" 
                                   class="text-blue-400 hover:text-blue-300">
                                   Заказы
                                </a>
                            </td>
                            <td class="p-4 text-right text-yellow-500 font-bold">
                                <?= number_format($client['total_spent'], 0, '', ' ') ?> ₽
                            </td>
                            <td class="p-4 text-center">
                                <form method="post" onsubmit="return confirm('Удалить клиента?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $client['id'] ?>">
                                    <button class="text-red-400 hover:text-red-300">
                                        Удалить
                                    </button>
                                </form>
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