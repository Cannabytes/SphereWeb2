<?php

namespace Ofey\Logan22\component\plugins\library;

class StatLabels
{
    /** @var array<string,string> */
    private static array $MAP = [
        // Core combat stats
        'pAtk' => 'Физ. Атака',
        'mAtk' => 'Маг. Атака',
        'pDef' => 'Физ. Защита',
        'mDef' => 'Маг. Защита',
        'pAtkSpd' => 'Скорость Атаки',
        'mAtkSpd' => 'Скорость Магии',
        'mReuse' => 'Скорость Отката',
        'atkReuse' => 'Откат Физ. Скиллов',
        'critRate' => 'Крит. Шанс',
        'critDamage' => 'Крит. Урон',
        'pCritDamage' => 'Физ. Крит. Урон',
        'mCritDamage' => 'Маг. Крит. Урон',
        'skillCritRate' => 'Шанс Крита Скиллов',
        'skillCritDamage' => 'Крит. Урон Скиллов',
        'cAtk' => 'Критический Удар',
        'cAtkAdd' => 'Прибавка к Крит. Удару',
        'rCrit' => 'Шанс Крита',
        'mCritRate' => 'Маг. Крит. Шанс',
        'skillPower' => 'Сила Скиллов',
        'skillMastery' => 'Мастерство Скиллов',

        // Accuracy / evasion / shield
        'acc' => 'Точность',
        'accCombat' => 'Точность',
        'evasion' => 'Уклонение',
        'rEvas' => 'Уворот',
        'pSkillEvas' => 'Уворот от Скиллов',
        'shieldDef' => 'Защита Щита',
        'sDef' => 'Защита Щитом',
        'shieldRate' => 'Шанс Блока Щитом',
        'rShld' => 'Шанс Блокировки Щитом',
        'shieldDefAngle' => 'Угол Защиты Щитом',

        // Vital resources
        'maxHp' => 'Макс. HP',
        'maxMp' => 'Макс. MP',
        'maxCp' => 'Макс. CP',
        'hp' => 'HP',
        'mp' => 'MP',
        'cp' => 'CP',
        'regHp' => 'Регенерация HP',
        'regMp' => 'Регенерация MP',
        'regCp' => 'Регенерация CP',
        'hpRegen' => 'Регенерация HP',
        'mpRegen' => 'Регенерация MP',
        'cpRegen' => 'Регенерация CP',
        'gainHp' => 'Эффективность Хила',
        'giveHp' => 'Умение Хила',
        'bonusHp' => 'Бонус Хила',

        // Primary attributes
        'str' => 'STR',
        'dex' => 'DEX',
        'con' => 'CON',
        'int' => 'INT',
        'wit' => 'WIT',
        'men' => 'MEN',
        'luc' => 'LUC',
        'cha' => 'CHA',

        // Movement & ranges
        'runSpd' => 'Скорость Бега',
        'walkSpd' => 'Скорость Ходьбы',
        'swimSpd' => 'Скорость Плавания',
        'atkRange' => 'Дистанция Атаки',
        'pAtkRange' => 'Зона Покрытия Физ. Атаки',
        'mAtkRange' => 'Зона Покрытия Маг. Атаки',
        'castRange' => 'Дистанция Каста',
        'bowAtkRange' => 'Дист. Атаки (Лук)',
        'pAtkAngle' => 'Угол Атаки',

        // Capacity
        'load' => 'Грузоподъёмность',
        'breath' => 'Запас Дыхания',

        // Rates
        'expRate' => 'Опыт (EXP) %',
        'spRate' => 'SP %',
        'adenaRate' => 'Адена %',
        'dropRate' => 'Дроп %',
        'spoilRate' => 'Споил %',
        'questRate' => 'Квест Награды %',
        'rExp' => 'EXP/SP Шанс',

        // PvP / PvE
        'pvpPAtk' => 'PVP Физ. Атака',
        'pvpMAtk' => 'PVP Маг. Атака',
        'pvpPDef' => 'PVP Физ. Защита',
        'pvpMDef' => 'PVP Маг. Защита',
        'pvePAtk' => 'PVE Физ. Атака',
        'pveMAtk' => 'PVE Маг. Атака',
        'pvePDef' => 'PVE Физ. Защита',
        'pveMDef' => 'PVE Маг. Защита',
        'pvpPhysDmg' => 'Физ. Урон в PVP',
        'pvpMagicalDmg' => 'Маг. Урон в PVP',
        'pvpPhysSkillsDmg' => 'Урон Скиллом в PVP',
        'atkCountMax' => 'Кол-во Атакуемых',

        // Elemental attack / defence
        'fireAtk' => 'Огненная Атака',
        'waterAtk' => 'Водная Атака',
        'windAtk' => 'Атака Ветра',
        'earthAtk' => 'Атака Земли',
        'holyAtk' => 'Святая Атака',
        'darkAtk' => 'Тьма Атака',
        'fireDef' => 'Огненная Защита',
        'waterDef' => 'Водная Защита',
        'windDef' => 'Защита Ветра',
        'earthDef' => 'Защита Земли',
        'holyDef' => 'Святая Защита',
        'darkDef' => 'Защита Тьмы',

        // Vulnerabilities
        'aggressionVuln' => 'Уязвимость к Агрессии',
        'bleedVuln' => 'Уязвимость к Кровотечению',
        'poisonVuln' => 'Уязвимость к Яду',
        'stunVuln' => 'Уязвимость к Стану',
        'paralyzeVuln' => 'Уязвимость к Парализации',
        'rootVuln' => 'Уязвимость к Удержанию',
        'sleepVuln' => 'Уязвимость к Сну',
        'confusionVuln' => 'Уязвимость к Смешению',
        'movementVuln' => 'Уязвимость к Движению',
        'fireVuln' => 'Уязвимость к Огню',
        'windVuln' => 'Уязвимость к Ветру',
        'waterVuln' => 'Уязвимость к Воде',
        'earthVuln' => 'Уязвимость к Земле',
        'holyVuln' => 'Уязвимость к Святым Атакам',
        'darkVuln' => 'Уязвимость к Темным Атакам',
        'cancelVuln' => 'Уязвимость к Cancel',
        'debuffVuln' => 'Уязвимость к Дебаффам',
        'critVuln' => 'Уязвимость к Криту',

        // Resists
        'stunRes' => 'Сопротивление Оглушению',
        'bleedRes' => 'Сопротивление Кровотечению',
        'poisonRes' => 'Сопротивление Яду',
        'paralyzeRes' => 'Сопротивление Параличу',
        'sleepRes' => 'Сопротивление Сну',
        'holdRes' => 'Сопротивление Холд',
        'rootRes' => 'Сопротивление Руту',
        'mentalRes' => 'Сопротивление Ментал',
        'fearRes' => 'Сопротивление Страху',
        'debuffRes' => 'Сопротивление Дебаффам',
        'cancel' => 'Cancel',

        // Weapon vulnerabilities
        'noneWpnVuln' => 'Уязвимость к Безоружной Атаке',
        'swordWpnVuln' => 'Уязвимость к Мечам',
        'bluntWpnVuln' => 'Уязвимость к Дробящему',
        'daggerWpnVuln' => 'Уязвимость к Кинжалам',
        'bowWpnVuln' => 'Уязвимость к Лукам',
        'crossbowWpnVuln' => 'Уязвимость к Арбалетам',
        'poleWpnVuln' => 'Уязвимость к Пикам',
        'etcWpnVuln' => 'Уязвимость к Прочему Оружию',
        'fistWpnVuln' => 'Уязвимость к Кастетам',
        'dualWpnVuln' => 'Уязвимость к Парному Оружию',
        'dualFistWpnVuln' => 'Уязвимость к Парным Кастетам',
        'bigSwordWpnVuln' => 'Уязвимость к Двуручным Мечам',

        // Specials & Reflect
        'vampiric' => 'Вампиризм',
        'reflectDamage' => 'Рефлект Физ. Урона',
        'reflectSkill' => 'Рефлект Скиллов',
        'reflectDam' => 'Процент Отражаемого Урона',
        'absorbDam' => 'Процент Рассеиваемого Урона',
        'transDam' => 'Процент Переносимого Урона',
        'reflectSkillMagic' => 'Отражение Маг. Скиллов',
        'reflectSkillPhysic' => 'Отражение Физ. Скиллов',
        'absorbHp' => 'Поглощение HP',

        // Blow & Lethal
        'blowRate' => 'Шанс Blow',
        'lethalRate' => 'Шанс Lethal',

        // Against specific types
        'pAtkGiants' => 'Атака на Гигантов',
        'pDefUndead' => 'Защита от Нежити',
        'pAtkAnimals' => 'Атака на Животных',
        'pDefMonsters' => 'Защита от Монстров',

        // Inventory & Limits
        'inventoryLimit' => 'Лимит Инвентаря',
        'whLimit' => 'Лимит Склада',
        'FreightLimit' => 'Лимит Freight',
        'PrivateSellLimit' => 'Лимит Продажи',
        'PrivateBuyLimit' => 'Лимит Покупки',
        'DwarfRecipeLimit' => 'Лимит Гномьего Крафта',
        'CommonRecipeLimit' => 'Лимит Обычного Крафта',

        // Consume rates
        'PhysicalMpConsumeRate' => 'Потребление MP Физ. Скиллами',
        'MagicalMpConsumeRate' => 'Потребление MP Маг. Скиллами',
        'DanceMpConsumeRate' => 'Потребление MP Танцами',
        'HpConsumeRate' => 'Потребление HP',
        'MpConsume' => 'Потребление MP',
        'soulShotCount' => 'Потребление Соулшотов',

        // Shots / misc
        'soulshots' => 'Soulshots',
        'spiritshots' => 'Spiritshots',
        'karma' => 'Карма',
        'pk' => 'PK',
    ];

    public static function get(string $code): ?string
    {
        return self::$MAP[$code] ?? null;
    }

    /**
     * Получить все характеристики
     * 
     * @return array<string,string> Массив всех характеристик
     */
    public static function all(): array
    {
        return self::$MAP;
    }

    /**
     * Проверить существование характеристики
     * 
     * @param string $code Код характеристики
     * @return bool
     */
    public static function exists(string $code): bool
    {
        return isset(self::$MAP[$code]);
    }

    /**
     * Поиск характеристик по частичному совпадению
     * 
     * @param string $search Строка поиска
     * @return array<string,string> Найденные характеристики
     */
    public static function search(string $search): array
    {
        $search = mb_strtolower($search);
        return array_filter(
            self::$MAP,
            fn($label) => mb_stripos($label, $search) !== false,
            ARRAY_FILTER_USE_BOTH
        );
    }
}
