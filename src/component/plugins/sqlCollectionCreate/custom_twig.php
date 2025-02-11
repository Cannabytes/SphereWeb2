<?php

namespace Ofey\Logan22\component\plugins\sqlCollectionCreate;

class custom_twig
{

    static public function get_struct_func($name): ?array
    {
        return match ($name) {
            'statistic_castle' => self::struct_statistic_castle(),
            'statistic_clan' => self::struct_statistic_clan(),
            'statistic_exp' => self::struct_statistic_exp(),
            'statistic_online' => self::struct_statistic_online(),
            'statistic_pk' => self::struct_statistic_pk(),
            'statistic_pvp' => self::struct_statistic_pvp(),
            default => null,
        };
    }

    static public function struct_statistic_castle(): array
    {
        return [
            'castle_id',
            'tax',
            'treasury',
            'siege_date',
            'clan_name',
            'clan_level',
            'player_name',
            'clan_crest',
            'alliance_crest',
        ];
    }

    static public function struct_statistic_clan(): array
    {
        return [
            'castle_id',
            'reputation_score',
            'leader_id',
            'player_name',
            'pvp',
            'pk',
            'online',
            'time_in_game',
            'sex',
            'clan_name',
            'clan_level',
            'clan_crest',
            'alliance_crest',
            'clan_count_members'
        ];
    }

    static public function struct_statistic_exp(): array
    {
        return [
            'player_id',
            'player_name',
            'pvp',
            'pk',
            'sex',
            'online',
            'time_in_game',
            'clan_name',
            'clan_level',
            'class_id',
            'level',
            'clan_crest',
            'alliance_crest'
        ];
    }
    static public function struct_statistic_online(): array
    {
        return [
            'player_id', 'player_name', 'pvp', 'pk', 'sex', 'online', 'time_in_game',
            'clan_name', 'clan_level', 'class_id', 'level', 'clan_crest', 'alliance_crest'
        ];
    }

    static public function struct_statistic_pk(): array
    {
        return [
            'player_id',
            'player_name',
            'pvp',
            'pk',
            'sex',
            'online',
            'time_in_game',
            'clan_name',
            'clan_level',
            'class_id',
            'level', 
            'clan_crest',
            'alliance_crest'
        ];

    }
    static public function struct_statistic_pvp(): array
    {
        return [
            'player_id',
            'player_name',
            'pvp',
            'pk',
            'sex',
            'online',
            'time_in_game',
            'clan_name',
            'clan_level',
            'class_id',
            'level',
            'clan_crest',
            'alliance_crest'
        ];
    }


}