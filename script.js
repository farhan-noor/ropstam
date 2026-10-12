
document.addEventListener('change', function (event) {
    if (!event.target.matches('.service-addon-toggle')) {
        return;
    }

    const calculator = event.target.closest('.service-calculator');

    if (!calculator) {
        return;
    }

    const basePrice = Number(calculator.dataset.basePrice) || 0;
    let total = basePrice;

    calculator.querySelectorAll('.service-addon-toggle:checked')
        .forEach(function (checkbox) {
            total += Number(checkbox.dataset.addonPrice) || 0;
        });

    const totalElement = calculator.querySelector(
        '.service-estimated-price'
    );

    if (totalElement) {
        totalElement.textContent = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(total);
    }
});
