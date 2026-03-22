<?php

class Dish
{
    public $id;
    public $name;
    public $composition;
    public $price;
    public $availability;
    public $description;
    public $image_path;
    public $created_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->composition = $data['composition'] ?? '';
        $this->price = $data['price'] ?? 0.0;
        $this->availability = (bool)($data['availability'] ?? 1);
        $this->description = $data['description'] ?? '';
        $this->image_path = $data['image_path'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }
}
?>