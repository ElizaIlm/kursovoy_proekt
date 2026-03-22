<?php
    require_once("../../../backend/Controllers/LoginController.php");
    $message = "";
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $login = $_POST["login"] ?? "";
        $password = $_POST["password"] ?? "";
        $controller = new LoginController($mysql_connection);
        $result = $controller->login($login, $password);

        if ($result["success"]) {
            header("Location: " . $result["redirect"]);
            exit();
        }
        $message = $result["message"];
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>

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
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.07);
        }
        .input-focus {
            transition: all 0.25s ease;
        }
        .input-focus:focus {
            border-color: #c8102e;
            box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.18);
            outline: none;
        }
        .btn-glow:hover {
            box-shadow: 0 0 25px rgba(200, 16, 46, 0.45);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-5 font-sans text-gray-100">

<div class="w-full max-w-md">
    <div class="text-center mb-8">
            <div class="inline-block text-5xl font-bold tracking-tight japanese font-japanese">
                <span class="text-sushi-red">プレミアム寿司</span>
            </div>
            <p class="mt-2 text-gray-400 text-sm">Авторизация</p>
        </div>

        <div class="glass rounded-2xl p-8 md:p-10 shadow-2xl">
            <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 bg-red-900/40 border border-red-700/50 rounded-lg text-red-300 text-center text-sm">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-5">

                <div>
                    <label for="login" class="block text-sm font-medium text-gray-300 mb-1.5">Телефон или Email</label>
                    <input id="login" name="login" type="text" required class="input-focus w-full px-4 py-3.5 bg-sushi-darker border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-sushi-red transition-all" />
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">Пароль</label>
                    <input id="password" name="password" type="password" required class="input-focus w-full px-4 py-3.5 bg-sushi-darker border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-sushi-red transition-all"/>
                </div>

                <button type="submit" class="btn-glow w-full py-4 bg-sushi-red hover:bg-red-700 text-white font-medium rounded-lg text-base uppercase tracking-wider transition-all duration-300 shadow-md">Войти</button>

                <div class="mt-6 text-center space-y-3 text-sm">
                    Ещё не зарегистрированы? <a href="regin.php" class="text-gray-400 hover:text-sushi-gold transition-colors">Создать аккаунт</a>
                </div>
            </form>
        </div>

        <p class="text-center text-xs text-gray-600 mt-8">
            © <?= date("Y") ?> Лучший суши-ресторан • С любовью к Японии
        </p>
</div>
</body>
</html>