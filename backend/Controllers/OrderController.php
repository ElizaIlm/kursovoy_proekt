<?php

require_once(__DIR__ . '/../../settings/connect_database.php');

class OrderController
{
    private $db;

    /** @return string[] */
    public static function allowedStatuses(): array
    {
        return ['new', 'preparing', 'ready', 'completed'];
    }

    public static function statusLabelRu(string $status): string
    {
        $map = [
            'new' => 'Новый',
            'preparing' => 'Готовится',
            'ready' => 'Готов к подаче',
            'completed' => 'Завершён',
        ];

        return $map[$status] ?? $status;
    }

    public function __construct($mysql_connection)
    {
        $this->db = $mysql_connection;
    }

    private function getFreeWaiter()
    {
        $result = $this->db->query("
            SELECT e.id, COUNT(o.id) as order_count
            FROM employees e
            LEFT JOIN orders o ON e.id = o.employee_id
            WHERE e.role = 'waiter'
            GROUP BY e.id
        ");

        $waiters = [];
        $minOrders = PHP_INT_MAX;

        while ($row = $result->fetch_assoc()) {

            if ($row['order_count'] < $minOrders) {
                $minOrders = $row['order_count'];
                $waiters = [$row['id']];
            } 
            else if ($row['order_count'] == $minOrders) {
                $waiters[] = $row['id'];
            }
        }

        if (empty($waiters)) {
            return null;
        }

        return $waiters[array_rand($waiters)];
    }

    public function createOrder($client_id, $cart)
    {
        if (empty($cart)) {
            return false;
        }

        $this->db->begin_transaction();

        try {

            $total = 0;
            foreach ($cart as $item) {
                $total += $item['price'] * $item['qty'];
            }

            $employee_id = $this->getFreeWaiter();

            if (!$employee_id) {
                throw new Exception("Нет доступных официантов");
            }

            $stmt = $this->db->prepare("
                INSERT INTO orders (order_datetime, client_id, employee_id, total_amount, status)
                VALUES (NOW(), ?, ?, ?, 'new')
            ");

            $stmt->bind_param("iid", $client_id, $employee_id, $total);
            $stmt->execute();

            $order_id = $this->db->insert_id;

            $stmt = $this->db->prepare("
                INSERT INTO order_items (order_id, dish_id, quantity, price_at_order)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($cart as $dish_id => $item) {

                $qty = $item['qty'];
                $price = $item['price'];

                $stmt->bind_param("iiid", $order_id, $dish_id, $qty, $price);
                $stmt->execute();
            }

            $this->db->commit();

            return $order_id;

        } catch (Exception $e) {

            $this->db->rollback();
            return false;
        }
    }

    /**
     * Заказ «с зала»: официант сам себе назначен исполнителем.
     */
    public function createOrderByWaiter(int $employee_id, array $cart)
    {
        if (empty($cart)) {
            return false;
        }

        $this->db->begin_transaction();

        try {

            $total = 0;
            foreach ($cart as $item) {
                $total += $item['price'] * $item['qty'];
            }

            $stmt = $this->db->prepare("
                INSERT INTO orders (order_datetime, client_id, employee_id, total_amount, status)
                VALUES (NOW(), NULL, ?, ?, 'new')
            ");

            $stmt->bind_param("id", $employee_id, $total);
            $stmt->execute();

            $order_id = $this->db->insert_id;

            $stmt = $this->db->prepare("
                INSERT INTO order_items (order_id, dish_id, quantity, price_at_order)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($cart as $item) {

                $dish_id = (int)$item['id'];
                $qty = $item['qty'];
                $price = $item['price'];

                $stmt->bind_param("iiid", $order_id, $dish_id, $qty, $price);
                $stmt->execute();
            }

            $this->db->commit();

            return $order_id;

        } catch (Exception $e) {

            $this->db->rollback();
            return false;
        }
    }

    public function updateStatusForWaiter(int $order_id, int $waiter_id, string $status): bool
    {
        if (!in_array($status, self::allowedStatuses(), true)) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE orders SET status = ?
            WHERE id = ? AND employee_id = ?
        ");

        $stmt->bind_param("sii", $status, $order_id, $waiter_id);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }
}