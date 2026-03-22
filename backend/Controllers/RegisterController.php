<?php
    require_once(__DIR__ . '/../../settings/connect_database.php');
    require_once(__DIR__ . '/../Models/Client.php');

    class RegisterController 
    {
        private $db;

        public function __construct($mysql_connection)
        {
            $this->db = $mysql_connection;
        }

        public function register($full_name, $email, $phone, $password)
        {
            if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
                return [
                    "success" => false,
                    "message" => "Заполните обязательные поля"
                ];
            }
            if (!empty($email)) {
                $stmt = $this->db->prepare("SELECT `id` FROM `clients` WHERE `email` = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    return [
                        "success" => false,
                        "message" => "Пользователь с таким email уже существует"
                    ];
                }
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $date = date("Y:m:d H:i:s");

            $stmt = $this->db->prepare("INSERT INTO `clients` (`full_name`, `email`, `phone`, `password_hash`, `created_at`) VALUES (?, ?, ?, ?, ?)");

            $stmt->bind_param("sssss", $full_name, $email, $phone, $password_hash, $date);

            if ($stmt->execute()) {
                return [
                    "success" => true,
                    "message" => "Регистрация прошла успешно"
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Ошибка регистрации"
                ];
            }
        }
    }
?>