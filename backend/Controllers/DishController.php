<?php

require_once(__DIR__ . '/../../settings/connect_database.php');
require_once(__DIR__ . '/../Models/Dish.php');

class DishController
{
    private $db;

    public function __construct($mysql_connection)
    {
        $this->db = $mysql_connection;
    }

    public function getAllDishes()
    {
        $sql = "SELECT * FROM dishes WHERE availability = 1 ORDER BY name";
        $result = $this->db->query($sql);

        $dishes = [];

        while ($row = $result->fetch_assoc()) {
            $dishes[] = new Dish($row);
        }

        return $dishes;
    }

    public function getDishById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM dishes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            return new Dish($row);
        }

        return null;
    }

    public function createDish($name, $composition, $price, $description, $image)
    {
        $imagePath = null;

        if ($image && $image["error"] === 0) {
    
            $uploadDir = __DIR__ . "/../../frontend/img/";
            $fileName = time() . "_" . basename($image["name"]);
            $targetFile = $uploadDir . $fileName;
    
            move_uploaded_file($image["tmp_name"], $targetFile);
    
            $imagePath = "img/" . $fileName;
        }

        $stmt = $this->db->prepare("
            INSERT INTO dishes (name, composition, price, description, image_path, availability, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");

        $stmt->bind_param("ssdss", $name, $composition, $price, $description, $imagePath);

        return $stmt->execute();
    }

    public function updateDish($id, $name, $composition, $price, $description, $availability)
    {
        $stmt = $this->db->prepare("
            UPDATE dishes 
            SET name=?, composition=?, price=?, description=?, availability=? 
            WHERE id=?
        ");

        $stmt->bind_param("ssdssi", $name, $composition, $price, $description, $availability, $id);

        return $stmt->execute();
    }

    public function deleteDish($id)
    {
        $stmt = $this->db->prepare("DELETE FROM dishes WHERE id=?");
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}