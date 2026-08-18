document.addEventListener("DOMContentLoaded", () => {
    const roleSelect = document.getElementById('reg-role');
    const bankGroup = document.getElementById('bank-number-group');
    const bankInput = document.getElementById('reg-bank-no');
    
    // New parameters selectors
    const customerParams = document.getElementById('customer-parameters');
    const incomeInput = document.getElementById('reg-income');
    const creditInput = document.getElementById('reg-credit');

    if (roleSelect) {
        roleSelect.addEventListener('change', () => {
            if (roleSelect.value === 'officer') {
                // Show security field, hide customer fields
                bankGroup.classList.add('visible');
                bankInput.setAttribute('required', 'required');
                
                customerParams.style.display = 'none';
                incomeInput.removeAttribute('required');
                creditInput.removeAttribute('required');
            } else {
                // Hide security field, show customer fields
                bankGroup.classList.remove('visible');
                bankInput.removeAttribute('required');
                bankInput.value = '';
                
                customerParams.style.display = 'block';
                incomeInput.setAttribute('required', 'required');
                creditInput.setAttribute('required', 'required');
            }
        });
    }
});