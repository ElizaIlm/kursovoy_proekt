<?php
class Employee
{
    public $id;
    public $full_name;
    public $role;
    public $login;
    public $password_hash;
    public $created_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->full_name = $data['full_name'] ?? '';
        $this->role = $data['role'] ?? 'waiter';
        $this->login = $data['login'] ?? '';
        $this->password_hash = $data['password_hash'] ?? '';
        $this->created_at = $data['created_at'] ?? null;
    }
}
?>