<?php

class Order
{
    public $id;
    public $order_datetime;
    public $client_id;        
    public $employee_id;
    public $total_amount;
    
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->order_datetime = $data['order_datetime'] ?? null;
        $this->client_id = $data['client_id'] ?? null;
        $this->employee_id = $data['employee_id'] ?? null;
        $this->total_amount = $data['total_amount'] ?? 0.0;
    }
}
?>