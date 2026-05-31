<?php

require_once 'AppController.php';

class ErrorController extends AppController {

    public function error400($message = "Nieprawidłowe żądanie. Serwer nie mógł zrozumieć zapytania.") {
        http_response_code(400);
        $this->render('error', ['errorCode' => '400', 'errorMessage' => 'Bad Request', 'errorDescription' => $message]);
    }

    public function error403($message = "Odmowa dostępu. Brak odpowiednich uprawnień do przeglądania tego zasobu.") {
        http_response_code(403);
        $this->render('error', ['errorCode' => '403', 'errorMessage' => 'Forbidden', 'errorDescription' => $message]);
    }

    public function error404($message = "Strona, której szukasz, nie istnieje lub została przeniesiona.") {
        http_response_code(404);
        $this->render('error', ['errorCode' => '404', 'errorMessage' => 'Not Found', 'errorDescription' => $message]);
    }

    public function error500($message = "Wewnętrzny błąd serwera. Trwają prace naprawcze, spróbuj ponownie później.") {
        http_response_code(500);
        $this->render('error', ['errorCode' => '500', 'errorMessage' => 'Internal Server Error', 'errorDescription' => $message]);
    }
}