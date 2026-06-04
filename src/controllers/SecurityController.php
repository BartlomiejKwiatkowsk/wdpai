<?php

require_once 'AppController.php';
require_once __DIR__ .'/../models/User.php';
require_once __DIR__ .'/../repository/UserRepository.php';

class SecurityController extends AppController {

    public function login() {
        $userRepository = new UserRepository();

        // Startujemy sesję, żeby mieć gdzie zapisać token
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$this->isPost()) {
            // Generowanie tokena CSRF przed wyrenderowaniem widoku
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $this->render('login');
        }

        // [B2] Weryfikacja tokena CSRF przy żądaniu POST
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            return $this->render('login', ['messages' => ['Błąd bezpieczeństwa (CSRF). Spróbuj ponownie.']]);
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        $user = $userRepository->getUser($email);

        // [B1] Generyczny komunikat
        if (!$user || !password_verify($password, $user->getPassword())) {
            return $this->render('login', ['messages' => ['Nieprawidłowy adres e-mail lub hasło!']]);
        }

        // [B3] Regeneracja ID
        session_regenerate_id(true);

        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_role'] = $user->getRole();

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
        exit();
    }

    public function logout() {
        session_start();
        session_unset();
        session_destroy();

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        exit();
    }

    public function register() {
        if (!$this->isPost()) {
            return $this->render('register');
        }

        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirmedPassword = $_POST['confirmedPassword'];

        // [C1] Walidacja formatu email po stronie backendu
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('register', ['messages' => ['Nieprawidłowy format adresu e-mail.']]);
        }

        // [B4] Minimalna długość hasła
        if (strlen($password) < 8) {
            return $this->render('register', ['messages' => ['Hasło musi składać się z przynajmniej 8 znaków.']]);
        }

        if ($password !== $confirmedPassword) {
            return $this->render('register', ['messages' => ['Hasła nie są identyczne.']]);
        }

        $userRepository = new UserRepository();

        if ($userRepository->getUser($email)) {
            return $this->render('register', ['messages' => ['Konto z tym adresem e-mail już istnieje!']]);
        }

        // Twarde przypisanie roli 'user' dla każdego nowego konta
        $user = new User($email, password_hash($password, PASSWORD_BCRYPT), 'user');
        $userRepository->addUser($user);

        return $this->render('login', ['messages' => ['Konto utworzone poprawnie. Można się zalogować.']]);
    }
}