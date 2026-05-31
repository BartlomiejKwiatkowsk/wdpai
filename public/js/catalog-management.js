document.addEventListener('DOMContentLoaded', () => {
    const initModal = (modalId, closeBtnId, openBtnsSelector) => {
        const modal = document.getElementById(modalId);
        const closeBtn = document.getElementById(closeBtnId);
        const openBtns = document.querySelectorAll(openBtnsSelector);

        if(!modal) return;

        openBtns.forEach(btn => btn.addEventListener('click', () => modal.classList.add('active')));
        if(closeBtn) closeBtn.addEventListener('click', () => modal.classList.remove('active'));
        modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('active'); });
    };

    // Obsługa Add To Tank
    initModal('addSpeciesModal', 'closeAddSpeciesModal', '.open-add-modal');
    document.querySelectorAll('.open-add-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('hiddenSpeciesId').value = btn.getAttribute('data-id');
            document.getElementById('modalSpeciesName').textContent = btn.getAttribute('data-name');
        });
    });

    // Obsługa New Species
    initModal('newSpeciesModal', 'closeNewSpeciesModal', '#openNewSpeciesModal');

    // Obsługa Edit Species (Przenoszenie danych do formularza)
    initModal('editSpeciesModal', 'closeEditSpeciesModal', '.open-edit-modal');
    document.querySelectorAll('.open-edit-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_common').value = btn.getAttribute('data-common');
            document.getElementById('edit_scientific').value = btn.getAttribute('data-scientific');
            document.getElementById('edit_water').value = btn.getAttribute('data-water');
            document.getElementById('edit_phmin').value = btn.getAttribute('data-phmin');
            document.getElementById('edit_phmax').value = btn.getAttribute('data-phmax');
            document.getElementById('edit_tempmin').value = btn.getAttribute('data-tempmin');
            document.getElementById('edit_tempmax').value = btn.getAttribute('data-tempmax');
        });
    });
});