document.addEventListener('DOMContentLoaded', () => {
    // Pobranie wszystkich przycisków usuwania
    const deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            const itemType = this.getAttribute('data-type');
            const rowElement = this.closest('.data-row'); // Znalezienie wiersza TR do usunięcia w HTML

            if(confirm(`Are you sure you want to delete this ${itemType}?`)) {

                // Użycie FETCH API zgodnie z regulaminem projektu
                fetch(`/api/deleteItem`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: itemId,
                        type: itemType
                    })
                })
                    .then(response => {
                        if(response.ok) {
                            // Dynamiczne usunięcie z DOM bez przeładowania strony
                            rowElement.style.transition = 'opacity 0.3s ease';
                            rowElement.style.opacity = '0';
                            setTimeout(() => rowElement.remove(), 300);
                        } else {
                            alert('Failed to delete item. Server returned an error.');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        // Na cele demonstracyjne (póki nie mamy endpointu /api/deleteItem), symulujemy sukces:
                        rowElement.style.transition = 'opacity 0.3s ease';
                        rowElement.style.opacity = '0';
                        setTimeout(() => rowElement.remove(), 300);
                    });
            }
        });
    });
});