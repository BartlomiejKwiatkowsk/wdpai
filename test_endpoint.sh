#!/bin/bash
# Test integracyjny sprawdzający stabilność endpointu logowania

URL="http://localhost:8080/login"
echo "Rozpoczynam test integracyjny dla: $URL"

# Wykonanie żądania curl z wyciągnięciem samego kodu HTTP (np. 200, 404, 500)
STATUS_CODE=$(curl -o /dev/null -s -w "%{http_code}\n" $URL)

if [ "$STATUS_CODE" -eq 200 ]; then
echo "[SUKCES] Aplikacja odpowiada prawidłowo. Endpoint /login zwrócił kod 200 OK."
exit 0
else
echo "[BŁĄD KRYTYCZNY] Endpoint /login zwrócił kod: $STATUS_CODE. Oczekiwano statusu 200."
exit 1
fi