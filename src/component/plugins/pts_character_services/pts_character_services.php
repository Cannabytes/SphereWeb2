<?php

namespace Ofey\Logan22\component\plugins\pts_character_services;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server as api;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class pts_character_services
{

    private ?array $setting = [];

    public function __construct()
    {
        if (server::get_count_servers() == 0) {
            redirect::location("/main");
        }
        $this->setting = server::getServer(user::self()->getServerId())->getPluginSetting("pts_character_services") ?? [];
        tpl::addVar('setting', plugin::getSetting("pts_character_services"));
        tpl::addVar("pluginName", "pts_character_services");
        tpl::addVar("pluginActive", (bool) plugin::getPluginActive("pts_character_services") ?? false);
    }

    public function setting()
    {
        \Ofey\Logan22\model\admin\validation::user_protection("admin");
        if (!isset($this->setting['available_colors'])) {
            $this->setting['available_colors'] = [];
        }
        $availableColors = $this->setting['available_colors'];
        tpl::addVar("available_colors", json_encode($availableColors));
        tpl::displayPlugin("/pts_character_services/tpl/setting.html");
    }

    public function save(): void
    {
        $server = server::getServer(user::self()->getServerId());

        if ($server->getPlatform() != "pts") {
            board::error(lang::get_phrase("error_save_platform"));
            return;
        }

        $services = $_POST['services'] ?? [];
        $costs = $_POST['costs'] ?? [];

        // Получаем массив доступных цветов
        $availableColors = [];
        if (isset($_POST['available_colors']) && !empty($_POST['available_colors'])) {
            $colorsJson = $_POST['available_colors'];
            $availableColors = json_decode($colorsJson, true);

            // Проверяем, что это действительно массив
            if (!is_array($availableColors)) {
                $availableColors = [];
            }
        }

        $server->setPluginSetting("pts_character_services", [
            'services' => $services,
            'costs' => $costs,
            'available_colors' => $availableColors,
        ]);

        board::success(lang::get_phrase("success_settings_saved"));

    }
    public function show()
    {
        if (!plugin::getPluginActive("pts_character_services")) {
            redirect::location("/main");
        }

        $availableColors = server::getServer(user::self()->getServerId())->getPluginSetting("pts_character_services");
        if (isset($availableColors['available_colors'])) {
            $availableColors = $availableColors['available_colors'];
        } else {
            $availableColors = [];
        }

        tpl::addVar("available_colors", json_encode($availableColors));
        tpl::displayPlugin("/pts_character_services/tpl/services.html");
    }

    public function changeName()
    {
        $character = $_POST['character'] ?? null;
        $new_name = $_POST['new_name'] ?? null;
        if (!$character || !$new_name) {
            echo json_encode(['success' => false, 'message' => lang::get_phrase("error_not_all_parameters")]);
            return;
        }

        $cost = $this->setting['costs']['change_name'] ?? null;
        if (!$cost) {
            board::error(lang::get_phrase("error_no_cost"));
            return;
        }
        $canAffordPurchase = user::self()->canAffordPurchase($cost);
        if (!$canAffordPurchase) {
            board::error(lang::get_phrase("You dont have enough to purchase", $cost - user::self()->getDonate()));
        }

        // Проверяем, есть ли такой персонаж у пользователя
        $user = user::self();
        if (!$user->isPlayer($character)) {
            board::error(lang::get_phrase("error_character_not_found"));
        }

        $response = api::sendCustom(
            "/pts/character/services/change/name",
            [
                'character' => $character,
                'new_name' => $new_name,
            ]
        )->show()->getResponse();
        if ($response['success']) {
            user::self()->donateDeduct($cost);
            board::reload();
            board::success(lang::get_phrase("success_name_changed"));
        } else {
            board::error($response['message']);
        }
    }


    public function moveCharacter()
    {
        $character = $_POST['character'] ?? null;
        $target_account = $_POST['target_account'] ?? null;
        header('Content-Type: application/json');
        if (!$character || !$target_account) {
            board::error(lang::get_phrase("error_not_all_parameters"));
            return;
        }

        $cost = $this->setting['costs']['move_account'] ?? null;
        if (!$cost) {
            board::error(lang::get_phrase("error_no_cost"));
            return;
        }
        $canAffordPurchase = user::self()->canAffordPurchase($cost);
        if (!$canAffordPurchase) {
            board::error(lang::get_phrase("You dont have enough to purchase", $cost - user::self()->getDonate()));
        }

        $user = user::self();
        if (!$user->isPlayer($character)) {
            board::error(lang::get_phrase("error_character_not_found"));
        }
        //Проверяем существование аккаунта переноса
        if (!$user->getAccounts($target_account)) {
            board::error(lang::get_phrase("error_target_account_not_found"));
        }

        $response = api::sendCustom(
            "/pts/character/services/move/player",
            [
                'character' => $character,
                'target_account' => $target_account,
            ]
        )->show()->getResponse();
        if ($response['success']) {
            user::self()->donateDeduct($cost);
            board::reload();
            board::success(lang::get_phrase("success_character_moved"));
        } else {
            board::error($response['message']);
        }
        board::success(lang::get_phrase("success_character_moved"));
    }

