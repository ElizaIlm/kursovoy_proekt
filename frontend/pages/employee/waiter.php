<?php
session_start();


if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'waiter') {
    header("Location: ../auth/login.php");
    exit;
}

define('ROOT', dirname(__DIR__, 3));

require_once ROOT . "/settings/connect_database.php";
require_once ROOT . "/backend/Controllers/DishController.php";
require_once ROOT . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dishController = new DishController($mysql_connection);
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

    header("Location: waiter.php");
    exit;
}

if ($action === 'remove' && isset($_GET['dish_id'])) {

    $dish_id = (int)$_GET['dish_id'];
    unset($_SESSION['waiter_cart'][$dish_id]);

    header("Location: waiter.php");
    exit;
}

if ($action === 'create' && !empty($_SESSION['waiter_cart'])) {

    $mysql_connection->begin_transaction();

    try {

        $total = 0;

        foreach ($_SESSION['waiter_cart'] as $item) {
            $total += $item['price'] * $item['qty'];
        }

        $stmt = $mysql_connection->prepare("
            INSERT INTO orders (order_datetime, client_id, employee_id, total_amount)
            VALUES (NOW(), NULL, ?, ?)
        ");

        $stmt->bind_param("id", $_SESSION['user_id'], $total);
        $stmt->execute();

        $order_id = $mysql_connection->insert_id;

        $stmt_items = $mysql_connection->prepare("
            INSERT INTO order_items (order_id, dish_id, quantity, price_at_order)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($_SESSION['waiter_cart'] as $item) {

            $stmt_items->bind_param(
                "iiid",
                $order_id,
                $item['id'],
                $item['qty'],
                $item['price']
            );

            $stmt_items->execute();
        }

        $mysql_connection->commit();

        $_SESSION['waiter_cart'] = [];

        $successMsg = "Заказ #$order_id успешно создан";

    } catch (Exception $e) {

        $mysql_connection->rollback();
        $errorMsg = "Ошибка создания заказа";

    }

    header("Location: waiter.php");
    exit;
}

if ($action === 'export_today') {

    $today = date('Y-m-d');

    $query = "
        SELECT 
            o.id,
            o.order_datetime,
            o.total_amount,
            GROUP_CONCAT(
                CONCAT(d.name, ' ×', oi.quantity)
                SEPARATOR ', '
            ) AS items
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN dishes d ON d.id = oi.dish_id
        WHERE o.employee_id = ?
        AND DATE(o.order_datetime) = ?
        GROUP BY o.id
        ORDER BY o.order_datetime DESC
    ";

    $stmt = $mysql_connection->prepare($query);
    $stmt->bind_param("is", $_SESSION['user_id'], $today);
    $stmt->execute();

    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', '№ заказа');
    $sheet->setCellValue('B1', 'Время');
    $sheet->setCellValue('C1', 'Блюда');
    $sheet->setCellValue('D1', 'Сумма');

    $row = 2;
    $total = 0;

    foreach ($orders as $order) {

        $sheet->setCellValue("A$row", $order['id']);
        $sheet->setCellValue("B$row", $order['order_datetime']);
        $sheet->setCellValue("C$row", $order['items']);
        $sheet->setCellValue("D$row", $order['total_amount']);

        $total += $order['total_amount'];

        $row++;
    }

    $sheet->setCellValue("C$row", "ИТОГО");
    $sheet->setCellValue("D$row", $total);

    $sheet->getStyle("A1:D1")->getFont()->setBold(true);
    $sheet->getStyle("C$row:D$row")->getFont()->setBold(true);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="orders_' . $today . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель официанта</title>
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
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <span class="text-red-600 text-3xl font-bold">プレミアム寿司</span>
        <div class="flex items-center gap-6">
            <span>Официант: <?= htmlspecialchars($_SESSION['user_name'] ?? '—') ?></span>
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

        <div class="mt-10 pt-6 border-t border-gray-700">
            <h3 class="text-xl font-semibold mb-4">Мои заказы</h3>
            <a href="?action=export_today" class="block bg-blue-900/70 hover:bg-blue-800 text-center py-3 rounded-xl font-medium">
                Скачать отчёт за сегодня (.xlsx)
            </a>
        </div>
    </section>

</main>

<?php if (isset($successMsg)): ?>
<div class="fixed bottom-8 right-8 bg-green-700 px-6 py-4 rounded-xl shadow-2xl z-50">
    <?= htmlspecialchars($successMsg) ?>
</div>
<?php endif; ?>

<?php if (isset($errorMsg)): ?>
<div class="fixed bottom-8 right-8 bg-red-700 px-6 py-4 rounded-xl shadow-2xl z-50">
    <?= htmlspecialchars($errorMsg) ?>
</div>
<?php endif; ?>

</body>
</html>