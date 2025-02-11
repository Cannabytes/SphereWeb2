// forum-moderation.js
// Модуль для модерации и управления темами форума

const ForumModeration = (function () {
    // Приватные переменные
    const config = window.forumConfig || {};

    // Инициализация
    function initialize() {
        if (config.userIsAdmin || config.userIsModerator) {
            initializeModerationHandlers();
        }
        initializeSubscriptionHandlers();
    }

    function initializeModerationHandlers() {
        // Обработчики модерации
        $('#confirmDelete').on('click', handleDeleteThread);
        $('#confirmMove').on('click', handleMoveThread);
        $('#confirmRename').on('click', handleRenameThread);

        if (config.isModerated && !config.isApproved) {
            $('#applyApprove').on('click', handleApproveThread);
        }
    }

    function initializeSubscriptionHandlers() {
        $('#toggleSubscription').on('click', handleToggleSubscription);
    }

    // Обработчики событий
    async function handleDeleteThread() {
        const deleteReason = $('#deleteReason').val();

        try {
            const response = await AjaxSend("/forum/topic/delete", "POST", {
                threadId: config.threadId,
                reason: deleteReason
            }, true);

            if (response.ok) {
                $('#deleteModal').modal('hide');
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    window.location.reload();
                }
            }
        } catch (error) {
            console.error('Ошибка при удалении темы:', error);
            noticeError('Произошла ошибка при удалении темы');
        }
    }

    async function handleMoveThread() {
        const newCategoryId = $('#moveThreadSelect').val();

        try {
            const response = await AjaxSend("/forum/thread/move", "POST", {
                threadId: config.threadId,
                categoryId: newCategoryId
            }, true);

            if (response.ok) {
                $('#moveModal').modal('hide');
                window.location.reload();
            }
        } catch (error) {
            console.error('Ошибка при перемещении темы:', error);
            noticeError('Произошла ошибка при перемещении темы');
        }
    }

    async function handleRenameThread() {
        const newTitle = $('#newThreadTitle').val().trim();

        if (!validateThreadTitle(newTitle)) {
            return;
        }
        const response = await AjaxSend("/forum/thread/rename", "POST", {
            threadId: config.threadId,
            title: newTitle
        }, false);
    }

    async function handleApproveThread() {
        try {
            const response = await AjaxSend("/forum/topic/approve", "POST", {
                threadId: config.threadId
            }, true);

            if (response.ok) {
                window.location.reload();
            }
        } catch (error) {
            console.error('Ошибка при подтверждении темы:', error);
            noticeError('Произошла ошибка при подтверждении темы');
        }
    }

    async function handleToggleSubscription() {
        const $btn = $(this);
        const isCurrentlySubscribed = $btn.find('.subscription-text')
            .text().includes('Отписаться');

        try {
            const response = await AjaxSend('/forum/thread/toggle-subscription', 'POST', {
                threadId: config.threadId,
                subscribed: !isCurrentlySubscribed
            }, true);
            console.log(response)
            if (response.ok) {
                updateSubscriptionButton($btn, isCurrentlySubscribed);
            }
        } catch (error) {
            console.error('Ошибка при изменении подписки:', error);
            noticeError('Произошла ошибка при изменении подписки');
        }
    }

    // Вспомогательные функции
    function validateThreadTitle(title) {
        if (title.length < 3) {
            noticeError('Название темы должно содержать минимум 3 символа');
            return false;
        }
        if (title.length > 60) {
            noticeError('Название темы не может быть длиннее 60 символов');
            return false;
        }
        return true;
    }

    function updateSubscriptionButton($btn, wasSubscribed) {
        const newText = wasSubscribed ?
            'Подписаться на уведомления' :
            'Отписаться от уведомлений';
        $btn.find('.subscription-text').text(newText);
    }

    // Публичное API
    return {
        initialize
    };
})();

// Инициализация при загрузке документа
document.addEventListener('DOMContentLoaded', () => {
    ForumModeration.initialize();
});