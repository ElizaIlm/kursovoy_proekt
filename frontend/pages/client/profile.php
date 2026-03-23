<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once("../../../backend/Controllers/UserController.php");

$userController = new UserController($mysql_connection);
$user = $userController->getUserById($_SESSION['user_id']);
$orders = $userController->getUserOrders($_SESSION['user_id']);

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? ""; 

    $result = $userController->updateProfile(
        $_SESSION['user_id'],
        $full_name,
        $email,
        $phone,
        $password ?: null  
    );

    $message = $result["message"];
    $success = $result["success"] ?? false;

    if ($success) {
        $_SESSION['user_name'] = $full_name;
        $user = $userController->getUserById($_SESSION['user_id']); 
    }
}
?>
<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Личный кабинет</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
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
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.07);
        }
        .input-focus:focus {
            border-color: #c8102e;
            box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.18);
            outline: none;
        }
    </style>
</head>
<body class="min-h-screen text-gray-100 font-sans">

    <header class="bg-sushi-darker border-b border-gray-800/50 sticky top-0 z-50 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-5 py-4 flex justify-between items-center">
            <a href="../../index.php" class="flex items-center gap-3 hover:opacity-90 transition">
                <span class="text-3xl font-bold japanese">
                    <span class="text-sushi-red">プレミアム寿司</span>
                </span>
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-5 py-12">

        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold japanese mb-3">
                Личный кабинет
            </h1>
            <p class="text-gray-400 text-lg">
                Здравствуйте, <span class="text-sushi-gold font-medium"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Гость') ?></span>
            </p>
        </div>

        <div class="glass rounded-2xl p-8 md:p-10 shadow-2xl">

            <?php if ($message): ?>
                <div class="mb-8 p-4 rounded-lg text-center text-sm <?= $success ? 'bg-green-900/40 border border-green-700/50 text-green-300' : 'bg-red-900/40 border border-red-700/50 text-red-300' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">ФИО</label>
                    <input 
                        type="text" 
                        name="full_name" 
                        value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" 
                        required
                        class="input-focus w-full px-5 py-3.5 bg-sushi-darker border border-gray-700 rounded-xl text-white placeholder-gray-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                        class="input-focus w-full px-5 py-3.5 bg-sushi-darker border border-gray-700 rounded-xl text-white placeholder-gray-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Телефон</label>
                                
                    <input 
                        type="text" 
                        name="phone" 
                        value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                        class="input-focus w-full px-5 py-3.5 bg-sushi-darker border border-gray-700 rounded-xl text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Новый пароль (оставьте пустым, если не меняете)</label>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder=""
                        class="input-focus w-full px-5 py-3.5 bg-sushi-darker border border-gray-700 rounded-xl text-white placeholder-gray-500"
                    >
                </div>

                <div class="pt-4">
                    <button 
                        type="submit"
                        class="w-full py-4 bg-sushi-red hover:bg-red-700 text-white font-medium rounded-xl text-lg transition-all duration-300 shadow-lg"
                    >
                        Сохранить изменения
                    </button>
                </div>

            </form>
        </div>

        <div class="glass rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-5">
                <h3 class="font-semibold text-lg">История заказов</h3>
            </div>
            <?php if (empty($orders)): ?>
                <p class="text-gray-400 text-sm">
                    У вас пока нет заказов
                </p>
            <?php else: ?>
                <div class="space-y-4">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-sushi-darker border border-gray-700 rounded-xl p-4 flex justify-between items-center">
                        <div>
                            <div class="font-medium">
                                Заказ #<?= $order['id'] ?>
                            </div>
                            <div class="text-sm text-gray-400">
                                <?= date("d.m.Y H:i", strtotime($order['order_datetime'])) ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sushi-gold font-semibold">
                                <?= number_format($order['total_amount'], 0, '', ' ') ?> ₽
                            </div>
                            <div class="text-xs text-gray-400">
                                <?= $order['items_count'] ?> блюд
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="text-center py-8 text-gray-600 text-sm border-t border-gray-800/50 mt-12">
        © <?= date("Y") ?> Лучший суши-ресторан
    </footer>

</body>
</html>