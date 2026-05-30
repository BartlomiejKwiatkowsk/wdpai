document.addEventListener('DOMContentLoaded', () => {

    // --- 1. GENERYCZNA OBSŁUGA MODALI ---
    const initModal = (openBtnId, closeBtnId, modalId) => {
        const openBtn = document.getElementById(openBtnId);
        const closeBtn = document.getElementById(closeBtnId);
        const modal = document.getElementById(modalId);

        if (!openBtn || !closeBtn || !modal) return;

        openBtn.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.add('active');
        });

        closeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.remove('active');
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.remove('active');
        });
    };

    // Inicjalizacja wszystkich okien
    initModal('openLogModal', 'closeLogModal', 'logModal');
    initModal('openEqModal', 'closeEqModal', 'eqModal');
    initModal('openLiveModal', 'closeLiveModal', 'liveModal');

    // --- 2. FETCH API (USUWANIE) ---
    const deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const itemId = this.getAttribute('data-id');
            const itemType = this.getAttribute('data-type');
            const rowElement = this.closest('.data-row'); // Dopasowane do nowej klasy w HTML

            if(confirm(`Are you sure you want to delete this ${itemType}?`)) {

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
                            rowElement.style.transition = 'opacity 0.3s ease';
                            rowElement.style.opacity = '0';
                            setTimeout(() => rowElement.remove(), 300);
                        } else {
                            alert('Failed to delete item. Server returned an error.');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert('Critical Error: Failed to connect to the server.');
                    });
            }
        });
    });

});