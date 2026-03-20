<?php

class AppController {

    // Sprawdza czy ktoś klika przycisk w formularzu
    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function render(string $template = null, array $variables = []) {
        $templatePath = 'public/views/'.$template.'.html';
        $output = 'File not found';

        if (file_exists($templatePath)) {
            // Wyciągamy zmienne (np. komunikaty o błędach), żeby widok miał do nich dostęp
            extract($variables);

            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        }

        // Plujemy gotowym HTML-em na ekran
        print $output;
    }
}