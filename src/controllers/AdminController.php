<?php

require_once 'AppController.php';
require_once __DIR__ .'/../repository/UserRepository.php';

class AdminController extends AppController {

    private function enforceAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            require_once 'ErrorController.php';
            $error = new ErrorController();
            $error->error403("Odmowa dostępu. Moduł wymaga uprawnień administratora.");
            exit();
        }
    }

    public function usersPanel() {
        $this->enforceAdmin();

        $userRepository = new UserRepository();
        $users = $userRepository->getAllUsers();

        $this->render('admin-users', [
            'users' => $users,
            'userEmail' => $_SESSION['user_email']
        ]);
    }

    public function updateUserRole() {
        $this->enforceAdmin();

        if ($this->isPost()) {
            $userId = $_POST['user_id'] ?? null;
            $newRole = $_POST['new_role'] ?? null;

            if ($userId && $newRole) {
                $userRepository = new UserRepository();
                $userRepository->updateUserRole($userId, $newRole);
            }
        }
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/users");
        exit();
    }

    public function updateUserPassword() {
        $this->enforceAdmin();

        if ($this->isPost()) {
            $userId = $_POST['user_id'] ?? null;
            $newPassword = $_POST['new_password'] ?? null;

            if ($userId && $newPassword) {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $userRepository = new UserRepository();
                $userRepository->updateUserPassword($userId, $hash);
            }
        }
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/users");
        exit();
    }

    public function deleteUser() {
        $this->enforceAdmin();

        if ($this->isPost()) {
            $userId = $_POST['user_id'] ?? null;

            if ($userId) {
                $userRepository = new UserRepository();
                $userRepository->deleteUser($userId);
            }
        }
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/users");
        exit();
    }
}