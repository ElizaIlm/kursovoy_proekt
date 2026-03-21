<?php
class Client
{
    public $id;
    public $full_name;
    public $email;
    public $phone;
    public $password_hash;
    public $created_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->full_name = $data['full_name'] ?? '';
        $this->email = $data['email'] ?? null;
        $this->phone = $data['phone'] ?? '';
        $this->password_hash = $data['password_hash'] ?? '';
        $this->created_at = $data['created_at'] ?? null;
    }
}
?>