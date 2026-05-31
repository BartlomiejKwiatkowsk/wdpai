<?php

require_once 'AppController.php';
require_once __DIR__ .'/../models/User.php';
require_once __DIR__ .'/../repository/UserRepository.php';

class SecurityController extends AppController {

    public function login() {
        $userRepository = new UserRepository();

        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        $user = $userRepository->getUser($email);

        if (!$user) {
            return $this->render('login', ['messages' => ['Nie ma takiego konta w systemie!']]);
        }

        if (!password_verify($password, $user->getPassword())) {
            return $this->render('login', ['messages' => ['Błędne hasło!']]);
        }

        session_start();
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

        if ($password !== $confirmedPassword) {
            return $this->render('register', ['messages' => ['Hasła nie są identyczne.']]);
        }

        $userRepository = new UserRepository();

        if ($userRepository->getUser($email)) {
            return $this->render('register', ['messages' => ['Konto z tym adresem e-mail już istnieje!']]);
        }

        // Twarde przypisanie roli 'user' dla każdego nowego konta publicznego
        $user = new User($email, password_hash($password, PASSWORD_BCRYPT), 'user');
        $userRepository->addUser($user);

        return $this->render('login', ['messages' => ['Konto utworzone poprawnie. Można się zalogować.']]);
    }
}