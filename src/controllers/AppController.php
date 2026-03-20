<?php

class AppController {
    // Metoda odpowiedzialna za renderowanie widoku.
    // Zapewnia enkapsulację procesu ładowania plików szablonów.
    protected function render(string $template = null) {
        $templatePath = 'public/views/'.$template.'.html'; // Z czasem zmienimy na .php pod sesje
        $output = 'File not found';

        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            echo $output;
        }
    }
}