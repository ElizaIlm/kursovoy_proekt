<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrator') {
    exit('Нет доступа');
}

define('ROOT', dirname(__DIR__, 3));

require_once ROOT . "/vendor/autoload.php";
require_once ROOT . "/settings/connect_database.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

$orders = $mysql_connection->query("
SELECT o.id, o.order_datetime, o.total_amount,
       c.full_name AS client,
       e.full_name AS waiter
FROM orders o
LEFT JOIN clients c ON c.id = o.client_id
LEFT JOIN employees e ON e.id = o.employee_id
ORDER BY o.order_datetime DESC
");

$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Заказы");

$sheet->fromArray(
    ['ID', 'Дата', 'Сумма', 'Клиент', 'Официант'],
    NULL,
    'A1'
);

$row = 2;

while ($o = $orders->fetch_assoc()) {
    $sheet->fromArray([
        $o['id'],
        $o['order_datetime'],
        $o['total_amount'],
        $o['client'],
        $o['waiter']
    ], NULL, 'A' . $row);

    $row++;
}

$items = $mysql_connection->query("
SELECT oi.order_id, d.name, oi.quantity, oi.price_at_order
FROM order_items oi
JOIN dishes d ON d.id = oi.dish_id
");

$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle("Состав заказов");

$sheet2->fromArray(
    ['Заказ', 'Блюдо', 'Кол-во', 'Цена'],
    NULL,
    'A1'
);

$row = 2;

while ($i = $items->fetch_assoc()) {
    $sheet2->fromArray([
        $i['order_id'],
        $i['name'],
        $i['quantity'],
        $i['price_at_order']
    ], NULL, 'A' . $row);

    $row++;
}

$dishes = $mysql_connection->query("
SELECT name, composition, price, availability
FROM dishes
");

$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle("Блюда");

$sheet3->fromArray(
    ['Название', 'Состав', 'Цена', 'Доступно'],
    NULL,
    'A1'
);

$row = 2;

while ($d = $dishes->fetch_assoc()) {
    $sheet3->fromArray([
        $d['name'],
        $d['composition'],
        $d['price'],
        $d['availability'] ? 'Да' : 'Нет'
    ], NULL, 'A' . $row);

    $row++;
}

$clients = $mysql_connection->query("
SELECT full_name, phone, email
FROM clients
");

$sheet4 = $spreadsheet->createSheet();
$sheet4->setTitle("Клиенты");

$sheet4->fromArray(
    ['ФИО', 'Телефон', 'Email'],
    NULL,
    'A1'
);

$row = 2;

while ($c = $clients->fetch_assoc()) {
    $sheet4->fromArray([
        $c['full_name'],
        $c['phone'],
        $c['email']
    ], NULL, 'A' . $row);

    $row++;
}

$employees = $mysql_connection->query("
SELECT full_name, login, role
FROM employees
");

$sheet5 = $spreadsheet->createSheet();
$sheet5->setTitle("Сотрудники");

$sheet5->fromArray(
    ['ФИО', 'Логин', 'Роль'],
    NULL,
    'A1'
);

$row = 2;

while ($e = $employees->fetch_assoc()) {
    $sheet5->fromArray([
        $e['full_name'],
        $e['login'],
        $e['role']
    ], NULL, 'A' . $row);

    $row++;
}

$top_dishes = $mysql_connection->query("
SELECT d.name, SUM(oi.quantity) as total
FROM order_items oi
JOIN dishes d ON d.id = oi.dish_id
GROUP BY d.id
ORDER BY total DESC
");

$sheet6 = $spreadsheet->createSheet();
$sheet6->setTitle("ТОП блюда");

$sheet6->fromArray(['Блюдо', 'Продано'], NULL, 'A1');

$row = 2;

while ($t = $top_dishes->fetch_assoc()) {
    $sheet6->fromArray([$t['name'], $t['total']], NULL, 'A' . $row);
    $row++;
}

$top_waiters = $mysql_connection->query("
SELECT e.full_name, COUNT(o.id) as total
FROM orders o
JOIN employees e ON e.id = o.employee_id
WHERE e.role = 'waiter'
GROUP BY e.id
ORDER BY total DESC
");

$sheet7 = $spreadsheet->createSheet();
$sheet7->setTitle("ТОП официанты");

$sheet7->fromArray(['Официант', 'Заказов'], NULL, 'A1');

$row = 2;

while ($w = $top_waiters->fetch_assoc()) {
    $sheet7->fromArray([$w['full_name'], $w['total']], NULL, 'A' . $row);
    $row++;
}

$fileName = "full_report_" . date("Y-m-d_H-i") . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$fileName\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;