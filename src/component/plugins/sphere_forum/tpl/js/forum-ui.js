// forum-ui.js
// Модуль для работы с UI элементами форума

const ForumUI = (function() {
    // Приватные переменные
    let lightbox = null;

    // Инициализация
    function initialize() {
        initializeUI();
        initializeLightbox();
        initializeTooltips();
        initializeModals();
    }

    function initializeUI() {
        // Инициализация адаптивного меню
        $(window).on('resize', handleResponsiveUI);
        handleResponsiveUI();

        // Инициализация кнопок с подтверждением
        initializeConfirmButtons();

        // Инициализация анимаций
        initializeAnimations();
    }

    function initializeLightbox() {
        lightbox = GLightbox({
            touchNavigation: true,
            loop: true,
            autoplayVideos: true
        });
    }

    function initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                trigger: 'hover'
            });
        });
    }

    function initializeModals() {
        // Обработка модальных окон
        $('.modal').on('show.bs.modal', handleModalShow);
        $('.modal').on('hidden.bs.modal', handleModalHide);
    }

    // Обработчики событий
    function handleResponsiveUI() {
        const width = $(window).width();

        // Адаптация интерфейса для мобильных устройств
        if (width < 768) {
            $('.desktop-only').hide();
            $('.mobile-only').show();
        } else {
            $('.desktop-only').show();
            $('.mobile-only').hide();
        }

        // Адаптация таблиц
        makeTablesResponsive();
    }

    function handleModalShow(e) {
        const modal = $(this);
        const modalDialog = modal.find('.modal-dialog');

        // Анимация появления
        modalDialog.css({
            transform: 'scale(0.8)'
        });

        setTimeout(() => {
            modalDialog.css({
                transform: 'scale(1)',
                transition: 'transform 0.3s ease-out'
            });
        }, 20);
    }

    function handleModalHide(e) {
        const modal = $(this);
        modal.find('.modal-dialog').css({
            transform: '',
            transition: ''
        });
    }

    // Вспомогательные функции
    function initializeConfirmButtons() {
        $('[data-confirm]').on('click', function(e) {
            e.preventDefault();

            const message = $(this).data('confirm');
            if (!confirm(message)) return;

            const form = $(this).closest('form');
            if (form.length) {
                form.submit();
            } else {
                window.location.href = $(this).attr('href');
            }
        });
    }

    function initializeAnimations() {
        // Анимация появления элементов при скролле
        const animatedElements = document.querySelectorAll('.animate-on-scroll');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        });

        animatedElements.forEach(element => observer.observe(element));
    }

    function makeTablesResponsive() {
        $('.table').each(function() {
            const table = $(this);
            if (!table.parent().hasClass('table-responsive')) {
                table.wrap('<div class="table-responsive"></div>');
            }
        });
    }

    // Функции для работы с формами
    function setupFormValidation(formSelector, rules = {}) {
        const form = $(formSelector);

        form.on('submit', function(e) {
            e.preventDefault();

            const isValid = validateForm(form, rules);
            if (isValid) {
                form.off('submit').submit();
            }
        });

        // Живая валидация при вводе
        form.find('input, textarea, select').on('input change', function() {
            const field = $(this);
            const fieldRules = rules[field.attr('name')];

            if (fieldRules) {
                validateField(field, fieldRules);
            }
        });
    }

    function validateForm(form, rules) {
        let isValid = true;

        Object.entries(rules).forEach(([fieldName, fieldRules]) => {
            const field = form.find(`[name="${fieldName}"]`);
            if (!validateField(field, fieldRules)) {
                isValid = false;
            }
        });

        return isValid;
    }

    function validateField(field, rules) {
        const value = field.val();
        const error = ForumUtils.validateInput(value, rules);

        const formGroup = field.closest('.form-group');
        const feedback = formGroup.find('.invalid-feedback');

        if (error) {
            field.addClass('is-invalid').removeClass('is-valid');
            if (feedback.length) {
                feedback.text(error);
            } else {
                formGroup.append(`<div class="invalid-feedback">${error}</div>`);
            }
            return false;
        } else {
            field.removeClass('is-invalid').addClass('is-valid');
            feedback.remove();
            return true;
        }
    }

    // Публичное API
    return {
        initialize,
        setupFormValidation,
        refreshLightbox: initializeLightbox
    };
})();

// Инициализация при загрузке документа
document.addEventListener('DOMContentLoaded', () => {
    ForumUI.initialize();
});

// Добавляем стили для анимаций
document.head.appendChild(Object.assign(document.createElement('style'), {
    textContent: `
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }
        
        .animate-on-scroll.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        @media (prefers-reduced-motion: reduce) {
            .animate-on-scroll {
                transition: none;
                opacity: 1;
                transform: none;
            }
        }
    `
}));