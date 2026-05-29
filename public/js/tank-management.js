document.addEventListener('DOMContentLoaded', () => {

    // --- FETCH API (USUWANIE) ---
    const deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            const itemType = this.getAttribute('data-type');
            const rowElement = this.closest('.data-row');

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
                        rowElement.style.transition = 'opacity 0.3s ease';
                        rowElement.style.opacity = '0';
                        setTimeout(() => rowElement.remove(), 300);
                    });
            }
        });
    });

    // --- OBSŁUGA MODALA ---
    const logModal = document.getElementById('logModal');
    const openLogBtn = document.getElementById('openLogModal');
    const closeLogBtn = document.getElementById('closeLogModal');

    if (openLogBtn && logModal) {
        openLogBtn.addEventListener('click', () => {
            logModal.classList.add('active');
        });
    }

    if (closeLogBtn && logModal) {
        closeLogBtn.addEventListener('click', () => {
            logModal.classList.remove('active');
        });

        logModal.addEventListener('click', (e) => {
            if (e.target === logModal) {
                logModal.classList.remove('active');
            }
        });
    }
    // --- OBSŁUGA MODALA SPRZĘTU ---
    const eqModal = document.getElementById('eqModal');
    const openEqBtn = document.getElementById('openEqModal');
    const closeEqBtn = document.getElementById('closeEqModal');

    if (openEqBtn && eqModal) {
        openEqBtn.addEventListener('click', () => eqModal.classList.add('active'));
    }

    if (closeEqBtn && eqModal) {
        closeEqBtn.addEventListener('click', () => eqModal.classList.remove('active'));
        eqModal.addEventListener('click', (e) => {
            if (e.target === eqModal) eqModal.classList.remove('active');
        });
    }
    // --- OBSŁUGA MODALA OBSADY (LIVESTOCK) ---
    const liveModal = document.getElementById('liveModal');
    const openLiveBtn = document.getElementById('openLiveModal');
    const closeLiveBtn = document.getElementById('closeLiveModal');

    if (openLiveBtn && liveModal) {
        openLiveBtn.addEventListener('click', () => liveModal.classList.add('active'));
    }

    if (closeLiveBtn && liveModal) {
        closeLiveBtn.addEventListener('click', () => liveModal.classList.remove('active'));
        liveModal.addEventListener('click', (e) => {
            if (e.target === liveModal) liveModal.classList.remove('active');
        });
    }
});