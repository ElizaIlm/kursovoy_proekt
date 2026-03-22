<?php
    session_start();
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    $id = $data['dish_id'];

    if (isset($data['qty']) && $data['qty'] == 0) {
        unset($_SESSION['cart'][$id]);
    } else if (isset($data['delta'])) {
        $_SESSION['cart'][$id]['qty'] += $data['delta'];
        if ($_SESSION['cart'][$id]['qty'] <= 0) unset($_SESSION['cart'][$id]);
    } else {
        $_SESSION['cart'][$id] = [
            'name'  => $data['name'],
            'price' => $data['price'],
            'qty'   => ($_SESSION['cart'][$id]['qty'] ?? 0) + 1
        ];
    }

    echo json_encode(['success' => true]);
?>