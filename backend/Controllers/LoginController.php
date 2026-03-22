<?php
    session_start();
    require_once(__DIR__ . '/../../settings/connect_database.php');
    require_once(__DIR__ . '/../Models/Client.php');
    require_once(__DIR__ . '/../Models/Employee.php');

    class LoginController
    {
        private $db;

        public function __construct($mysql_connection)
        {
            $this->db = $mysql_connection;
        }

        public function login($login, $password)
        {
            if (empty($login) || empty($password)) {
                return [
                    "success" => false,
                    "message" => "Введите телефон или email и пароль"
                ];
            }

            $stmt = $this->db->prepare(
                "SELECT * FROM clients WHERE phone = ? OR email = ?"
            );

            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();

            $result = $stmt->get_result();
            $client = $result->fetch_assoc();

            if ($client && password_verify($password, $client["password_hash"])) {

                $_SESSION["user_type"] = "client";
                $_SESSION["user_id"] = $client["id"];
                $_SESSION["user_name"] = $client["full_name"];

                return [
                    "success" => true,
                    "redirect" => "../../index.php"
                ];
            }

            $stmt = $this->db->prepare("SELECT * FROM employees WHERE login = ?");

            $stmt->bind_param("s", $login);
            $stmt->execute();

            $result = $stmt->get_result();
            $employee = $result->fetch_assoc();

            if ($employee && password_verify($password, $employee["password_hash"])) {

                $_SESSION["user_type"] = "employee";
                $_SESSION["user_id"] = $employee["id"];
                $_SESSION["user_name"] = $employee["full_name"];
                $_SESSION["role"] = $employee["role"];

                return [
                    "success" => true,
                    "redirect" => "../../index.php"
                ];
            }

            return [
                "success" => false,
                "message" => "Неверный логин или пароль"
            ];
        }

        public function logout()
        {
            session_unset();
            session_destroy();

            header("Location: ../../../frontend/index.php");
            exit();
        }
    }
?>