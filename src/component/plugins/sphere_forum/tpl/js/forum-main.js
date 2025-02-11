// forum-main.js
const ForumMain = (function () {
    // Приватные переменные
    let quill = null;
    let pond = null;
    let replyToId = null;
    let originalPosition = null;
    let currentReplyButton = null;

    async function handleSaveEditMessage() {
        try {
            const content = ForumEditor.getContent();
            const error = ForumEditor.validateContent(content);
            if (error) {
                noticeError(error);
                return;
            }

            // Получаем данные опроса через модуль ForumPolls
            const pollData = window.ForumPolls ? ForumPolls.collectPollData() : null;

            const response = await AjaxSend("/forum/post/edit", "POST", {
                postId: forumConfig.postId,
                content: content,
                attachments: ForumEditor.getUploadedAttachments(),
                poll: pollData,
                returnPage: forumConfig.returnPage
            });
        } catch (error) {
            console.error('Ошибка при сохранении:', error);
            noticeError('Произошла ошибка при сохранении сообщения');
        }
    }

    // Инициализация основных компонентов
    function initialize() {
        initializeDropdowns();
        initializeEventListeners();
        initializeEditor();
        initializeModals();
        initializePoll();
        initializeTooltips();
        initializeScrollHandling();
    }

    function initializeDropdowns() {
        $('.dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).dropdown('toggle');
        });
     }

    function initializeEditor() {
        if (document.querySelector('#message-post')) {
            quill = ForumEditor.initialize('#message-post');
        }
    }

    function initializeModals() {
        // Инициализация Bootstrap модалов
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => new bootstrap.Modal(modal));
    }

    function initializePoll() {
        $('#enablePoll')?.on('change', handlePollToggle);
        if ($('#pollSection').length) {
            setupDefaultPollExpiration();
        }
    }

    function initializeTooltips() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
    }

    function initializeScrollHandling() {
        if (window.location.hash) {
            const hash = window.location.hash;
            setTimeout(() => {
                smoothScrollToPost(hash);
            }, 100);
        }
    }

    // Обработчики событий
    function initializeEventListeners() {
        // Создание и редактирование тем
        $("#create_topic")?.on('click', handleCreateTopic);
        $("#saveEditMessage")?.on('click', handleSaveEditMessage);

        // Модерация
        $("#confirmDelete")?.on('click', handleDeleteTopic);
        $("#confirmMove")?.on('click', handleMoveTopic);

        // Ответы и лайки
        $(document).on('click', '.reply-button', handleReply);
        $('#closeReplyButton')?.on('click', handleCloseReply);

        // Горячие клавиши
        $(document).on('keydown', handleHotkeys);

        // Счетчик символов заголовка
        $('#topic_title')?.on('input', handleTitleCounter);
    }

    // Функции обработчики
    async function handleCreateTopic() {
        const title = $('#topic_title').val().trim();
        if (!validateTopicTitle(title)) return;

        const content = ForumEditor.getContent();
        const error = ForumEditor.validateContent(content);
        if (error) {
            noticeError(error);
            return;
        }

        // Получаем данные опроса с помощью функции из модуля ForumPolls
        const pollData = window.ForumPolls ? ForumPolls.collectPollData() : null;

        const response = await AjaxSend("/forum/topic/create", "POST", {
            title: title,
            content: content,
            categoryId: forumConfig.categoryId,
            attachments: ForumEditor.getUploadedAttachments(),
            poll: pollData
        });

        handleCreateTopicResponse(response);
    }

    async function handleAddMessage() {
        const $button = $('#addMessageTopic');

        // Если кнопка уже заблокирована, прерываем выполнение
        if ($button.prop('disabled')) {
            return;
        }

        const content = ForumEditor.getContent();
        const error = ForumEditor.validateContent(content);

        if (error) {
            noticeError(error);
            return;
        }

        try {
            // Блокируем кнопку и меняем текст
            $button.prop('disabled', true);
            const originalText = $button.html();
            $button.html('<i class="ri-loader-2-line ri-spin"></i> Отправка...');

            const response = await AjaxSend("/forum/topic/message/add", "POST", {
                topicId: window.forumConfig.threadId,
                message: content,
                replyToId: replyToId,
                attachments: ForumEditor.getUploadedAttachments()
            }, true);

            if (response.ok) {
                if (window.location.hash) {
                    history.replaceState(null, null, window.location.pathname + window.location.search);
                }
                location.reload();
            }
        } catch (error) {
            console.error('Ошибка при отправке сообщения:', error);
            noticeError("Произошла ошибка при отправке сообщения");

            // Возвращаем кнопку в исходное состояние
            $button.prop('disabled', false);
            $button.html(originalText);
        }
    }

    async function handleDeleteTopic() {
        const reason = $('#deleteReason').val();
            const response = await AjaxSend("/forum/topic/delete", "POST", {
                threadId: forumConfig.threadId,
                reason: reason
            }, true);

    }

    async function handleMoveTopic() {
        const newCategoryId = $('#moveThreadSelect').val();
            const response = await AjaxSend("/forum/thread/move", "POST", {
                threadId: forumConfig.threadId,
                categoryId: newCategoryId
            }, false);
    }


    async function handleToggleThreadStatus(e) {
        e.preventDefault();
        const threadId = $(this).data('thread-id');
        const currentStatus = $(this).data('current-status');
        const newStatus = currentStatus === 'closed' ? 'open' : 'close';

            const response = await AjaxSend("/forum/thread/toggle-status", "POST", {
                threadId: threadId,
                status: newStatus
            });
    }

    async function handleToggleThreadPin(e) {
        e.preventDefault();
        const threadId = $(this).data('thread-id');
        const currentStatus = $(this).data('current-status');
        const newStatus = currentStatus === 'pinned' ? 'unpin' : 'pin';

            const response = await AjaxSend("/forum/thread/toggle-pin", "POST", {
                threadId: threadId,
                status: newStatus
            });

    }

    function handleReply() {
        replyToId = $(this).data('post-id');
        const postId = $(this).data('post-id');
        const authorName = $(this).data('author-name');
        const postElement = $(`#post-buff-${postId}`).closest('.d-flex');
        const answerPanel = $('.answer-panel');

        if (currentReplyButton === this) {
            returnPanelToOriginalPosition(answerPanel);
            return;
        }

        setupReplyPanel(postElement, answerPanel, authorName);
    }

    function handleCloseReply() {
        replyToId = null;
        const answerPanel = $('.answer-panel');
        returnPanelToOriginalPosition(answerPanel);
    }

    function handlePollToggle() {
        const isEnabled = $(this).is(':checked');
        $('#pollSection').toggle(isEnabled);

        if (isEnabled && $('#pollOptionsContainer').children().length === 0) {
            addPollOption();
            addPollOption();
        }
    }

    function handleHotkeys(e) {
        // Ctrl + Enter для отправки формы
        if (e.ctrlKey && e.key === 'Enter') {
            const $createBtn = $("#create_topic");
            const $saveBtn = $("#saveEditMessage");
            const $addBtn = $("#addMessageTopic");

            if ($createBtn.length) $createBtn.click();
            else if ($saveBtn.length) $saveBtn.click();
            else if ($addBtn.length) $addBtn.click();
        }
    }

    function handleTitleCounter() {
        const currentLength = this.value.length;
        const counter = $('#title-counter');

        counter.text(`${currentLength}/60`);

        if (currentLength >= 50) {
            counter.removeClass('text-danger').addClass('text-warning');
        } else {
            counter.removeClass('text-warning text-danger');
        }

        if (currentLength >= 60) {
            counter.removeClass('text-warning').addClass('text-danger shake');
            setTimeout(() => counter.removeClass('shake'), 500);
        }
    }

    // Вспомогательные функции
    function validateTopicTitle(title) {
        if (title.length === 0) {
            noticeError("Введите название темы");
            $('#topic_title').focus();
            return false;
        }

        if (title.length > 60) {
            noticeError("Название темы не может быть длиннее 60 символов");
            $('#topic_title').focus();
            return false;
        }

        if (title.length < 3) {
            noticeError("Название темы должно содержать минимум 3 символа");
            $('#topic_title').focus();
            return false;
        }

        return true;
    }

    function setupReplyPanel(postElement, answerPanel, authorName) {
        if (!originalPosition) {
            originalPosition = {
                parent: answerPanel.parent(),
                nextSibling: answerPanel.next().length ? answerPanel.next() : null
            };
        }

        clearPreviousReplyState();
        postElement.addClass('post-highlight');

        const connection = createConnectionElement();
        answerPanel.insertAfter(postElement)
            .addClass('border-bottom')
            .before(connection);

        $('#closeReplyButton').show();
        currentReplyButton = this;

        scrollToReply(answerPanel);
        addReplyInfo(authorName);
    }

    function clearPreviousReplyState() {
        $('.post-highlight').removeClass('post-highlight');
        $('.reply-connection, .reply-info').remove();
    }

    function createConnectionElement() {
        const connection = $('<div class="reply-connection"><div class="reply-line"></div></div>');
        connection.css('opacity', '0').animate({opacity: 1}, 300);
        return connection;
    }

    function returnPanelToOriginalPosition(answerPanel) {
        if (!originalPosition) return;

        clearPreviousReplyState();

        if (originalPosition.nextSibling) {
            answerPanel.insertBefore(originalPosition.nextSibling);
        } else {
            originalPosition.parent.append(answerPanel);
        }

        answerPanel.removeClass('border-bottom');
        $('#closeReplyButton').hide();
        currentReplyButton = null;

        if (ForumEditor.quill) {
            ForumEditor.quill.setContents([]);
        }
    }

    function scrollToReply(element) {
        $('html, body').animate({
            scrollTop: element.offset().top - 100
        }, 500);
    }

    function addReplyInfo(authorName) {
        const replyInfo = $('<div class="reply-info">').css('opacity', '0').html(`
            <div class="d-flex align-items-center text-muted">
                <i class="ri-reply-line me-2"></i>
                <span>Ответ на сообщение от <strong>${authorName}</strong></span>
            </div>
        `);

        $('#message-post').before(replyInfo);
        replyInfo.animate({opacity: 1}, 300);
    }

    function updateLikesDisplay(postId, likes) {
        const container = $(`#post-buff-${postId}`);
        if (!container.hasClass('list-group-item')) {
            container.addClass('list-group-item list-group-item-light');
        }

        const likesHtml = likes.map(like => `
            <span class="avatar avatar-sm m-2 like-item like-item-new">
                <img src="${like.like_image}" alt="img" class="like-image">
            </span>
        `).join('');

        container.html(likesHtml);
    }

    function updateSubscriptionButton($btn, wasSubscribed) {
        const newText = wasSubscribed ?
            'Подписаться на уведомления' :
            'Отписаться от уведомлений';
        $btn.find('.subscription-text').text(newText);
    }

    function handleCreateTopicResponse(response) {
        if (response.ok) {
            if (response.redirect) {
                window.location.href = response.redirect;
            } else {
                noticeSuccess("Тема успешно создана");
                setTimeout(() => window.location.reload(), 1000);
            }
        } else {
            noticeError(response.message || "Произошла ошибка при создании темы");
        }
    }

    function setupDefaultPollExpiration() {
        const defaultDate = new Date();
        defaultDate.setDate(defaultDate.getDate() + 7);
        $('#pollExpiration').val(formatDateForInput(defaultDate));
    }

    function formatDateForInput(date) {
        return date.toISOString().slice(0, 16);
    }

    function smoothScrollToPost(hash) {
        const $post = $(hash);
        if ($post.length) {
            const headerHeight = $('.header').outerHeight() || 0;
            const windowHeight = window.innerHeight;
            const postHeight = $post.outerHeight();

            const scrollTop = $post.offset().top - (windowHeight - postHeight) / 2;

            $('html, body').animate({
                scrollTop: scrollTop
            }, {
                duration: 1000,
                easing: 'easeInOutQuad',
                complete: () => {
                    $post.addClass('post-highlight');
                    setTimeout(() => {
                        $post.removeClass('post-highlight');
                    }, 3000);
                }
            });
        }
    }

    // Публичное API
    return {
        initialize,
        handlePollToggle,
        handleAddMessage,
        handleReply,
        handleCloseReply,
        getReplyToId: () => replyToId
    };
})();

// Добавляем метод easing для анимации
jQuery.extend(jQuery.easing, {
    easeInOutQuad: function (x, t, b, c, d) {
        if ((t /= d / 2) < 1) return c / 2 * t * t + b;
        return -c / 2 * ((--t) * (t - 2) - 1) + b;
    }
});

// Инициализация при загрузке документа
document.addEventListener('DOMContentLoaded', () => {
    ForumMain.initialize();
});