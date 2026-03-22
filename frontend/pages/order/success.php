<?php
    $order_id = $_GET["order_id"] ?? null;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Заказ оформлен</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-900 text-white">
    <div class="text-center">
        <h1 class="text-4xl font-bold text-green-400 mb-4">Заказ оформлен</h1>
        <p class="text-lg mb-6">Номер заказа: <b>#<?= $order_id ?></b></p>
        <a href="../../index.php" class="px-6 py-3 bg-red-600 rounded-lg hover:bg-red-700">Вернуться в меню</a>
    </div>
</body>
</html>