<?php

require_once 'AppController.php';

class DefaultController extends AppController {


    public function login() {


        $this->render('login');
    }

    public function dashboard() {
        session_start();


        if (!isset($_SESSION['user_email'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        // Przekazujemy dane do widoku, żeby potem na ich podstawie chować guziki przed zwykłym userem
        $this->render('dashboard', [
            'userEmail' => $_SESSION['user_email'],
            'userRole' => $_SESSION['user_role']
        ]);
    }
}