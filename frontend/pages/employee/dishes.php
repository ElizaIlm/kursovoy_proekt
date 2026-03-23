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
    
    $message = '';
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
        $action = $_POST['action'] ?? '';
    
        if ($action === 'create') {
    
            $name = trim($_POST['name']);
            $composition = trim($_POST['composition'] ?? '');
            $price = (float)$_POST['price'];
            $description = trim($_POST['description']);
    
            $image = $_FILES['image'] ?? null;

            $dishCtrl->createDish(
                $name,
                $composition,
                $price,
                $description,
                $image
            );
        }
    
        if ($action === 'update') {
    
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $composition = trim($_POST['composition'] ?? '');
            $price = (float)$_POST['price'];
            $description = trim($_POST['description']);
            $availability = 1;
    
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
    
        if ($action === 'delete') {
    
            $id = (int)$_POST['id'];
    
            $dishCtrl->deleteDish($id);
    
            header("Location: dishes.php?msg=deleted");
            exit;
        }
    }
    
    if (isset($_GET['msg'])) {
    
        if ($_GET['msg'] === 'created') $message = "Блюдо добавлено";
        if ($_GET['msg'] === 'updated') $message = "Блюдо обновлено";
        if ($_GET['msg'] === 'deleted') $message = "Блюдо удалено";
    }
    
    $dishes = $dishCtrl->getAllDishes();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Блюда</title>
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
                <a href="../auth/login.php?logout=1" class="text-red-400 hover:text-red-300">Выйти</a>
            </div>
        </div>
    </header>
    <main class="max-w-7xl mx-auto px-6 py-10">
        <h1 class="text-4xl font-bold text-center mb-10">Управление блюдами</h1>
        <?php if($message): ?>
            <div class="bg-green-800/40 border border-green-700 p-4 rounded mb-6 text-center"><?= $message ?></div>
        <?php endif; ?>
        <div class="glass p-8 rounded-xl mb-10">
            <h2 class="text-2xl mb-6">Добавить блюдо</h2>
            <form method="post" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-6">
                <input type="hidden" name="action" value="create">
                <input type="text" name="name" placeholder="Название" required class="bg-gray-800 border border-gray-700 rounded px-4 py-3">
                <div class="md:col-span-2">
                    <label class="block mb-2">Состав</label>
                    <textarea name="composition" class="w-full bg-gray-800 border border-gray-700 rounded px-4 py-3"></textarea>
                </div>
                <input type="number" name="price" placeholder="Цена" min="0" step="1" required class="bg-gray-800 border border-gray-700 rounded px-4 py-3">
                <textarea name="description" placeholder="Описание" class="md:col-span-2 bg-gray-800 border border-gray-700 rounded px-4 py-3"></textarea>
                <input type="file" name="image" accept="image/*" class="md:col-span-2 bg-gray-800 border border-gray-700 rounded px-4 py-3">
                <button class="md:col-span-2 bg-red-700 hover:bg-red-600 py-4 rounded text-lg">Добавить блюдо</button>
            </form>
        </div>
        <div class="glass rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-800">
                    <tr>
                        <th class="p-4">Фото</th>
                        <th class="p-4">Название</th>
                        <th class="p-4">Цена</th>
                        <th class="p-4">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dishes as $dish): ?>
                        <tr class="border-b border-gray-800">
                            <td class="p-4">
                                <img src="../../<?= $dish->image_path ?>"class="w-16 h-16 object-cover rounded">
                            </td>

                            <td class="p-4">
                                <?= htmlspecialchars($dish->name) ?>
                            </td>

                            <td class="p-4">
                                <?= number_format($dish->price,0,' ',' ') ?> ₽
                            </td>

                            <td class="p-4 flex gap-4">
                                <a href="edit_dish.php?id=<?= $dish->id ?>"class="text-blue-400 hover:text-blue-300">Редактировать</a>
                                <form method="post" onsubmit="return confirm('Удалить блюдо?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $dish->id ?>">
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