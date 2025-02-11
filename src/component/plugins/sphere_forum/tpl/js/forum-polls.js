// forum-polls.js
const ForumPolls = (function() {
    // Приватные переменные
    const MAX_OPTIONS = 10;
    const MIN_OPTIONS = 2;
    let optionsWithVotes = new Set();

    // Инициализация опросов
    function initialize() {
        initializeExistingPolls();
        initializeEventListeners();
        setupDefaultExpiration();
    }

    function initializeExistingPolls() {
        $('.poll-option-input').each(function() {
            const votes = parseInt($(this).data('votes'));
            if (votes > 0) {
                optionsWithVotes.add($(this).val());
            }
        });
    }

    function initializeEventListeners() {
        $('#enablePoll').on('change', handlePollToggle);
        $('#pollVoteForm').on('submit', handlePollVote);
        $(document).on('click', '.remove-poll-option', handleRemoveOption);
        $(document).on('input', '.poll-option-input', handleOptionInput);
    }

    function setupDefaultExpiration() {
        const defaultDate = new Date();
        defaultDate.setDate(defaultDate.getDate() + 7);
        $('#pollExpiration').val(formatDateForInput(defaultDate));
    }

    // Обработчики событий
    function handlePollToggle() {
        const isEnabled = $(this).is(':checked');
        $('#pollSection').toggle(isEnabled);

        if (isEnabled && $('#pollOptionsContainer').children().length === 0) {
            addOption();
            addOption();
        }
    }

    async function handlePollVote(e) {
        e.preventDefault();

        const selectedOptions = $('input[name="pollOption"]:checked')
            .map(function() { return $(this).val(); })
            .get();

        if (selectedOptions.length === 0) {
            noticeError('Выберите вариант ответа');
            return;
        }

        try {
            const response = await AjaxSend('/forum/poll/vote', 'POST', {
                threadId: window.forumConfig.threadId,
                options: selectedOptions
            }, true);

            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Ошибка при голосовании:', error);
            noticeError('Произошла ошибка при голосовании');
        }
    }

    function handleRemoveOption() {
        const input = $(this).closest('.poll-option').find('.poll-option-input');
        const optionText = input.val();

        if (optionsWithVotes.has(optionText)) {
            noticeError('Нельзя удалить вариант, за который уже проголосовали');
            return;
        }

        $(this).closest('.poll-option').remove();
    }

    function handleOptionInput() {
        const oldValue = $(this).attr('data-original-value');
        if (optionsWithVotes.has(oldValue)) {
            noticeError('Нельзя изменить вариант, за который уже проголосовали');
            $(this).val(oldValue);
        }
    }

    // Публичные методы
    function addOption() {
        const container = $('#pollOptionsContainer');
        const optionCount = container.children('.poll-option').length;

        if (optionCount >= MAX_OPTIONS) {
            noticeError(`Максимум ${MAX_OPTIONS} вариантов ответа`);
            return;
        }

        const optionHtml = createOptionHtml(optionCount + 1);
        container.append(optionHtml);
    }

    function createOptionHtml(number) {
        return `
            <div class="poll-option input-group mb-2">
                <input type="text" class="form-control poll-option-input"
                       placeholder="Вариант ответа ${number}">
                <button class="btn btn-danger remove-poll-option" type="button">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        `;
    }

    // ВАЖНО: Делаем функцию collectPollData доступной через публичный API
    function collectPollData() {
        // Проверяем наличие секции с опросом и её видимость
        const pollSection = $('#pollSection');
        if (!pollSection.length || !pollSection.is(':visible')) {
            return null;
        }

        const question = $('#pollQuestion').val().trim();
        const isMultiple = $('#multipleChoice').is(':checked');
        const expiresAt = $('#pollExpiration').val()
            ? new Date($('#pollExpiration').val()).toISOString()
            : null;

        const options = $('.poll-option-input')
            .map(function() { return $(this).val().trim(); })
            .get()
            .filter(option => option !== '');

        if (!validatePollData(question, options)) {
            return false;
        }

        return {
            question,
            isMultiple,
            expiresAt,
            options
        };
    }

    function validatePollData(question, options) {
        if (!question) {
            noticeError('Введите вопрос опроса');
            return false;
        }

        if (options.length < MIN_OPTIONS) {
            noticeError(`Минимум ${MIN_OPTIONS} варианта ответа`);
            return false;
        }

        return true;
    }

    function formatDateForInput(date) {
        return date.toISOString().slice(0, 16);
    }

    // Публичное API
    return {
        initialize,
        addOption,
        collectPollData,
        MAX_OPTIONS,
        MIN_OPTIONS
    };
})();

// Инициализация при загрузке документа
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('pollSection')) {
        ForumPolls.initialize();
    }
});