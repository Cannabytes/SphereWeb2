/**
 * JavaScript для страницы оплаты FreeKassa
 */

// Инициализация (работает даже если скрипт подключён после загрузки DOM)
function initFreekassa() {
    // Инициализация слайдера суммы (если есть noUiSlider)
    initAmountSlider();

    // Обработка изменения суммы
    const amountInput = document.getElementById('amount');
    if (amountInput) {
        amountInput.addEventListener('input', function() {
            updateTotalCost();
        });
    }

    // Делегированные обработчики для radio-полей (payment_method)
    const container = document.getElementById('paymentForm') || document;
    container.addEventListener('change', function(e) {
        const target = e.target;
        if (!target) return;

        if (target.matches('input[name="payment_method"]')) {
            try { updateCurrencyLimits(target); } catch (err) { console.error(err); }
        }
    });

    // Обработка отправки по нажатию на кнопку оплаты
    const payButton = document.getElementById('payButton');
    if (payButton) {
        // защитимся от двойной привязки
        if (!payButton.dataset.freekassaBound) {
            payButton.addEventListener('click', function(e) {
                e.preventDefault();
                submitPayment();
            });
            payButton.dataset.freekassaBound = '1';
        }
    }

    // Инициализация стоимости
    updateTotalCost();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFreekassa);
} else {
    initFreekassa();
}

/**
 * Инициализация слайдера суммы
 */
function initAmountSlider() {
    const sliderElement = document.getElementById('amountSlider');
    const amountInput = document.getElementById('amount');
    
    if (!sliderElement || !amountInput || typeof noUiSlider === 'undefined') {
        return;
    }

    // Проверяем, не инициализирован ли уже слайдер
    if (sliderElement.noUiSlider) {
        console.log('Slider already initialized, skipping');
        return;
    }

    const minAmount = parseInt(amountInput.getAttribute('min')) || 1;
    const maxAmount = parseInt(amountInput.getAttribute('max')) || 10000;
    const defaultAmount = parseInt(amountInput.value) || 100;

    noUiSlider.create(sliderElement, {
        start: [defaultAmount],
        connect: [true, false],
        range: {
            'min': minAmount,
            'max': maxAmount
        },
        step: 1,
        tooltips: {
            to: function(value) {
                return Math.round(value) + ' Coins';
            }
        }
    });

    // Связываем слайдер с input
    sliderElement.noUiSlider.on('update', function(values, handle) {
        const value = Math.round(values[handle]);
        amountInput.value = value;
        updateTotalCost();
    });

    // Связываем input со слайдером
    amountInput.addEventListener('change', function() {
        let value = parseInt(this.value) || minAmount;
        
        if (value < minAmount) {
            value = minAmount;
        }
        if (value > maxAmount) {
            value = maxAmount;
        }
        
        this.value = value;
        sliderElement.noUiSlider.set(value);
    });
}

/**
 * Обновить общую стоимость
 */
function updateTotalCost() {
    const amountInput = document.getElementById('amount');
    const totalCostElement = document.getElementById('totalCost');
    const costCurrencyElement = document.getElementById('costCurrency');
    const coinRateElement = document.getElementById('coinRate');
    
    if (!amountInput || !totalCostElement) {
        return;
    }

    const amount = parseFloat(amountInput.value) || 0;
    const sphereCoinCost = parseFloat(coinRateElement?.textContent) || 1;
    
    // Получаем валюту выбранного магазина
    const selectedInstance = getSelectedInstance();
    let currency = 'RUB';
    
    if (selectedInstance) {
        currency = selectedInstance.currency || 'RUB';
    }

    // Вычисляем стоимость
    const totalCost = (amount * sphereCoinCost).toFixed(2);
    
    // Обновляем отображение. Если в шаблоне есть отдельный span для значения — обновляем только его,
    // чтобы не удалять span с валютой. Иначе используем старое поведение.
    const totalCostValueEl = document.getElementById('totalCostValue');
    if (totalCostValueEl) {
        totalCostValueEl.textContent = totalCost;
    } else {
        totalCostElement.textContent = totalCost + ' ';
    }

    if (costCurrencyElement) {
        costCurrencyElement.textContent = currency;
    }
}

/**
 * Получить выбранный магазин
 */
