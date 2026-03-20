<?php

require_once 'AppController.php';
require_once __DIR__ .'/../models/User.php';
require_once __DIR__ .'/../repository/UserRepository.php';

class SecurityController extends AppController {

    public function login() {
        $userRepository = new UserRepository();

        // Jeśli to tylko odświeżenie strony (GET), pokazujemy formularz
        if (!$this->isPost()) {
            return $this->render('login');
        }

        // Zbieramy dane z formularza
        $email = $_POST['email'];
        $password = $_POST['password'];

        $user = $userRepository->getUser($email);

        // Zabezpieczenie: czy user istnieje?
        if (!$user) {
            return $this->render('login', ['messages' => ['Nie ma takiego konta w systemie!']]);
        }

        // Zabezpieczenie: czy hasło pasuje? (password_verify bo plain-text = ocena 2.0)
        if (!password_verify($password, $user->getPassword())) {
            return $this->render('login', ['messages' => ['Błędne hasło!']]);
        }

        // Logowanie udane - startujemy sesję (wymóg z regulaminu)
        session_start();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_role'] = $user->getRole();

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
    }
}