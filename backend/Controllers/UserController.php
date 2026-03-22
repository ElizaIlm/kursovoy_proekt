<?php

require_once(__DIR__ . '/../../settings/connect_database.php');

class UserController
{
    private $db;

    public function __construct($mysql_connection)
    {
        $this->db = $mysql_connection;
    }

    public function getUserById($id)
    {
        $stmt = $this->db->prepare("SELECT id, full_name, email, phone FROM clients WHERE id = ?");

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getUserOrders($client_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                o.id,
                o.order_datetime,
                o.total_amount,
                COUNT(oi.id) as items_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.client_id = ?
            GROUP BY o.id
            ORDER BY o.order_datetime DESC
        ");
    
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
    
        $result = $stmt->get_result();
    
        $orders = [];
    
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    
        return $orders;
    }

    public function updateProfile($id, $full_name, $email, $phone, $password = null)
    {

        if (empty($full_name)) {
            return [
                "success" => false,
                "message" => "ФИО не может быть пустым"
            ];
        }

        if (!empty($email)) {

            $stmt = $this->db->prepare("
                SELECT id FROM clients
                WHERE email = ? AND id != ?
            ");

            $stmt->bind_param("si", $email, $id);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                return [
                    "success" => false,
                    "message" => "Этот email уже используется"
                ];
            }
        }

        if (!empty($phone)) {

            $stmt = $this->db->prepare("
                SELECT id FROM clients
                WHERE phone = ? AND id != ?
            ");

            $stmt->bind_param("si", $phone, $id);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                return [
                    "success" => false,
                    "message" => "Этот телефон уже используется"
                ];
            }
        }

        if ($password) {

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("
                UPDATE clients
                SET full_name = ?, email = ?, phone = ?, password_hash = ?
                WHERE id = ?
            ");

            $stmt->bind_param("ssssi", $full_name, $email, $phone, $password_hash, $id);

        } else {

            $stmt = $this->db->prepare("
                UPDATE clients
                SET full_name = ?, email = ?, phone = ?
                WHERE id = ?
            ");

            $stmt->bind_param("sssi", $full_name, $email, $phone, $id);
        }

        if ($stmt->execute()) {

            return [
                "success" => true,
                "message" => "Профиль успешно обновлён"
            ];

        }

        return [
            "success" => false,
            "message" => "Ошибка обновления профиля"
        ];
    }

    public function createUser($full_name, $email, $phone, $password)
    {

        if (empty($full_name) || empty($password)) {

            return [
                "success" => false,
                "message" => "Введите имя и пароль"
            ];
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO clients (full_name, email, phone, password_hash)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param("ssss", $full_name, $email, $phone, $password_hash);

        if ($stmt->execute()) {

            return [
                "success" => true,
                "message" => "Пользователь создан"
            ];
        }

        return [
            "success" => false,
            "message" => "Ошибка создания пользователя"
        ];
    }
}