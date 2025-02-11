// forum-posts.js
// Модуль для управления постами на форуме

const ForumPosts = (function () {
    // Приватные переменные
    let replyToId = null;
    let originalPosition = null;
    let currentReplyButton = null;

    // Инициализация
    function initialize() {
        initializeEventListeners();
        initializeLightbox();
        processPostContent();
    }

    // Обработчик добавления сообщения
    async function handleAddMessage() {
        const content = ForumEditor.getContent();
        const error = ForumEditor.validateContent(content);

        if (error) {
            noticeError(error);
            return;
        }

        try {
            const response = await AjaxSend("/forum/topic/message/add", "POST", {
                topicId: window.forumConfig.threadId,
                message: content,
                replyToId: replyToId,
                attachments: ForumEditor.getUploadedAttachments()
            }, true);
            responseAnalysis(response);
            if (response.ok) {
                if (window.location.hash) {
                    history.replaceState(null, null, window.location.pathname + window.location.search);
                }
                location.reload();
            }
        } catch (error) {
            console.error('Ошибка при отправке сообщения:', error);
            noticeError("Произошла ошибка при отправке сообщения");
        }
    }

    function initializeEventListeners() {
        // Обработчики для постов
        $(document).on('click', '.reply-button', handleReply);
        $('#closeReplyButton').on('click', handleCloseReply);
        $('.deletePost').on('click', handleDeletePost);

        // Обработчики для лайков
        $(document).on('click', '.like-item-select', handleLike);

        // Модерация
        $(document).on('click', '.toggle-thread-status', handleToggleThreadStatus);
        $(document).on('click', '.pin-thread', handleToggleThreadPin);

        // Отправка сообщения
        $('#addMessageTopic').on('click', handleAddMessage);
    }

    function initializeLightbox() {
        if (typeof GLightbox !== 'undefined') {
            GLightbox({
                selector: '.glightbox',
                touchNavigation: true,
                loop: true
            });
        }
    }

    // Обработчики событий
    async function handleReply(event) {
        event.preventDefault();
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

    async function handleDeletePost() {
        const messageId = $(this).data('post-id');

        try {
            const response = await AjaxSend("/forum/topic/message/delete", "POST", {
                messageId: messageId
            }, true);
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Ошибка при удалении сообщения:', error);
            noticeError('Произошла ошибка при удалении сообщения');
        }
    }

    $('#likeModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const postId = button.data('post-id');
        const modal = $(this);
        // Сохраняем ID поста в модальном окне
        modal.data('post-id', postId);
    });

    async function handleLike() {
        const modal = $('#likeModal');
        const postId = modal.data('post-id'); // Получаем сохраненный ID поста
        const likesContainer = $(`#post-buff-${postId}`);
        const likeImage = $(this).data('image');

        const response = await AjaxSend("/forum/post/like", "POST", {
            postId: postId,
            likeImage: likeImage
        }, false);

        updateLikesDisplay(likesContainer, response.likes);

        modal.find('[data-bs-dismiss="modal"]').click();
    }

    async function handleToggleThreadStatus(e) {
        e.preventDefault();
        const threadId = $(this).data('thread-id');
        const currentStatus = $(this).data('current-status');
        const newStatus = currentStatus === 'closed' ? 'open' : 'close';

        try {
            const response = await AjaxSend("/forum/thread/toggle-status", "POST", {
                threadId: threadId,
                status: newStatus
            }, true);

            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Ошибка при изменении статуса темы:', error);
            noticeError('Произошла ошибка при изменении статуса темы');
        }
    }

    async function handleToggleThreadPin(e) {
        e.preventDefault();
        const threadId = $(this).data('thread-id');
        const currentStatus = $(this).data('current-status');
        const newStatus = currentStatus === 'pinned' ? 'unpin' : 'pin';

        try {
            const response = await AjaxSend("/forum/thread/toggle-pin", "POST", {
                threadId: threadId,
                status: newStatus
            }, true);

            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Ошибка при закреплении/откреплении темы:', error);
            noticeError('Произошла ошибка при изменении закрепления темы');
        }
    }


    // Вспомогательные функции
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

        if (window.ForumEditor?.quill) {
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

    function updateLikesDisplay(container, likes) {
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

    function processPostContent() {
        if (typeof ForumUtils !== 'undefined' && ForumUtils.processPostContent) {
            ForumUtils.processPostContent();
        }
    }

    // Публичное API
    return {
        initialize,
        handleAddMessage,      // Делаем функцию публичной
        getReplyToId: () => replyToId,
        setReplyToId: (id) => {
            replyToId = id;
        },
        clearReply: () => {
            replyToId = null;
            originalPosition = null;
            currentReplyButton = null;
        }
    };

})();

// Инициализация при загрузке документа
document.addEventListener('DOMContentLoaded', () => {
    ForumPosts.initialize();
});