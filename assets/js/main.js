// Basic client-side form validation for University Sports Equipment Booking System
document.addEventListener('DOMContentLoaded', function () {
    const requestForm = document.querySelector('form[data-validate="request"]');
    if (requestForm) {
        requestForm.addEventListener('submit', function (e) {
            const qtyInput = requestForm.querySelector('input[name="quantity_requested"]');
            const selectEq = requestForm.querySelector('select[name="equipment_id"]');
            if (qtyInput && selectEq) {
                const selectedOption = selectEq.options[selectEq.selectedIndex];
                const maxAvail = parseInt(selectedOption.getAttribute('data-available') || '0', 10);
                const qty = parseInt(qtyInput.value || '0', 10);
                if (qty <= 0 || qty > maxAvail) {
                    e.preventDefault();
                    alert('Requested quantity must be between 1 and the available stock (' + maxAvail + ').');
                }
            }
        });
    }
});
