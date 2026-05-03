<?php
    session_start();
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        header("Location: ../../index.php");
        exit;
    }
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ваш заказ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'sushi-red':    '#c8102e',
                        'sushi-dark':   '#0f1419',
                        'sushi-darker': '#080c0f',
                        'sushi-gold':   '#d4a017',
                        'sushi-soy':    '#3c2f2f',
                    },
                    fontFamily: {
                        'sans':     ['Inter', 'system-ui', 'sans-serif'],
                        'japanese': ['Noto Sans JP', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(to bottom right, #0f1419, #080c0f);
            background-attachment: fixed;
        }
        .glass {
            background: rgba(15, 20, 25, 0.65);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.06);
        }
    </style>
</head>
<body class="min-h-screen text-gray-100 font-sans">

<div class="max-w-4xl mx-auto px-5 py-10">

    <h1 class="text-4xl font-bold japanese text-center mb-8">Ваш <span class="text-sushi-red">заказ</span></h1>

    <div class="glass rounded-3xl p-8">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-700">
                    <th class="text-left py-3">Блюдо</th>
                    <th class="text-center py-3">Кол-во</th>
                    <th class="text-right py-3">Цена</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="cart-items">
                <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                <tr class="border-b border-gray-700">
                    <td class="py-4"><?= htmlspecialchars($item['name']) ?></td>
                    <td class="text-center">
                        <button onclick="changeQtyInOrder(<?= $id ?>, -1)" class="px-3 py-1 bg-gray-700 rounded">-</button>
                        <span class="mx-4 font-semibold"><?= $item['qty'] ?></span>
                        <button onclick="changeQtyInOrder(<?= $id ?>, 1)" class="px-3 py-1 bg-gray-700 rounded">+</button>
                    </td>
                    <td class="text-right font-medium"><?= $item['price'] * $item['qty'] ?> ₽</td>
                    <td class="text-right">
                        <button onclick="removeItem(<?= $id ?>)" class="text-red-400 hover:text-red-500">✕</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="flex justify-between mt-8 text-xl">
            <span class="font-semibold">Итого:</span>
            <span id="total" class="font-bold text-sushi-gold">
                <?= array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $_SESSION['cart'])) ?> ₽
            </span>
        </div>

        <div class="mt-10 flex gap-4">
            <a href="../../index.php" class="flex-1 text-center py-4 bg-gray-700 hover:bg-gray-600 rounded-2xl font-medium">Продолжить выбор</a>
            <form action="checkout.php" method="POST" class="flex-1">
                <button class="w-full py-4 bg-sushi-red hover:bg-red-700 rounded-2xl font-medium text-lg">
                    Оформить заказ
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function changeQtyInOrder(id, delta) {
        fetch('add_to_cart.php', {method:'POST', body: JSON.stringify({dish_id: id, delta})})
            .then(() => location.reload());
    }
    function removeItem(id) {
        fetch('add_to_cart.php', {method:'POST', body: JSON.stringify({dish_id: id, qty: 0})})
            .then(() => location.reload());
    }
</script>

</body>
</html>