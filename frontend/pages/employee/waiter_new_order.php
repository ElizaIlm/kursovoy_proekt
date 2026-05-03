<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'waiter') {
    header("Location: ../auth/login.php");
    exit;
}

define('ROOT', dirname(__DIR__, 3));

require_once ROOT . "/settings/connect_database.php";
require_once ROOT . "/backend/Controllers/DishController.php";
require_once ROOT . "/backend/Controllers/OrderController.php";

$dishController = new DishController($mysql_connection);
$orderController = new OrderController($mysql_connection);
$dishes = $dishController->getAllDishes();

if (!isset($_SESSION['waiter_cart'])) {
    $_SESSION['waiter_cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add' && isset($_POST['dish_id'], $_POST['quantity'])) {

    $dish_id = (int)$_POST['dish_id'];
    $qty = max(1, (int)$_POST['quantity']);

    $dish = $dishController->getDishById($dish_id);

    if ($dish) {

        $id = $dish->id;

        if (isset($_SESSION['waiter_cart'][$id])) {
            $_SESSION['waiter_cart'][$id]['qty'] += $qty;
        } else {

            $_SESSION['waiter_cart'][$id] = [
                'id' => $id,
                'name' => $dish->name,
                'price' => $dish->price,
                'qty' => $qty
            ];
        }
    }

    header("Location: waiter_new_order.php");
    exit;
}

if ($action === 'remove' && isset($_GET['dish_id'])) {

    $dish_id = (int)$_GET['dish_id'];
    unset($_SESSION['waiter_cart'][$dish_id]);

    header("Location: waiter_new_order.php");
    exit;
}

if ($action === 'create' && !empty($_SESSION['waiter_cart'])) {

    $order_id = $orderController->createOrderByWaiter((int)$_SESSION['user_id'], $_SESSION['waiter_cart']);

    if ($order_id) {
        $_SESSION['waiter_cart'] = [];
        $_SESSION['waiter_flash_ok'] = "Заказ #$order_id успешно создан";
        header("Location: waiter.php");
        exit;
    }

    $_SESSION['waiter_flash_err'] = "Ошибка создания заказа";
    header("Location: waiter_new_order.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новый заказ — официант</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(to bottom right, #0f1419, #080c0f); }
        .glass { background: rgba(15,20,25,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); }
    </style>
</head>
<body class="text-gray-100 min-h-screen">

<header class="bg-gray-900/80 border-b border-gray-800 sticky top-0 z-50 backdrop-blur">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center flex-wrap gap-4">
        <span class="text-red-600 text-3xl font-bold">プレミアム寿司</span>
        <div class="flex items-center gap-6 flex-wrap">
            <a href="waiter.php" class="text-gray-300 hover:text-white">← Мои заказы</a>
            <span class="text-gray-400"><?= htmlspecialchars($_SESSION['user_name'] ?? '—') ?></span>
            <a href="../auth/login.php?logout=1" class="text-red-400 hover:text-red-300">Выйти</a>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-10 grid lg:grid-cols-2 gap-8">

    <section class="glass rounded-2xl p-6">
        <h2 class="text-2xl font-bold mb-6">Меню</h2>
        <div class="grid sm:grid-cols-2 gap-5 max-h-[70vh] overflow-y-auto pr-2">
            <?php foreach ($dishes as $dish): ?>
            <div class="bg-gray-900/60 rounded-xl p-4 flex flex-col">
                <h3 class="font-semibold"><?= htmlspecialchars($dish->name) ?></h3>
                <div class="text-yellow-500 font-bold mt-1"><?= number_format($dish->price, 0, '', ' ') ?> ₽</div>
                <form method="post" class="mt-4 flex gap-3 items-center">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="dish_id" value="<?= $dish->id ?>">
                    <input type="number" name="quantity" value="1" min="1" class="w-16 bg-gray-800 border border-gray-700 rounded text-center py-1">
                    <button type="submit" class="flex-1 bg-red-700 hover:bg-red-600 rounded py-1.5 font-medium">Добавить</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="glass rounded-2xl p-6 flex flex-col">
        <h2 class="text-2xl font-bold mb-6">Текущий заказ</h2>

        <?php if (!empty($_SESSION['waiter_cart'])):
            $total = 0;
        ?>
            <div class="flex-1 overflow-y-auto mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-gray-800/70">
                        <tr>
                            <th class="p-3 text-left">Блюдо</th>
                            <th class="p-3 text-center">Кол-во</th>
                            <th class="p-3 text-right">Сумма</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['waiter_cart'] as $item):
                            $sum = $item['price'] * $item['qty'];
                            $total += $sum;
                        ?>
                        <tr class="border-b border-gray-800">
                            <td class="p-3"><?= htmlspecialchars($item['name']) ?></td>
                            <td class="p-3 text-center font-medium"><?= $item['qty'] ?></td>
                            <td class="p-3 text-right"><?= number_format($sum, 0, '', ' ') ?> ₽</td>
                            <td class="p-3 text-center">
                                <a href="?action=remove&dish_id=<?= $item['id'] ?>" class="text-red-400 hover:text-red-300 text-lg">×</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-2xl font-bold text-right mb-6">
                Итого: <span class="text-yellow-500"><?= number_format($total, 0, '', ' ') ?> ₽</span>
            </div>

            <form method="post" class="space-y-4">
                <input type="hidden" name="action" value="create">
                <button type="submit" class="w-full bg-green-700 hover:bg-green-600 py-4 rounded-xl font-semibold text-lg transition">
                    Создать заказ
                </button>
            </form>

        <?php else: ?>
            <div class="text-center py-20 text-gray-400">
                Корзина пуста<br>Добавьте блюда из меню слева
            </div>
        <?php endif; ?>

    </section>

</main>

<?php if (!empty($_SESSION['waiter_flash_err'])): ?>
<div class="fixed bottom-8 right-8 bg-red-700 px-6 py-4 rounded-xl shadow-2xl z-50">
    <?= htmlspecialchars($_SESSION['waiter_flash_err']) ?>
</div>
<?php unset($_SESSION['waiter_flash_err']); endif; ?>

</body>
</html>
