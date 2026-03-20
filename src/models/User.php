<?php

class User {
    private $email;
    private $password;
    private $role;

    public function __construct(string $email, string $password, string $role = 'user') {
        $this->email = $email;
        $this->password = $password;
        // Rola przypisywana domyślnie jako user, ale nadpisana przy wyciąganiu admina z bazy
        $this->role = $role;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getRole(): string {
        return $this->role;
    }
}