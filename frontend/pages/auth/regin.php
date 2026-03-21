<?php
require_once("../../../backend/Controllers/RegisterController.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = $_POST["full_name"] ?? "";
    $email = $_POST["email"] ?? "";
    $phone = $_POST["phone"] ?? "";
    $password = $_POST["password"] ?? "";

    $controller = new RegisterController($mysql_connection);
    $result = $controller->register($full_name, $email, $phone, $password);

    $message = $result["message"];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>

    <style>
        body{
            font-family: Arial, sans-serif;
            background:#f5f5f5;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }

        .register-box{
            background:white;
            padding:30px;
            border-radius:8px;
            width:320px;
            box-shadow:0 0 10px rgba(0,0,0,0.1);
        }

        h2{
            text-align:center;
            margin-bottom:20px;
        }

        input{
            width:100%;
            padding:10px;
            margin-bottom:12px;
            border:1px solid #ccc;
            border-radius:4px;
        }

        button{
            width:100%;
            padding:10px;
            background:#28a745;
            color:white;
            border:none;
            border-radius:4px;
            cursor:pointer;
        }

        button:hover{
            background:#218838;
        }

        .message{
            text-align:center;
            margin-bottom:10px;
            color:red;
        }
    </style>

</head>
<body>

<div class="register-box">

    <h2>Регистрация</h2>

    <?php if(!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST">

        <input type="text" name="full_name" placeholder="ФИО" required>

        <input type="email" name="email" placeholder="Email">

        <input type="text" name="phone" placeholder="Телефон" required>

        <input type="password" name="password" placeholder="Пароль" required>

        <button type="submit">Зарегистрироваться</button>

    </form>

</div>

</body>
</html>