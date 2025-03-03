<?php

namespace Ofey\Logan22\model\log;

enum logTypes: int
{
    case LOG_REGISTRATION_ACCOUNT = 1;
    case LOG_LOGIN = 2;
    case LOG_DONATE_SUCCESS = 3;
    case LOG_CHANGE_ACCOUNT_PASSWORD = 4;
    case LOG_ADD_DONATE_ITEM_BONUS = 5;
    case LOG_INVENTORY_TO_GAME = 6;
    case LOG_DONATE_COIN_TO_GAME = 7;
    case LOG_USER_CHANGE_PROFILE = 8;
    case LOG_CHANGE_AVATAR = 9;
    case LOG_REGISTRATION_USER = 10;
    case LOG_BONUS_CODE = 11;
    case LOG_SAVE_CONFIG = 12;
    case LOG_WHEEL_WIN = 13;
    case LOG_WINROW_WIN = 14;
    case LOG_DONATE_BONUS_SUCCESS = 15;
}

