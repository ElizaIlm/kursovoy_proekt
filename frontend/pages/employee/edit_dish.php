<?php
    session_start();

    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrator') {
        header("Location: ../auth/login.php");
        exit;
    }

    define('ROOT', dirname(__DIR__, 3));

    require_once ROOT . "/settings/connect_database.php";
    require_once ROOT . "/backend/Controllers/DishController.php";

    $dishCtrl = new DishController($mysql_connection);

    $id = (int)($_GET['id'] ?? 0);

    $dish = $dishCtrl->getDishById($id);

    if (!$dish) {
        die("Блюдо не найдено");
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $name = trim($_POST["name"]);
        $composition = trim($_POST["composition"]);
        $price = (float)$_POST["price"];
        $description = trim($_POST["description"]);
        $availability = isset($_POST["availability"]) ? 1 : 0;

        $dishCtrl->updateDish(
            $id,
            $name,
            $composition,
            $price,
            $description,
            $availability
        );

        header("Location: dishes.php?msg=updated");
        exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование блюда</title>
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
        <span class="text-red-600">プレミアム寿司</span>
        <a href="dishes.php" class="text-gray-300 hover:text-white">← Назад</a>
    </div>
</header>
<main class="max-w-4xl mx-auto px-6 py-10">
    <h1 class="text-4xl font-bold text-center mb-10">
        Редактирование блюда
    </h1>
    <div class="glass p-8 rounded-xl">
        <form method="post" class="grid gap-6">
            <div>
                <label class="block mb-2">Название</label>
                <input type="text" name="name" value="<?= htmlspecialchars($dish->name) ?>" required class="w-full bg-gray-800 border border-gray-700 rounded px-4 py-3">
            </div>

            <div>
                <label class="block mb-2">Состав</label>
                <textarea name="composition" class="w-full bg-gray-800 border border-gray-700 rounded px-4 py-3"><?= htmlspecialchars($dish->composition) ?></textarea>
            </div>

            <div>
                <label class="block mb-2">Цена</label>
                <input type="number" name="price" value="<?= $dish->price ?>" min="0" step="1" required class="w-full bg-gray-800 border border-gray-700 rounded px-4 py-3">
            </div>

            <div>
                <label class="block mb-2">Описание</label>
                <textarea name="description" class="w-full bg-gray-800 border border-gray-700 rounded px-4 py-3"><?= htmlspecialchars($dish->description) ?></textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="availability" <?= $dish->availability ? "checked" : "" ?> class="w-5 h-5">
                <span>Доступно для заказа</span>
            </div>

            <button class="bg-red-700 hover:bg-red-600 py-4 rounded text-lg"> Сохранить изменения</button>
        </form>
    </div>
</main>
</body>
</html>