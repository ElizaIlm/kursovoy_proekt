<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'waiter') {
    header("Location: ../auth/login.php");
    exit;
}

define('ROOT', dirname(__DIR__, 3));

require_once ROOT . "/settings/connect_database.php";
require_once ROOT . "/backend/Controllers/OrderController.php";
require_once ROOT . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$orderController = new OrderController($mysql_connection);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'update_status' && isset($_POST['order_id'], $_POST['status'])) {

    $order_id = (int)$_POST['order_id'];
    $status = (string)$_POST['status'];

    $orderController->updateStatusForWaiter($order_id, (int)$_SESSION['user_id'], $status);

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
            o.status,
            GROUP_CONCAT(
                CONCAT(d.name, ' ×', oi.quantity)
                SEPARATOR ', '
            ) AS items
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN dishes d ON d.id = oi.dish_id
        WHERE o.employee_id = ?
        AND DATE(o.order_datetime) = ?
        GROUP BY o.id, o.order_datetime, o.total_amount, o.status
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
    $sheet->setCellValue('C1', 'Статус');
    $sheet->setCellValue('D1', 'Блюда');
    $sheet->setCellValue('E1', 'Сумма');

    $row = 2;
    $total = 0;

    foreach ($orders as $order) {

        $sheet->setCellValue("A$row", $order['id']);
        $sheet->setCellValue("B$row", $order['order_datetime']);
        $sheet->setCellValue("C$row", OrderController::statusLabelRu($order['status'] ?? 'new'));
        $sheet->setCellValue("D$row", $order['items']);
        $sheet->setCellValue("E$row", $order['total_amount']);

        $total += $order['total_amount'];

        $row++;
    }

    $sheet->setCellValue("D$row", "ИТОГО");
    $sheet->setCellValue("E$row", $total);

    $sheet->getStyle("A1:E1")->getFont()->setBold(true);
    $sheet->getStyle("D$row:E$row")->getFont()->setBold(true);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="orders_' . $today . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

$stmt = $mysql_connection->prepare("
    SELECT id, order_datetime, total_amount, client_id, status
    FROM orders
    WHERE employee_id = ?
    ORDER BY order_datetime DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$my_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$allowed_statuses = OrderController::allowedStatuses();

$flash_ok = $_SESSION['waiter_flash_ok'] ?? null;
$flash_err = $_SESSION['waiter_flash_err'] ?? null;
unset($_SESSION['waiter_flash_ok'], $_SESSION['waiter_flash_err']);
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
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center flex-wrap gap-4">
        <span class="text-red-600 text-3xl font-bold">プレミアム寿司</span>
        <div class="flex items-center gap-6 flex-wrap">
            <a href="waiter_new_order.php" class="bg-red-700 hover:bg-red-600 px-4 py-2 rounded-lg font-medium transition">
                Новый заказ
            </a>
            <span class="text-gray-300">Официант: <?= htmlspecialchars($_SESSION['user_name'] ?? '—') ?></span>
            <a href="../auth/login.php?logout=1" class="text-red-400 hover:text-red-300">Выйти</a>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-10">

    <section class="glass rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h2 class="text-2xl font-bold">Мои заказы</h2>
            <a href="?action=export_today" class="bg-blue-900/70 hover:bg-blue-800 text-center py-3 px-6 rounded-xl font-medium shrink-0">
                Скачать отчёт за сегодня (.xlsx)
            </a>
        </div>

        <?php if (empty($my_orders)): ?>
            <p class="text-gray-400 text-center py-16">Заказов пока нет. Создайте первый через «Новый заказ».</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-gray-800/70">
                        <tr>
                            <th class="p-3 text-left">№</th>
                            <th class="p-3 text-left">Дата</th>
                            <th class="p-3 text-left">Сумма</th>
                            <th class="p-3 text-left">Статус</th>
                            <th class="p-3 text-left"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_orders as $o):
                            $st = $o['status'] ?? 'new';
                        ?>
                        <tr class="border-b border-gray-800 align-top">
                            <td class="p-3 font-medium"><?= (int)$o['id'] ?></td>
                            <td class="p-3 whitespace-nowrap"><?= date('d.m.Y H:i', strtotime($o['order_datetime'])) ?></td>
                            <td class="p-3 text-yellow-500 font-semibold"><?= number_format((float)$o['total_amount'], 0, '', ' ') ?> ₽</td>
                            <td class="p-3">
                                <form method="post" class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                    <select name="status" class="bg-gray-800 border border-gray-700 rounded px-3 py-2 min-w-[180px]">
                                        <?php foreach ($allowed_statuses as $code): ?>
                                            <option value="<?= htmlspecialchars($code) ?>" <?= $st === $code ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(OrderController::statusLabelRu($code)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg whitespace-nowrap">
                                        Сохранить
                                    </button>
                                </form>
                            </td>
                            <td class="p-3">
                                <a href="order_details.php?id=<?= (int)$o['id'] ?>" class="text-blue-400 hover:text-blue-300 whitespace-nowrap">Подробнее</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</main>

<?php if ($flash_ok): ?>
<div class="fixed bottom-8 right-8 bg-green-700 px-6 py-4 rounded-xl shadow-2xl z-50">
    <?= htmlspecialchars($flash_ok) ?>
</div>
<?php endif; ?>

<?php if ($flash_err): ?>
<div class="fixed bottom-8 right-8 bg-red-700 px-6 py-4 rounded-xl shadow-2xl z-50">
    <?= htmlspecialchars($flash_err) ?>
</div>
<?php endif; ?>

</body>
</html>
