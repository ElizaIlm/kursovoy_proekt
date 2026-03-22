<?php

session_start();

require_once("../../../backend/Controllers/OrderController.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit;
}

if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    header("Location: ../../index.php");
    exit;
}

$orderController = new OrderController($mysql_connection);

$order_id = $orderController->createOrder(
    $_SESSION["user_id"],
    $_SESSION["cart"]
);

if ($order_id) {

    $_SESSION["cart"] = [];

    header("Location: success.php?order_id=" . $order_id);
    exit;
}

echo "Ошибка оформления заказа";
?>