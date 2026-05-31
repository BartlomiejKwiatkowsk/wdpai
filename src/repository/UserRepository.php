<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/User.php';

class UserRepository extends Repository {

    public function getUser(string $email): ?User {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM public.users WHERE email = :email
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user == false) {
            return null;
        }

        return new User(
            $user['email'],
            $user['password_hash'],
            $user['role']
        );
    }

    public function addUser(User $user): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO public.users (email, password_hash, role)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([
            $user->getEmail(),
            $user->getPassword(),
            $user->getRole()
        ]);
    }

    // Nowe metody dla panelu Admina
    public function getAllUsers(): array {
        $stmt = $this->database->connect()->prepare('
            SELECT id_user, email, role, created_at 
            FROM public.users 
            ORDER BY created_at DESC
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteUser(string $id): void {
        $stmt = $this->database->connect()->prepare('DELETE FROM public.users WHERE id_user = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function updateUserRole(string $id, string $role): void {
        $stmt = $this->database->connect()->prepare('UPDATE public.users SET role = :role WHERE id_user = :id');
        $stmt->execute([':role' => $role, ':id' => $id]);
    }

    public function updateUserPassword(string $id, string $newHash): void {
        $stmt = $this->database->connect()->prepare('UPDATE public.users SET password_hash = :hash WHERE id_user = :id');
        $stmt->execute([':hash' => $newHash, ':id' => $id]);
    }
}