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

        if ($action === "create") {

            $full_name = trim($_POST['full_name']);
            $login = trim($_POST['login']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'];

            $stmt = $mysql_connection->prepare("
                INSERT INTO employees (full_name, login, password_hash, role)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param("ssss", $full_name, $login, $password, $role);
            $stmt->execute();

            header("Location: employees.php?msg=created");
            exit;
        }

        if ($action === "delete") {

            $id = (int)$_POST['id'];

            $stmt = $mysql_connection->prepare("DELETE FROM employees WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            header("Location: employees.php?msg=deleted");
            exit;
        }

        if ($action === "update") {

            $id = (int)$_POST['id'];
            $full_name = trim($_POST['full_name']);
            $role = $_POST['role'];

            $stmt = $mysql_connection->prepare("
                UPDATE employees
                SET full_name=?, role=?
                WHERE id=?
            ");

            $stmt->bind_param("ssi", $full_name, $role, $id);
            $stmt->execute();

            header("Location: employees.php?msg=updated");
            exit;
        }
    }

    $result = $mysql_connection->query("
        SELECT id, full_name, login, role
        FROM employees
        ORDER BY full_name
    ");

    $employees = [];

    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }

    $message = '';

    if (isset($_GET['msg'])) {
        if ($_GET['msg'] === "created") $message = "Сотрудник добавлен";
        if ($_GET['msg'] === "updated") $message = "Сотрудник обновлен";
        if ($_GET['msg'] === "deleted") $message = "Сотрудник удален";
    }
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сотрудники</title>
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
        Сотрудники
    </h1>
    <?php if($message): ?>
    <div class="bg-green-800/40 border border-green-700 p-4 rounded mb-6 text-center">
        <?= $message ?>
    </div>
    <?php endif; ?>
    <div class="glass p-8 rounded-xl mb-10">
    <h2 class="text-2xl mb-6">
        Добавить сотрудника
    </h2>
    <form method="post" class="grid md:grid-cols-2 gap-6">
    <input type="hidden" name="action" value="create">
    <input
    type="text"
    name="full_name"
    placeholder="ФИО"
    required
    class="bg-gray-800 border border-gray-700 rounded px-4 py-3">
    <input
    type="text"
    name="login"
    placeholder="Логин"
    required
    class="bg-gray-800 border border-gray-700 rounded px-4 py-3">
    <input
    type="password"
    name="password"
    placeholder="Пароль"
    required
    class="bg-gray-800 border border-gray-700 rounded px-4 py-3">
    <select
    name="role"
    class="bg-gray-800 border border-gray-700 rounded px-4 py-3">
    <option value="administrator">Администратор</option>
    <option value="waiter">Официант</option>
    </select>
    <button class="md:col-span-2 bg-red-700 hover:bg-red-600 py-4 rounded text-lg">Добавить сотрудника</button>
    </form>
    </div>
    <div class="glass rounded-xl overflow-hidden">
    <table class="w-full">
    <thead class="bg-gray-800">
    <tr>
    <th class="p-4">ФИО</th>
    <th class="p-4">Логин</th>
    <th class="p-4">Роль</th>
    <th class="p-4">Действия</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($employees as $emp): ?>
    <tr class="border-b border-gray-800">
    <td class="p-4">
    <form method="post" class="flex gap-2 items-center">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= $emp['id'] ?>">
    <input
    type="text"
    name="full_name"
    value="<?= htmlspecialchars($emp['full_name']) ?>"
    class="bg-gray-800 border border-gray-700 rounded px-3 py-1">
    </td>
    <td class="p-4">
    <?= htmlspecialchars($emp['login']) ?>
    </td>
    <td class="p-4">
    <select name="role" class="bg-gray-800 border border-gray-700 rounded px-3 py-1">
    <option value="administrator" <?= $emp['role']=="administrator"?"selected":"" ?>>Администратор</option>
    <option value="waiter" <?= $emp['role']=="waiter"?"selected":"" ?>>Официант</option>
    </select>
    </td>
    <td class="p-4 flex gap-3">
    <button class="text-blue-400 hover:text-blue-300">Сохранить</button>
    </form>
    <form method="post" onsubmit="return confirm('Удалить сотрудника?')">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="<?= $emp['id'] ?>">
    <button class="text-red-400 hover:text-red-300">Удалить</button>
    </form>
    </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    </main>
</body>
</html>