    public function deleteCharacter()
    {
        $character = $_POST['character'] ?? null;
        header('Content-Type: application/json');
        if (!$character) {
            echo json_encode(['success' => false, 'message' => lang::get_phrase("error_character_not_selected")]);
            return;
        }

        $cost = $this->setting['costs']['delete_character'] ?? null;
        if (!$cost) {
            board::error(lang::get_phrase("error_no_cost"));
            return;
        }
        $canAffordPurchase = user::self()->canAffordPurchase($cost);
        if (!$canAffordPurchase) {
            board::error(lang::get_phrase("You dont have enough to purchase", $cost - user::self()->getDonate()));
        }

        $user = user::self();
        if (!$user->isPlayer($character)) {
            board::error(lang::get_phrase("error_character_not_found"));
        }

        $response = api::sendCustom("/pts/character/services/remove/player", [
            'character' => $character,
        ])->show()->getResponse();
        if ($response['success']) {
            user::self()->donateDeduct($cost);
            board::reload();
            board::success(lang::get_phrase("success_character_deleted"));
        } else {
            board::error($response['message']);
        }
        echo json_encode(['success' => true, 'message' => lang::get_phrase("success_character_deleted")]);
    }

    private function hexToBGR($hexColor): int
    {
        // Убираем символ # если есть
        $hexColor = ltrim($hexColor, '#');

        // Проверяем, что цвет в правильном формате
        if (strlen($hexColor) !== 6) {
            throw new \InvalidArgumentException("Неверный формат цвета. Ожидается 6-значный HEX код.");
        }

        // Разбираем HEX на RGB компоненты
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        // Конвертируем в BGR формат (Blue << 16 | Green << 8 | Red)
        $bgr = ($b << 16) | ($g << 8) | $r;

        return $bgr;
    }

    public function changeNameColor()
    {
        $character = $_POST['character'] ?? null;
        $color = $_POST['color'] ?? null;
        header('Content-Type: application/json');
        if (!$character || !$color) {
            echo json_encode(['success' => false, 'message' => lang::get_phrase("error_not_all_parameters")]);
            return;
        }

        $cost = $this->setting['costs']['change_name_color'] ?? null;
        if (!$cost) {
            board::error(lang::get_phrase("error_no_cost"));
            return;
        }
        $canAffordPurchase = user::self()->canAffordPurchase($cost);
        if (!$canAffordPurchase) {
            board::error(lang::get_phrase("You dont have enough to purchase", $cost - user::self()->getDonate()));
        }

        $availableColors = server::getServer(user::self()->getServerId())->getPluginSetting("pts_character_services");
        if (!isset($availableColors['available_colors'])) {
            board::error(lang::get_phrase("error_no_colors"));
            return;
        }
        $availableColors = $availableColors['available_colors'];
        if (!in_array($color, $availableColors)) {
            board::error(lang::get_phrase("error_invalid_color"));
            return;
        }

        $user = user::self();
        if (!$user->isPlayer($character)) {
            board::error(lang::get_phrase("error_character_not_found"));
        }

        try {
            // Конвертируем цвет в BGR формат
            $bgrColor = $this->hexToBGR($color);

            // Отправляем запрос к API с BGR цветом
            $response = api::sendCustom("/pts/character/services/change/name/color", [
                'character' => $character,
                'color' => $bgrColor
            ])->show()->getResponse();

            if ($response['success']) {
                user::self()->donateDeduct($cost);
                board::reload();
                board::success(lang::get_phrase("success_color_changed"));
            } else {
                board::error($response['message']);
            }
        } catch (\InvalidArgumentException $e) {
            board::error(lang::get_phrase("error_color_format") . $e->getMessage());
        }

        echo json_encode(['success' => true, 'message' => lang::get_phrase("success_color_changed")]);
    }

}