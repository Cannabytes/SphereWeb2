<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\plugins\sphere_forum;
use Ofey\Logan22\component\plugins\sphere_forum\ForumClans;
use Ofey\Logan22\component\plugins\sphere_forum\ForumTracker;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/forum",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->show();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/forum/([\w.-]+)\.(\d+)",
        "file" => "forum.php",
        "call" => function ($sectionName, $categoryId) {
            (new sphere_forum\forum())->getTopics($sectionName, $categoryId);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/forum/([\w.-]+)\.(\d+)/create",
        "file" => "forum.php",
        "call" => function ($sectionName, $categoryId) {
            (new sphere_forum\forum())->createTopic($sectionName, $categoryId);
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/topic/create",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->createTopicSave();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/forum/topic/([\w.-]+)\.(\d+)",
        "file" => "forum.php",
        "call" => function ($sectionName, $categoryId) {
            (new sphere_forum\forum())->getTopicRead($categoryId);
        },
    ],


    //Новое сообщение
    [
        "method" => "POST",
        "pattern" => "/forum/topic/message/add",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->addTopicMessage();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/category/create",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->createCategory();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/section/create",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->createSection();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/topic/message/delete",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->deleteMessage();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/forum/post/edit/(\d+)(?:/page/(\d+))?",
        "file" => "forum.php",
        "call" => function ($postId, $page = 1) {
            (new sphere_forum\forum())->postEdit($postId, $page);
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/post/edit",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->postEditSave();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/category/rename",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->renameCategory();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/category/move",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->moveCategory();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/category/delete",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->deleteCategory();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/topic/delete",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->deleteThread();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/thread/move",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->moveThread();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/thread/rename",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->renameThread();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/post/like",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->addLike();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/upload/image",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->uploadImage();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/topic/approve",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->applyApprove();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/section/update",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->updateSection();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/category/move-order",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->moveOrder();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/forum/moderators",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->showModeratorPanel();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/moderator/add",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->addModerator();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/moderator/delete",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->deleteModerator();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/moderator/edit",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->editModerator();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/thread/toggle-status",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->toggleThreadStatus();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/category/update-permissions",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->updateCategoryPermissions();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/admin/sphere/forum",
        "file" => "sphere_forum.php",
        "call" => function () {
            (new sphere_forum\sphere_forum())->index();
        }
    ],

    [
        "method" => "GET",
        "pattern" => "/admin/sphere/forum/settings",
        "file" => "sphere_forum.php",
        "call" => function () {
            (new sphere_forum\sphere_forum())->settings();
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/admin/sphere/forum/settings/save",
        "file" => "sphere_forum.php",
        "call" => function () {
            (new sphere_forum\sphere_forum())->saveSettings();
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/thread/toggle-pin",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->toggleThreadPin();
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/user/settings/update",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->updateUserSettings();
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/thread/toggle-subscription",
        "file" => "forum.php",
        "call" => function () {
            $subscribed = filter_var($_POST['subscribed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            ForumTracker::toggleThreadSubscription($_POST['threadId'], $subscribed);
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/notifications/mark-all-read",
        "file" => "forum.php",
        "call" => function () {
            if (user::self()->isAuth()) {
                $notifications = sql::getRows(
                    "SELECT id FROM forum_notifications 
                WHERE user_id = ? AND is_read = 0",
                    [user::self()->getId()]
                );

                if (!empty($notifications)) {
                    $notificationIds = array_column($notifications, 'id');
                    ForumTracker::markNotificationsAsRead($notificationIds);
                }

                board::success("Все уведомления отмечены как прочитанные");
            }
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/poll/vote",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->votePoll();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/forum/clan/create",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\ForumClans())->createClanIndex();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/create",
        "file" => "forum.php",
        "call" => function () {
            ((new sphere_forum\ForumClans())->create());
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/forum/clan/edit/{clanName}",
        "file" => "forum.php",
        "call" => function ($clanName) {
            ((new sphere_forum\ForumClans())->editClan($clanName));
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/clan/edit/{clanName}",
        "file" => "forum.php",
        "call" => function () {
            ((new sphere_forum\ForumClans())->updateClan());
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/clan/join",
        "file" => "forum.php",
        "call" => function () {
            ((new sphere_forum\ForumClans())->joinClan($_POST['clan_id']));
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/request/cancel",
        "file" => "forum.php",
        "call" => function () {
            if (!user::self()->isAuth()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            $clanId = $_POST['clan_id'] ?? null;
            if (!$clanId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing clan_id']);
                return;
            }

            $result = (new sphere_forum\ForumClans())->cancelJoinRequest($clanId);
            echo json_encode(['success' => $result]);
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/chat/send",
        "file" => "forum.php",
        "call" => function () {
            if (!user::self()->isAuth()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            $clanId = $_POST['clan_id'] ?? null;
            $message = $_POST['message'] ?? '';
            
            // XSS защита: очищаем сообщение
            $message = \Ofey\Logan22\component\request\XssSecurity::clean($message);

            if (!$clanId || !$message) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }

            try {
                $clan = (new sphere_forum\ForumClans())->getClanInfoById((int)$clanId);
                if (!$clan) {
                    throw new \Exception('Клан не найден');
                }

                $newMessage = $clan->sendMessage(user::self()->getId(), $message);
                echo json_encode($newMessage);
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/request/handle",
        "file" => "forum.php",
        "call" => function () {
            if (!user::self()->isAuth()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            $requestId = $_POST['request_id'] ?? null;
            $accept = isset($_POST['accept']) ? filter_var($_POST['accept'], FILTER_VALIDATE_BOOLEAN) : null;

            if (!$requestId || $accept === null) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                return;
            }

            try {
                $result = (new sphere_forum\ForumClans())->handleRequest($requestId, $accept);
                echo json_encode(['success' => $result]);
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/chat/messages",
        "file" => "forum.php",
        "call" => function () {
            $clanId = $_POST['clan_id'] ?? null;
            $lastMessageId = $_POST['last_message_id'] ?? 0;

            if($clanId === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing clan_id']);
                return;
            }

            try {
                $clan = (new sphere_forum\ForumClans())->getClanInfoById((int)$clanId);
                if (!$clan) {
                    throw new \Exception('Клан не найден');
                }

                $messages = $clan->getMessages($lastMessageId);
                echo json_encode($messages);
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/leave",
        "file" => "forum.php",
        "call" => function () {
            $clanId = $_POST['clan_id'];
            ((new sphere_forum\ForumClans())->leaveClan($clanId));
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/kick-member",
        "file" => "forum.php",
        "call" => function () {
            if (!user::self()->isAuth()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            $clanId = $_POST['clan_id'] ?? null;
            $memberId = $_POST['member_id'] ?? null;

            if (!$clanId || !$memberId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                return;
            }

            try {
                $result = (new sphere_forum\ForumClans())->kickMember($clanId, $memberId);
                echo json_encode(['success' => $result]);
            } catch (\Exception $e) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/posts",
        "file" => "forum.php",
        "call" => function () {
            header('Content-Type: application/json');

            try {
                $clanId = $_POST['clan_id'] ?? null;
                if (!$clanId) {
                    throw new \Exception('Missing clan_id');
                }

                $clan = (new sphere_forum\ForumClans())->getClanInfoById((int)$clanId);
                if (!$clan) {
                    throw new \Exception('Клан не найден');
                }
                $posts = $clan->getClanPosts();
                $posts = array_reverse($posts);
                echo json_encode([
                    'success' => true,
                    'posts' => array_values($posts) // Убедимся, что отдаем массив
                ]);

            } catch (\Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/post/create",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\ForumClans())->createClanPost();
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/post/update",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\ForumClans())->updateClanPost();
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/post/delete",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\ForumClans())->deleteClanPost();
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/update-description",
        "file" => "forum.php",
        "call" => function () {
            try {
                $clanId = $_POST['clan_id'] ?? null;
                $description = $_POST['description'] ?? '';
                
                // XSS защита: очищаем описание
                $description = \Ofey\Logan22\component\request\XssSecurity::clean($description);

                if (!$clanId || !$description) {
                    throw new \Exception('Отсутствуют необходимые параметры');
                }

                $clans = new ForumClans();
                $clan = $clans->getClanInfoById((int)$clanId);

                if (!$clan) {
                    throw new \Exception('Клан не найден');
                }

                if ($clan->updateDescription($description)) {
                    board::reload();
                    board::success("Описание обновлено");
                } else {
                    throw new \Exception('Ошибка при обновлении описания');
                }
            } catch (\Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/update-game-name",
        "file" => "forum.php",
        "call" => function () {
            try {
                $clanId = $_POST['clan_id'] ?? null;
                $clanNameGame = $_POST['clan_name_game'] ?? null;
                
                // XSS защита: очищаем название клана
                if ($clanNameGame !== null) {
                    $clanNameGame = \Ofey\Logan22\component\request\XssSecurity::cleanText($clanNameGame);
                }

                if (!$clanId) {
                    throw new Exception('Отсутствует ID клана');
                }

                $clans = new ForumClans();
                $clan = $clans->getClanInfoById((int)$clanId);

                if (!$clan) {
                    throw new Exception('Клан не найден');
                }

                // Проверяем, является ли текущий пользователь владельцем клана
                if ($clan->getOwnerId() !== user::self()->getId()) {
                    throw new Exception('Недостаточно прав для изменения названия клана');
                }

                if($clanNameGame != ""){
                    $clans = [];
                    foreach(user::self()->getAccounts() as $account) {
                        foreach($account->getCharacters() as $character) {
                            $clans[] = $character->getClanName();
                        }
                    }
                    $clansExists = in_array($clanNameGame, $clans);
                    if(!$clansExists) {
                        board::error("Клан не найден");
                    }
                }


                sql::run(
                    "UPDATE forum_clans SET clan_name_game = ? WHERE id = ?",
                    [$clanNameGame, $clanId]
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Название клана в игре обновлено'
                ]);

            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        }
    ],

    [
        "method" => "POST",
        "pattern" => "/forum/clan/delete",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\ForumClans())->deleteClan();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/forum/clans",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\ForumClans())->showAllClans();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/forum/clans",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\ForumClans())->adminClansList();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/forum/clan/edit/{clanId}",
        "file" => "forum.php",
        "call" => function ($clanId) {
            (new sphere_forum\ForumClans())->adminEditClan($clanId);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/forum/clan/{clanName}",
        "file" => "forum.php",
        "call" => function ($clanName) {
            ((new sphere_forum\ForumClans())->view($clanName));
        }
    ],

    // Управление банами (используем user-block вместо ban для обхода AdBlocker)
    [
        "method" => "GET",
        "pattern" => "/forum/user-blocks",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->showBansPanel();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/user-block/create",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->createBan();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/user-block/update",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->updateBan();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/user-block/remove",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->removeBan();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/forum/user-block/history/{userId}",
        "file" => "forum.php",
        "call" => function ($userId) {
            (new sphere_forum\forum())->getBanHistory((int)$userId);
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/forum/user/search",
        "file" => "forum.php",
        "call" => function () {
            (new sphere_forum\forum())->searchUsers();
        },
    ],

];
