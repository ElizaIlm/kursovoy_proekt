<?php

class OrderItem
{
    public $id;
    public $order_id;
    public $dish_id;
    public $quantity;
    public $price_at_order;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->order_id = $data['order_id'] ?? null;
        $this->dish_id = $data['dish_id'] ?? null;
        $this->quantity = $data['quantity'] ?? 1;
        $this->price_at_order = $data['price_at_order'] ?? 0.0;
    }
}