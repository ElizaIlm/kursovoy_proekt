<?php
    session_start();
    require_once("../backend/Controllers/DishController.php");

    $dishController = new DishController($mysql_connection);
    $dishes = $dishController->getAllDishes();

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
?>
<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Меню</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'sushi-red':   '#c8102e',
                        'sushi-dark':  '#0f1419',
                        'sushi-darker':'#080c0f',
                        'sushi-gold':  '#d4a017',
                        'sushi-soy':   '#3c2f2f',
                    },
                    fontFamily: {
                        'sans':    ['Inter', 'system-ui', 'sans-serif'],
                        'japanese':['Noto Sans JP', 'sans-serif'],
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
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 35px rgba(200, 16, 46, 0.18);
        }
        .btn-glow:hover {
            box-shadow: 0 0 25px rgba(200, 16, 46, 0.5);
        }
        .dish-img {
            height: 220px;
            object-fit: cover;
            object-position: center;
        }
        .qty-btn { 
            width: 32px; height: 32px; 
        }
    </style>
</head>
<body class="min-h-screen text-gray-100 font-sans">

    <header class="bg-sushi-darker border-b border-gray-800/50 sticky top-0 z-50 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-5 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <span class="text-3xl font-bold japanese font-japanese">
                    <span class="text-sushi-red">プレミアム寿司</span>
                </span>
            </div>

            <div class="flex items-center gap-6 text-sm md:text-base">
                <?php if (!isset($_SESSION["user_id"])): ?>
                    <a href="pages/auth/login.php" class="text-gray-300 hover:text-white transition">Войти</a>
                    <a href="pages/auth/regin.php" class="text-gray-300 hover:text-sushi-gold transition">Регистрация</a>
                <?php else: ?>
                    <a href="pages/order/order.php" id="cart-link" class="flex items-center gap-2 hover:text-sushi-gold transition relative text-2xl">
                        <span class="relative">
                            <span class="text-3xl">🛒</span>
                            <span id="cart-count"
                                  class="bg-sushi-red text-white text-xs font-bold px-2 py-0.5 rounded-full absolute -top-1 -right-1">
                                <?= array_sum(array_column($_SESSION['cart'], 'qty')) ?>
                            </span>
                        </span>
                    </a>
                    <a href="pages/client/profile.php" class="text-gray-300 hover:text-sushi-gold transition">
                        Здравствуйте, <?= htmlspecialchars($_SESSION["user_name"]) ?>
                    </a>
                    <a href="pages/auth/login.php?logout=1" class="text-gray-300 hover:text-red-400 transition">Выйти</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-5 py-12 md:py-16">

        <div class="text-center mb-12 md:mb-16">
            <h1 class="text-4xl md:text-5xl font-bold japanese font-japanese mb-3">
                Наше <span class="text-sushi-red">меню</span>
            </h1>
            <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                Свежие ингредиенты • Авторские сочетания
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 md:gap-8">

            <?php foreach ($dishes as $dish): 
                $inCart = $_SESSION['cart'][$dish->id] ?? null;    
            ?>
                <div class="glass rounded-2xl overflow-hidden card-hover shadow-xl flex flex-col h-full">

                    <div class="relative">
                        <img  src="<?= htmlspecialchars($dish->image_path) ?>"  alt="<?= htmlspecialchars($dish->name) ?>"  class="dish-img w-full" loading="lazy">
                        <div class="absolute top-3 right-3 bg-sushi-red text-white text-xs font-bold px-3 py-1 rounded-full">
                            <?= number_format($dish->price, 0, '', ' ') ?> ₽
                        </div>
                    </div>

                    <div class="p-5 md:p-6 flex flex-col flex-grow">
                        <h3 class="text-xl font-semibold mb-2 line-clamp-2">
                            <?= htmlspecialchars($dish->name) ?>
                        </h3>

                        <p class="text-gray-400 text-sm mb-3 line-clamp-3 flex-grow">
                            <?= htmlspecialchars($dish->description ?: $dish->composition ?: 'Свежеприготовленное блюдо из качественных ингредиентов') ?>
                        </p>

                        <?php if (isset($_SESSION["user_id"])): ?>
                            <?php if ($inCart): ?>
                                <div class="flex items-center justify-center gap-1 bg-gray-800 rounded-lg p-1" id="qty-block-<?= $dish->id ?>">
                                    <button onclick="changeQty(<?= $dish->id ?>, -1)" class="qty-btn bg-gray-700 hover:bg-gray-600 rounded">–</button>
                                    <span id="qty-<?= $dish->id ?>" class="w-10 text-center font-semibold"><?= $inCart['qty'] ?></span>
                                    <button onclick="changeQty(<?= $dish->id ?>, 1)" class="qty-btn bg-gray-700 hover:bg-gray-600 rounded">+</button>
                                </div>
                            <?php else: ?>
                                <button onclick="addToCart(<?= $dish->id ?>, '<?= htmlspecialchars($dish->name) ?>', <?= $dish->price ?>)" 
                                        id="add-btn-<?= $dish->id ?>"
                                        class="px-5 py-2 bg-sushi-red hover:bg-red-700 font-medium rounded-lg transition">
                                    В корзину
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="mt-auto text-center text-sm text-gray-500">
                                <a href="pages/auth/login.php" class="text-sushi-gold hover:underline">
                                    Войдите, чтобы добавить в корзину
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($dishes)): ?>
                <div class="col-span-full text-center py-20 text-gray-400">
                    В данный момент меню пусто... скоро добавим новые роллы!
                </div>
            <?php endif; ?>

        </div>

    </main>

    <footer class="bg-sushi-darker border-t border-gray-800/50 py-8 text-center text-sm text-gray-500 mt-12">
        © <?= date("Y") ?> Лучший суши-ресторан
    </footer>
</body>
</html>
<script>
    async function addToCart(id, name, price) {
        await fetch('pages/order/add_to_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({dish_id: id, name, price})
        });

        document.getElementById(`add-btn-${id}`).outerHTML = `
            <div class="flex items-center justify-center gap-1 bg-gray-800 rounded-lg p-1" id="qty-block-${id}">
                <button onclick="changeQty(${id}, -1)" class="qty-btn bg-gray-700 hover:bg-gray-600 rounded">–</button>
                <span id="qty-${id}" class="w-10 text-center font-semibold">1</span>
                <button onclick="changeQty(${id}, 1)" class="qty-btn bg-gray-700 hover:bg-gray-600 rounded">+</button>
            </div>`;
                
        updateCartCount();
    }

    async function changeQty(id, delta) {
        await fetch('pages/order/add_to_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({dish_id: id, delta})
        });

        const qtyEl = document.getElementById(`qty-${id}`);
        let qty = parseInt(qtyEl.textContent) + delta;
                
        if (qty <= 0) {
            document.getElementById(`qty-block-${id}`).outerHTML = `
                <button onclick="addToCart(${id}, 'Название', 999)" id="add-btn-${id}"
                        class="px-5 py-2 bg-sushi-red hover:bg-red-700 font-medium rounded-lg transition">
                    В корзину
                </button>`;
        } else {
            qtyEl.textContent = qty;
        }
        updateCartCount();
    }

    function updateCartCount() {
        fetch('pages/order/get_cart_count.php')
            .then(r => r.text())
            .then(count => {
                document.getElementById('cart-count').textContent = count || 0;
            });
    }

    window.onload = updateCartCount;
</script>