function getSelectedInstance() {
    const selectedRadio = document.querySelector('input[name="instance_id"]:checked');
    
    if (!selectedRadio) {
        return null;
    }

    const instanceId = selectedRadio.value;
    const label = document.querySelector(`label[for="instance_${instanceId}"]`);
    
    if (!label) {
        return null;
    }

    // Извлекаем валюту из текста
    const currencyMatch = label.textContent.match(/Валюта:\s*(\w+)/);
    const currency = currencyMatch ? currencyMatch[1] : 'RUB';

    return {
        id: instanceId,
        currency: currency
    };
}

/**
 * Обновить способы оплаты при смене магазина
 */
function updatePaymentMethods() {
    updateTotalCost();
    
    const selectedInstance = getSelectedInstance();
    if (!selectedInstance) return;

    const paymentMethodsContainer = document.getElementById('paymentMethods');
    const amountInput = document.getElementById('amount');
    
    if (!paymentMethodsContainer) return;

    // Показываем индикатор загрузки
    paymentMethodsContainer.innerHTML = `
        <div class="col-12 text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2 text-muted">Загрузка способов оплаты...</p>
        </div>
    `;

    AjaxSend('/plugin/freekassa/currencies', 'POST', {}, true, 10).then(data => {
        if (data.ok || data.status === 'success') {
            const currencies = data.currencies || [];
            
            if (currencies.length === 0) {
                paymentMethodsContainer.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">
                            Нет доступных способов оплаты для этого магазина
                        </div>
                    </div>
                `;
                return;
            }

            let html = '';
            currencies.forEach((currency, index) => {
                const min = currency.limits?.min || (amountInput ? amountInput.getAttribute('min') : 1);
                const max = currency.limits?.max || (amountInput ? amountInput.getAttribute('max') : 1000000);
                const fee = currency.fee?.user || 0;
                const code = currency.currency || '';
                
                html += `
                    <div class="col-xl-3 col-md-4 col-sm-6">
                        <input type="radio" class="btn-check" name="payment_method" 
                               id="pm_${currency.id}" value="${currency.id}" 
                               data-min="${min}" data-max="${max}" data-fee="${fee}" data-currency="${code}"
                               ${index === 0 ? 'checked' : ''}>
                        <label class="btn btn-outline-primary w-100 p-2 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="pm_${currency.id}">
                            <div class="mb-1">
                                <img src="https://static.freekassa.ru/images/currencies/${currency.id}.png" alt="${currency.name}" style="max-height: 24px;">
                            </div>
                            <small style="font-size: 10px; line-height: 1.2;">${currency.name}</small>
                            ${code ? ('<small class="text-muted mt-1" style="font-size:10px;line-height:1.1">' + code + '</small>') : ''}
                        </label>
                    </div>
                `;
            });
            paymentMethodsContainer.innerHTML = html;

            const firstRadio = paymentMethodsContainer.querySelector('input[type="radio"]');
            if (firstRadio) {
                updateCurrencyLimits(firstRadio);
            }
        } else {
            paymentMethodsContainer.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger mb-0">
                        Ошибка загрузки способов оплаты: ${data.message || 'Неизвестная ошибка'}
                    </div>
                </div>
            `;
        }
    }).catch(error => {
        console.error('Error fetching currencies:', error);
        paymentMethodsContainer.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger mb-0">
                    Ошибка сети при загрузке способов оплаты
                </div>
            </div>
        `;
    });
}

/**
 * Обновить лимиты выбранной валюты
 */
function updateCurrencyLimits(radio) {
    const min = radio.getAttribute('data-min');
    const max = radio.getAttribute('data-max');
    const fee = radio.getAttribute('data-fee');
    
    const minDisplay = document.getElementById('minAmountDisplay');
    const maxDisplay = document.getElementById('maxAmountDisplay');
    const feeDisplay = document.getElementById('feeDisplay');
    const feeValue = document.getElementById('feeValue');
    
    if (minDisplay) minDisplay.innerHTML = `<strong>${min}</strong>`;
    if (maxDisplay) maxDisplay.innerHTML = `<strong>${max}</strong>`;

    if (feeDisplay && feeValue) {
        if (fee && parseFloat(fee) > 0) {
            feeValue.textContent = fee;
            feeDisplay.style.display = 'inline';
        } else {
            feeDisplay.style.display = 'none';
        }
    }
}

/**
 * Отправить платеж
 */
function submitPayment() {
    const payButton = document.getElementById('payButton');

    // Защита от повторной отправки
    if (window.freekassaSending) {
        console.warn('submitPayment: already sending');
        return;
    }

    // Валидация: проверяем поля
    const amountInput = document.getElementById('amount');
    if (!amountInput) {
        console.error('submitPayment: amount input not found');
        noticeError('Поле суммы не найдено');
        return;
    }

    // Валидация суммы
    try {
        if (typeof amountInput.checkValidity === 'function' && !amountInput.checkValidity()) {
            if (typeof amountInput.reportValidity === 'function') amountInput.reportValidity();
            return;
        }
    } catch (err) {
        console.error('submitPayment validation error', err);
    }

    // Получаем элементы формы
    const paymentMethodElem = document.querySelector('input[name="payment_method"]:checked')
        || document.querySelector('input[name="payment_method"]');

    // Собираем данные
    const amount = parseFloat(amountInput.value);
    const paymentMethod = paymentMethodElem ? paymentMethodElem.value : null;

    // Проверяем обязательные поля
    if (!amount || amount <= 0) {
        noticeError('Пожалуйста, заполните все обязательные поля');
        return;
    }

    // Блокируем кнопку и показываем модально окно загрузки
    window.freekassaSending = true;
    if (payButton) {
        payButton.disabled = true;
        payButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Создание платежа...';
    }
    showLoadingModal();

    // Determine currency: prefer selected payment method, fallback to instance
    let currency = 'RUB';
    if (paymentMethodElem && paymentMethodElem.dataset && paymentMethodElem.dataset.currency) {
        currency = paymentMethodElem.dataset.currency || currency;
    } else {
        const instanceElem = document.querySelector('input[name="instance_id"]:checked') || document.querySelector('input[name="instance_id"]');
        if (instanceElem && instanceElem.dataset && instanceElem.dataset.currency) {
            currency = instanceElem.dataset.currency;
        } else {
            const si = getSelectedInstance();
            if (si && si.currency) currency = si.currency;
        }
    }

    // Отправляем запрос
    AjaxSend('/freekassa/payment/create', 'POST', {
        payment_method: paymentMethod,
        amount: amount,
        currency: currency
    }, true, 10).then(data => {
        hideLoadingModal();
        window.freekassaSending = false;

        const isSuccess = !!(data && (data.ok || data.success || data.type === 'success'));

        if (isSuccess) {
            const paymentUrl = data.url || data.location || data.paymentUrl || null;
            if (paymentUrl) {
                try { 
                    window.open(paymentUrl, '_blank'); 
                } catch (err) { 
                    window.location.href = paymentUrl; 
                }
                return;
            }
            noticeError('Не получен URL для оплаты');
        } else {
            noticeError(data?.message || 'Не удалось создать платеж');
        }
        resetPayButton();
    }).catch(error => {
        console.error('submitPayment error:', error);
        hideLoadingModal();
        window.freekassaSending = false;
        noticeError('Произошла ошибка при создании платежа');
        resetPayButton();
    });
}

/**
 * Сбросить кнопку оплаты
 */
function resetPayButton() {
    const payButton = document.getElementById('payButton');
    if (payButton) {
        payButton.disabled = false;
        payButton.innerHTML = '<i class="bi bi-arrow-right-circle me-2"></i>Перейти к оплате';
    }
}


/**
 * Показать модальное окно загрузки
 */
function showLoadingModal() {
    const modalElement = document.getElementById('loadingModal');
    if (modalElement) {
        const loadingModal = new bootstrap.Modal(modalElement);
        loadingModal.show();
    }
}

/**
 * Скрыть модальное окно загрузки
 */
function hideLoadingModal() {
    const modalElement = document.getElementById('loadingModal');
    if (modalElement) {
        const loadingModal = bootstrap.Modal.getInstance(modalElement);
        if (loadingModal) {
            loadingModal.hide();
        }
    }
}

/**
 * Показать уведомление
 */
function showNotification(title, message, type = 'info') {
    // Используем встроенную систему уведомлений если есть
    if (typeof showAlert === 'function') {
        showAlert(message, type);
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            text: message,
            icon: type === 'success' ? 'success' : type === 'error' ? 'error' : 'info',
            confirmButtonText: 'OK'
        });
    } else {
        alert(title + '\n' + message);
    }
}
