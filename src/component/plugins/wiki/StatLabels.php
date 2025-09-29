<?php

namespace Ofey\Logan22\component\plugins\wiki;

class StatLabels
{
    /**
     * Map raw stat codes to language phrase keys.
     * The actual translated value is resolved lazily through lang::get_phrase().
     * @var array<string,string>
     */
    private static array $MAP = [
        // Core combat stats
        'pAtk' => 'wiki_stat_pAtk',
        'mAtk' => 'wiki_stat_mAtk',
        'pDef' => 'wiki_stat_pDef',
        'mDef' => 'wiki_stat_mDef',
        'pAtkSpd' => 'wiki_stat_pAtkSpd',
        'mAtkSpd' => 'wiki_stat_mAtkSpd',
        'mReuse' => 'wiki_stat_mReuse',
        'atkReuse' => 'wiki_stat_atkReuse',
        'critRate' => 'wiki_stat_critRate',
        'critDamage' => 'wiki_stat_critDamage',
        'pCritDamage' => 'wiki_stat_pCritDamage',
        'mCritDamage' => 'wiki_stat_mCritDamage',
        'skillCritRate' => 'wiki_stat_skillCritRate',
        'skillCritDamage' => 'wiki_stat_skillCritDamage',
        'cAtk' => 'wiki_stat_cAtk',
        'cAtkAdd' => 'wiki_stat_cAtkAdd',
        'rCrit' => 'wiki_stat_rCrit',
        'mCritRate' => 'wiki_stat_mCritRate',
        'skillPower' => 'wiki_stat_skillPower',
        'skillMastery' => 'wiki_stat_skillMastery',

        // Accuracy / evasion / shield
    'acc' => 'wiki_stat_acc',
    'accCombat' => 'wiki_stat_accCombat',
    'evasion' => 'wiki_stat_evasion',
    'rEvas' => 'wiki_stat_rEvas',
    'pSkillEvas' => 'wiki_stat_pSkillEvas',
    'shieldDef' => 'wiki_stat_shieldDef',
    'sDef' => 'wiki_stat_sDef',
    'shieldRate' => 'wiki_stat_shieldRate',
    'rShld' => 'wiki_stat_rShld',
    'shieldDefAngle' => 'wiki_stat_shieldDefAngle',

        // Vital resources
    'maxHp' => 'wiki_stat_maxHp',
    'maxMp' => 'wiki_stat_maxMp',
    'maxCp' => 'wiki_stat_maxCp',
    'hp' => 'wiki_stat_hp',
    'mp' => 'wiki_stat_mp',
    'cp' => 'wiki_stat_cp',
    'regHp' => 'wiki_stat_regHp',
    'regMp' => 'wiki_stat_regMp',
    'regCp' => 'wiki_stat_regCp',
    'hpRegen' => 'wiki_stat_hpRegen',
    'mpRegen' => 'wiki_stat_mpRegen',
    'cpRegen' => 'wiki_stat_cpRegen',
    'gainHp' => 'wiki_stat_gainHp',
    'giveHp' => 'wiki_stat_giveHp',
    'bonusHp' => 'wiki_stat_bonusHp',

        // Primary attributes
    'str' => 'wiki_stat_str',
    'dex' => 'wiki_stat_dex',
    'con' => 'wiki_stat_con',
    'int' => 'wiki_stat_int',
    'wit' => 'wiki_stat_wit',
    'men' => 'wiki_stat_men',
    'luc' => 'wiki_stat_luc',
    'cha' => 'wiki_stat_cha',

        // Movement & ranges
    'runSpd' => 'wiki_stat_runSpd',
    'walkSpd' => 'wiki_stat_walkSpd',
    'swimSpd' => 'wiki_stat_swimSpd',
    'atkRange' => 'wiki_stat_atkRange',
    'pAtkRange' => 'wiki_stat_pAtkRange',
    'mAtkRange' => 'wiki_stat_mAtkRange',
    'castRange' => 'wiki_stat_castRange',
    'bowAtkRange' => 'wiki_stat_bowAtkRange',
    'pAtkAngle' => 'wiki_stat_pAtkAngle',

        // Capacity
    'load' => 'wiki_stat_load',
    'breath' => 'wiki_stat_breath',

        // Rates
    'expRate' => 'wiki_stat_expRate',
    'spRate' => 'wiki_stat_spRate',
    'adenaRate' => 'wiki_stat_adenaRate',
    'dropRate' => 'wiki_stat_dropRate',
    'spoilRate' => 'wiki_stat_spoilRate',
    'questRate' => 'wiki_stat_questRate',
    'rExp' => 'wiki_stat_rExp',

        // PvP / PvE
    'pvpPAtk' => 'wiki_stat_pvpPAtk',
    'pvpMAtk' => 'wiki_stat_pvpMAtk',
    'pvpPDef' => 'wiki_stat_pvpPDef',
    'pvpMDef' => 'wiki_stat_pvpMDef',
    'pvePAtk' => 'wiki_stat_pvePAtk',
    'pveMAtk' => 'wiki_stat_pveMAtk',
    'pvePDef' => 'wiki_stat_pvePDef',
    'pveMDef' => 'wiki_stat_pveMDef',
    'pvpPhysDmg' => 'wiki_stat_pvpPhysDmg',
    'pvpMagicalDmg' => 'wiki_stat_pvpMagicalDmg',
    'pvpPhysSkillsDmg' => 'wiki_stat_pvpPhysSkillsDmg',
    'atkCountMax' => 'wiki_stat_atkCountMax',

        // Elemental attack / defence
    'fireAtk' => 'wiki_stat_fireAtk',
    'waterAtk' => 'wiki_stat_waterAtk',
    'windAtk' => 'wiki_stat_windAtk',
    'earthAtk' => 'wiki_stat_earthAtk',
    'holyAtk' => 'wiki_stat_holyAtk',
    'darkAtk' => 'wiki_stat_darkAtk',
    'fireDef' => 'wiki_stat_fireDef',
    'waterDef' => 'wiki_stat_waterDef',
    'windDef' => 'wiki_stat_windDef',
    'earthDef' => 'wiki_stat_earthDef',
    'holyDef' => 'wiki_stat_holyDef',
    'darkDef' => 'wiki_stat_darkDef',

        // Vulnerabilities
    'aggressionVuln' => 'wiki_stat_aggressionVuln',
    'bleedVuln' => 'wiki_stat_bleedVuln',
    'poisonVuln' => 'wiki_stat_poisonVuln',
    'stunVuln' => 'wiki_stat_stunVuln',
    'paralyzeVuln' => 'wiki_stat_paralyzeVuln',
    'rootVuln' => 'wiki_stat_rootVuln',
    'sleepVuln' => 'wiki_stat_sleepVuln',
    'confusionVuln' => 'wiki_stat_confusionVuln',
    'movementVuln' => 'wiki_stat_movementVuln',
    'fireVuln' => 'wiki_stat_fireVuln',
    'windVuln' => 'wiki_stat_windVuln',
    'waterVuln' => 'wiki_stat_waterVuln',
    'earthVuln' => 'wiki_stat_earthVuln',
    'holyVuln' => 'wiki_stat_holyVuln',
    'darkVuln' => 'wiki_stat_darkVuln',
    'cancelVuln' => 'wiki_stat_cancelVuln',
    'debuffVuln' => 'wiki_stat_debuffVuln',
    'critVuln' => 'wiki_stat_critVuln',

    // Resists (note: some keys may not yet have explicit phrase entries)
    'stunRes' => 'wiki_stat_stunRes',
    'bleedRes' => 'wiki_stat_bleedRes',
    'poisonRes' => 'wiki_stat_poisonRes',
    'paralyzeRes' => 'wiki_stat_paralyzeRes',
    'sleepRes' => 'wiki_stat_sleepRes',
    'holdRes' => 'wiki_stat_holdRes',
    'rootRes' => 'wiki_stat_rootRes',
    'mentalRes' => 'wiki_stat_mentalRes',
    'fearRes' => 'wiki_stat_fearRes',
    'debuffRes' => 'wiki_stat_debuffRes',
    'cancel' => 'wiki_stat_cancel',

    // Weapon vulnerabilities
    'noneWpnVuln' => 'wiki_stat_noneWpnVuln',
    'swordWpnVuln' => 'wiki_stat_swordWpnVuln',
    'bluntWpnVuln' => 'wiki_stat_bluntWpnVuln',
    'daggerWpnVuln' => 'wiki_stat_daggerWpnVuln',
    'bowWpnVuln' => 'wiki_stat_bowWpnVuln',
    'crossbowWpnVuln' => 'wiki_stat_crossbowWpnVuln',
    'poleWpnVuln' => 'wiki_stat_poleWpnVuln',
    'etcWpnVuln' => 'wiki_stat_etcWpnVuln',
    'fistWpnVuln' => 'wiki_stat_fistWpnVuln',
    'dualWpnVuln' => 'wiki_stat_dualWpnVuln',
    'dualFistWpnVuln' => 'wiki_stat_dualFistWpnVuln',
    'bigSwordWpnVuln' => 'wiki_stat_bigSwordWpnVuln',

        // Specials & Reflect
    'vampiric' => 'wiki_stat_vampiric',
    'reflectDamage' => 'wiki_stat_reflectDamage',
    'reflectSkill' => 'wiki_stat_reflectSkill',
    'reflectDam' => 'wiki_stat_reflectDam',
    'absorbDam' => 'wiki_stat_absorbDam',
    'transDam' => 'wiki_stat_transDam',
    'reflectSkillMagic' => 'wiki_stat_reflectSkillMagic',
    'reflectSkillPhysic' => 'wiki_stat_reflectSkillPhysic',
    'absorbHp' => 'wiki_stat_absorbHp',

        // Blow & Lethal
    'blowRate' => 'wiki_stat_blowRate',
    'lethalRate' => 'wiki_stat_lethalRate',

        // Against specific types
    'pAtkGiants' => 'wiki_stat_pAtkGiants',
    'pDefUndead' => 'wiki_stat_pDefUndead',
    'pAtkAnimals' => 'wiki_stat_pAtkAnimals',
    'pDefMonsters' => 'wiki_stat_pDefMonsters',

        // Inventory & Limits
    'inventoryLimit' => 'wiki_stat_inventoryLimit',
    'whLimit' => 'wiki_stat_whLimit',
    'FreightLimit' => 'wiki_stat_FreightLimit',
    'PrivateSellLimit' => 'wiki_stat_PrivateSellLimit',
    'PrivateBuyLimit' => 'wiki_stat_PrivateBuyLimit',
    'DwarfRecipeLimit' => 'wiki_stat_DwarfRecipeLimit',
    'CommonRecipeLimit' => 'wiki_stat_CommonRecipeLimit',

        // Consume rates
    'PhysicalMpConsumeRate' => 'wiki_stat_PhysicalMpConsumeRate',
    'MagicalMpConsumeRate' => 'wiki_stat_MagicalMpConsumeRate',
    'DanceMpConsumeRate' => 'wiki_stat_DanceMpConsumeRate',
    'HpConsumeRate' => 'wiki_stat_HpConsumeRate',
    'MpConsume' => 'wiki_stat_MpConsume',
    'soulShotCount' => 'wiki_stat_soulShotCount',

        // Shots / misc
    'soulshots' => 'wiki_stat_soulshots',
    'spiritshots' => 'wiki_stat_spiritshots',
    'karma' => 'wiki_stat_karma',
    'pk' => 'wiki_stat_pk',
    // Skill types / short descriptions (converted to phrase keys)
    'AGGRESSION' => 'wiki_skilltype_AGGRESSION',
    'AIEFFECTS' => 'wiki_skilltype_AIEFFECTS',
    'BALANCE' => 'wiki_skilltype_BALANCE',
    'BEAST_FEED' => 'wiki_skilltype_BEAST_FEED',
    'BLEED' => 'wiki_skilltype_BLEED',
    'BUFF' => 'wiki_skilltype_BUFF',
    'BUFF_CHARGER' => 'wiki_skilltype_BUFF_CHARGER',
    'CALL' => 'wiki_skilltype_CALL',
    'CLAN_GATE' => 'wiki_skilltype_CLAN_GATE',
    'COMBATPOINTHEAL' => 'wiki_skilltype_COMBATPOINTHEAL',
    'CONT' => 'wiki_skilltype_CONT',
    'CPDAM' => 'wiki_skilltype_CPDAM',
    'CPHOT' => 'wiki_skilltype_CPHOT',
    'CRAFT' => 'wiki_skilltype_CRAFT',
    'DEATH_PENALTY' => 'wiki_skilltype_DEATH_PENALTY',
    'DEBUFF' => 'wiki_skilltype_DEBUFF',
    'DELETE_HATE' => 'wiki_skilltype_DELETE_HATE',
    'DELETE_HATE_OF_ME' => 'wiki_skilltype_DELETE_HATE_OF_ME',
    'DESTROY_SUMMON' => 'wiki_skilltype_DESTROY_SUMMON',
    'DEFUSE_TRAP' => 'wiki_skilltype_DEFUSE_TRAP',
    'DETECT_TRAP' => 'wiki_skilltype_DETECT_TRAP',
    'DISCORD' => 'wiki_skilltype_DISCORD',
    'DOT' => 'wiki_skilltype_DOT',
    'DRAIN' => 'wiki_skilltype_DRAIN',
    'DRAIN_SOUL' => 'wiki_skilltype_DRAIN_SOUL',
    'EFFECT' => 'wiki_skilltype_EFFECT',
    'EFFECTS_FROM_SKILLS' => 'wiki_skilltype_EFFECTS_FROM_SKILLS',
    'ENCHANT_ARMOR' => 'wiki_skilltype_ENCHANT_ARMOR',
    'ENCHANT_WEAPON' => 'wiki_skilltype_ENCHANT_WEAPON',
    'FEED_PET' => 'wiki_skilltype_FEED_PET',
    'FISHING' => 'wiki_skilltype_FISHING',
    'HARDCODED' => 'wiki_skilltype_HARDCODED',
    'HARVESTING' => 'wiki_skilltype_HARVESTING',
    'HEAL' => 'wiki_skilltype_HEAL',
    'HEAL_PERCENT' => 'wiki_skilltype_HEAL_PERCENT',
    'HOT' => 'wiki_skilltype_HOT',
    'LETHAL_SHOT' => 'wiki_skilltype_LETHAL_SHOT',
    'LUCK' => 'wiki_skilltype_LUCK',
    'MANADAM' => 'wiki_skilltype_MANADAM',
    'MANAHEAL' => 'wiki_skilltype_MANAHEAL',
    'MANAHEAL_PERCENT' => 'wiki_skilltype_MANAHEAL_PERCENT',
    'MDAM' => 'wiki_skilltype_MDAM',
    'MDOT' => 'wiki_skilltype_MDOT',
    'MPHOT' => 'wiki_skilltype_MPHOT',
    'MUTE' => 'wiki_skilltype_MUTE',
    'DISMISS_AGATHION' => 'wiki_skilltype_DISMISS_AGATHION',
    'NEGATE_EFFECTS' => 'wiki_skilltype_NEGATE_EFFECTS',
    'NEGATE_STATS' => 'wiki_skilltype_NEGATE_STATS',
    'ADD_PC_BANG' => 'wiki_skilltype_ADD_PC_BANG',
    'NOTDONE' => 'wiki_skilltype_NOTDONE',
    'NOTUSED' => 'wiki_skilltype_NOTUSED',
    'PARALYZE' => 'wiki_skilltype_PARALYZE',
    'PASSIVE' => 'wiki_skilltype_PASSIVE',
    'PDAM' => 'wiki_skilltype_PDAM',
    'PET_SUMMON' => 'wiki_skilltype_PET_SUMMON',
    'POISON' => 'wiki_skilltype_POISON',
    'PUMPING' => 'wiki_skilltype_PUMPING',
    'RECALL' => 'wiki_skilltype_RECALL',
    'REELING' => 'wiki_skilltype_REELING',
    'RESURRECT' => 'wiki_skilltype_RESURRECT',
    'RIDE' => 'wiki_skilltype_RIDE',
    'ROOT' => 'wiki_skilltype_ROOT',
    'SHIFT_AGGRESSION' => 'wiki_skilltype_SHIFT_AGGRESSION',
    'SSEED' => 'wiki_skilltype_SSEED',
    'SLEEP' => 'wiki_skilltype_SLEEP',
    'SOULSHOT' => 'wiki_skilltype_SOULSHOT',
    'SOWING' => 'wiki_skilltype_SOWING',
    'SPHEAL' => 'wiki_skilltype_SPHEAL',
    'EXPHEAL' => 'wiki_skilltype_EXPHEAL',
    'SPIRITSHOT' => 'wiki_skilltype_SPIRITSHOT',
    'SPOIL' => 'wiki_skilltype_SPOIL',
    'STEAL_BUFF' => 'wiki_skilltype_STEAL_BUFF',
    'STUN' => 'wiki_skilltype_STUN',
    'SUMMON' => 'wiki_skilltype_SUMMON',
    'SUMMON_FLAG' => 'wiki_skilltype_SUMMON_FLAG',
    'SUMMON_ITEM' => 'wiki_skilltype_SUMMON_ITEM',
    'SWEEP' => 'wiki_skilltype_SWEEP',
    'TAKECASTLE' => 'wiki_skilltype_TAKECASTLE',
    'TAMECONTROL' => 'wiki_skilltype_TAMECONTROL',
    'TELEPORT_NPC' => 'wiki_skilltype_TELEPORT_NPC',
    'TRANSFORMATION' => 'wiki_skilltype_TRANSFORMATION',
    'UNLOCK' => 'wiki_skilltype_UNLOCK',
    'WATCHER_GAZE' => 'wiki_skilltype_WATCHER_GAZE',

    // Skill parameters / properties (from Lucera2 guide)
    'activateRate' => 'wiki_skillparam_activateRate',
    'absorbPart' => 'wiki_skillparam_absorbPart',
    'baseBlowRate' => 'wiki_skillparam_baseBlowRate',
    'coolTime' => 'wiki_skillparam_coolTime',
    'hitTime' => 'wiki_skillparam_hitTime',
    'reuseDelay' => 'wiki_skillparam_reuseDelay',
    'skillRadius' => 'wiki_skillparam_skillRadius',
    'effectiveRange' => 'wiki_skillparam_effectiveRange',
    'power' => 'wiki_skillparam_power',
    'powerPvP' => 'wiki_skillparam_powerPvP',
    'powerPvE' => 'wiki_skillparam_powerPvE',
    'mpConsume1' => 'wiki_skillparam_mpConsume1',
    'mpConsume2' => 'wiki_skillparam_mpConsume2',
    'hpConsume' => 'wiki_skillparam_hpConsume',
    'energyConsume' => 'wiki_skillparam_energyConsume',
    'soulsConsume' => 'wiki_skillparam_soulsConsume',
    'lethal1' => 'wiki_skillparam_lethal1',
    'lethal2' => 'wiki_skillparam_lethal2',
    'effectPoint' => 'wiki_skillparam_effectPoint',
    'skillType' => 'wiki_skillparam_skillType',
    'magicType' => 'wiki_skillparam_magicType',
    'targetType' => 'wiki_skillparam_targetType',
    'operateType' => 'wiki_skillparam_operateType',
    'traitType' => 'wiki_skillparam_traitType',
    'saveVs' => 'wiki_skillparam_saveVs',
    'skillNextAction' => 'wiki_skillparam_skillNextAction',
    'element' => 'wiki_skillparam_element',
    'elementPower' => 'wiki_skillparam_elementPower',
    'flyType' => 'wiki_skillparam_flyType',
    'flyRadius' => 'wiki_skillparam_flyRadius',
    'flyToBack' => 'wiki_skillparam_flyToBack',

    // Boolean flags / behavior
    'isOffensive' => 'wiki_skillflag_isOffensive',
    'isPvpSkill' => 'wiki_skillflag_isPvpSkill',
    'isPvm' => 'wiki_skillflag_isPvm',
    'isForceUse' => 'wiki_skillflag_isForceUse',
    'isCancelable' => 'wiki_skillflag_isCancelable',
    'isReflectable' => 'wiki_skillflag_isReflectable',
    'isShieldignore' => 'wiki_skillflag_isShieldignore',
    'isOverhit' => 'wiki_skillflag_isOverhit',
    'isSuicideAttack' => 'wiki_skillflag_isSuicideAttack',
    'isSoulBoost' => 'wiki_skillflag_isSoulBoost',
    'isChargeBoost' => 'wiki_skillflag_isChargeBoost',
    'isUsingWhileCasting' => 'wiki_skillflag_isUsingWhileCasting',
    'isIgnoreResists' => 'wiki_skillflag_isIgnoreResists',
    'isIgnoreInvul' => 'wiki_skillflag_isIgnoreInvul',
    'isTrigger' => 'wiki_skillflag_isTrigger',
    'isNotAffectedByMute' => 'wiki_skillflag_isNotAffectedByMute',
    'isSkillTimePermanent' => 'wiki_skillflag_isSkillTimePermanent',
    'isReuseDelayPermanent' => 'wiki_skillflag_isReuseDelayPermanent',
    'isBehind' => 'wiki_skillflag_isBehind',
    'isCorpse' => 'wiki_skillflag_isCorpse',
    'isUndeadOnly' => 'wiki_skillflag_isUndeadOnly',
    'isUseSS' => 'wiki_skillflag_isUseSS',
    'isCommon' => 'wiki_skillflag_isCommon',
    'isItemHandler' => 'wiki_skillflag_isItemHandler',
    'isAltUse' => 'wiki_skillflag_isAltUse',
    'isNewbie' => 'wiki_skillflag_isNewbie',
    'isPreservedOnDeath' => 'wiki_skillflag_isPreservedOnDeath',
    'isHeroic' => 'wiki_skillflag_isHeroic',
    'isSaveable' => 'wiki_skillflag_isSaveable',
    'isMultiClassSkill' => 'wiki_skillflag_isMultiClassSkill',
    'isFishingSkill' => 'wiki_skillflag_isFishingSkill',
    'isProvoke' => 'wiki_skillflag_isProvoke',
    'isCubicSkill' => 'wiki_skillflag_isCubicSkill',
    'isSelfDispellable' => 'wiki_skillflag_isSelfDispellable',
    'isSlotNone' => 'wiki_skillflag_isSlotNone',
    'isSharedClassReuse' => 'wiki_skillflag_isSharedClassReuse',
    'isIncreaseLevel' => 'wiki_skillflag_isIncreaseLevel',
    'isCheckCanSee' => 'wiki_skillflag_isCheckCanSee',

    // Misc / additional params
    'itemConsume' => 'wiki_skillmisc_itemConsume',
    'itemConsumeId' => 'wiki_skillmisc_itemConsumeId',
    'referenceItemId' => 'wiki_skillmisc_referenceItemId',
    'referenceItemMpConsume' => 'wiki_skillmisc_referenceItemMpConsume',
    'negateSkill' => 'wiki_skillmisc_negateSkill',
    'negatePower' => 'wiki_skillmisc_negatePower',
    'skillInterruptTime' => 'wiki_skillmisc_skillInterruptTime',
    'delayedEffect' => 'wiki_skillmisc_delayedEffect',
    'cancelTarget' => 'wiki_skillmisc_cancelTarget',
    'minPledgeClass' => 'wiki_skillmisc_minPledgeClass',
    'minRank' => 'wiki_skillmisc_minRank',
    'weaponsAllowed' => 'wiki_skillmisc_weaponsAllowed',
    'npcId' => 'wiki_skillmisc_npcId',
    'symbolId' => 'wiki_skillmisc_symbolId',
    'enchantLevelCount' => 'wiki_skillmisc_enchantLevelCount',
    'criticalRate' => 'wiki_skillmisc_criticalRate',
    'secondSkill' => 'wiki_skillmisc_secondSkill',
    'name' => 'wiki_skillmisc_name',
    'baseValues' => 'wiki_skillmisc_baseValues',
    'icon' => 'wiki_skillmisc_icon',
    'teachers' => 'wiki_skillmisc_teachers',
    'canLearn' => 'wiki_skillmisc_canLearn',
    'addedSkills' => 'wiki_skillmisc_addedSkills',
    'effectTemplates' => 'wiki_skillmisc_effectTemplates',
    'preCondition' => 'wiki_skillmisc_preCondition',

    // Misc
    'fall' => 'wiki_misc_fall',
    'expLost' => 'wiki_misc_expLost',

    // Resists
    'bleedResist' => 'wiki_resist_bleedResist',
    'poisonResist' => 'wiki_resist_poisonResist',
    'stunResist' => 'wiki_resist_stunResist',
    'rootResist' => 'wiki_resist_rootResist',
    'mentalResist' => 'wiki_resist_mentalResist',
    'sleepResist' => 'wiki_resist_sleepResist',
    'paralyzeResist' => 'wiki_resist_paralyzeResist',
    'cancelResist' => 'wiki_resist_cancelResist',
    'debuffResist' => 'wiki_resist_debuffResist',
    'magicResist' => 'wiki_resist_magicResist',

    // Effect strengths
    'bleedPower' => 'wiki_power_bleedPower',
    'poisonPower' => 'wiki_power_poisonPower',
    'stunPower' => 'wiki_power_stunPower',
    'rootPower' => 'wiki_power_rootPower',
    'mentalPower' => 'wiki_power_mentalPower',
    'sleepPower' => 'wiki_power_sleepPower',
    'paralyzePower' => 'wiki_power_paralyzePower',
    'cancelPower' => 'wiki_power_cancelPower',
    'debuffPower' => 'wiki_power_debuffPower',
    'magicPower' => 'wiki_power_magicPower',

    // Crits & vulnerabilities
    'SkillCritChanceMod' => 'wiki_misc_SkillCritChanceMod',
    'deathVuln' => 'wiki_misc_deathVuln',
    'critDamRcpt' => 'wiki_misc_critDamRcpt',
    'critChanceRcpt' => 'wiki_misc_critChanceRcpt',

    // Elemental defence
    'defenceFire' => 'wiki_def_defenceFire',
    'defenceWater' => 'wiki_def_defenceWater',
    'defenceWind' => 'wiki_def_defenceWind',
    'defenceEarth' => 'wiki_def_defenceEarth',
    'defenceHoly' => 'wiki_def_defenceHoly',
    'defenceUnholy' => 'wiki_def_defenceUnholy',

    // Elemental attack
    'attackFire' => 'wiki_atk_attackFire',
    'attackWater' => 'wiki_atk_attackWater',
    'attackWind' => 'wiki_atk_attackWind',
    'attackEarth' => 'wiki_atk_attackEarth',
    'attackHoly' => 'wiki_atk_attackHoly',
    'attackUnholy' => 'wiki_atk_attackUnholy',

    // Damage transfer / absorb
    'absorbDamToMp' => 'wiki_absorb_absorbDamToMp',
    'absorbDamToMpChance' => 'wiki_absorb_absorbDamToMpChance',
    'transferPetDam' => 'wiki_absorb_transferPetDam',
    'transferToEffectorDam' => 'wiki_absorb_transferToEffectorDam',

    // Damage reflection / block
    'reflectAndBlockDam' => 'wiki_reflect_reflectAndBlockDam',
    'reflectAndBlockPSkillDam' => 'wiki_reflect_reflectAndBlockPSkillDam',
    'reflectAndBlockMSkillDam' => 'wiki_reflect_reflectAndBlockMSkillDam',
    'absorbDamageValue' => 'wiki_reflect_absorbDamageValue',
    'reflectPSkillDam' => 'wiki_reflect_reflectPSkillDam',
    'reflectMSkillDam' => 'wiki_reflect_reflectMSkillDam',

    // Reflect skills & debuffs
    'reflectPhysicSkill' => 'wiki_reflect_reflectPhysicSkill',
    'reflectMagicSkill' => 'wiki_reflect_reflectMagicSkill',
    'reflectPhysicDebuff' => 'wiki_reflect_reflectPhysicDebuff',
    'reflectMagicDebuff' => 'wiki_reflect_reflectMagicDebuff',

    // Evasion & counter
    'pSkillEvasion' => 'wiki_evasion_pSkillEvasion',
    'counterAttack' => 'wiki_evasion_counterAttack',

    // PvP bonuses
    'pvpPhysDmgBonus' => 'wiki_pvp_pvpPhysDmgBonus',
    'pvpPhysSkillDmgBonus' => 'wiki_pvp_pvpPhysSkillDmgBonus',
    'pvpMagicSkillDmgBonus' => 'wiki_pvp_pvpMagicSkillDmgBonus',
    'pvpPhysDefenceBonus' => 'wiki_pvp_pvpPhysDefenceBonus',
    'pvpPhysSkillDefenceBonus' => 'wiki_pvp_pvpPhysSkillDefenceBonus',
    'pvpMagicSkillDefenceBonus' => 'wiki_pvp_pvpMagicSkillDefenceBonus',

    // PvE bonuses
    'pvePhysDmgBonus' => 'wiki_pve_pvePhysDmgBonus',
    'pvePhysSkillDmgBonus' => 'wiki_pve_pvePhysSkillDmgBonus',
    'pveMagicSkillDmgBonus' => 'wiki_pve_pveMagicSkillDmgBonus',
    'pvePhysDefenceBonus' => 'wiki_pve_pvePhysDefenceBonus',
    'pvePhysSkillDefenceBonus' => 'wiki_pve_pvePhysSkillDefenceBonus',
    'pveMagicSkillDefenceBonus' => 'wiki_pve_pveMagicSkillDefenceBonus',

    // Shot bonuses
    'ssBonus' => 'wiki_shot_ssBonus',
    'spsBonus' => 'wiki_shot_spsBonus',
    'bspsBonus' => 'wiki_shot_bspsBonus',

    // Healing
    'hpEff' => 'wiki_heal_hpEff',
    'mpEff' => 'wiki_heal_mpEff',
    'cpEff' => 'wiki_heal_cpEff',
    'healPower' => 'wiki_heal_healPower',

    // MP consumption
    'mpConsum' => 'wiki_mp_mpConsum',
    'mpConsumePhysical' => 'wiki_mp_mpConsumePhysical',
    'mpDanceConsume' => 'wiki_mp_mpDanceConsume',
    'cheapShot' => 'wiki_mp_cheapShot',
    'cheapShotChance' => 'wiki_mp_cheapShotChance',
    'miser' => 'wiki_mp_miser',
    'miserChance' => 'wiki_mp_miserChance',

    // Mastery
    // 'skillMastery' already exists

    // Inventory & limits
    'maxLoad' => 'wiki_limit_maxLoad',
    'maxNoPenaltyLoad' => 'wiki_limit_maxNoPenaltyLoad',
    'storageLimit' => 'wiki_limit_storageLimit',
    'tradeLimit' => 'wiki_limit_tradeLimit',
    'CommonRecipeLimit' => 'wiki_limit_CommonRecipeLimit',
    'DwarvenRecipeLimit' => 'wiki_limit_DwarvenRecipeLimit',
    'buffLimit' => 'wiki_limit_buffLimit',
    'cubicsLimit' => 'wiki_limit_cubicsLimit',
    'openCloakSlot' => 'wiki_limit_openCloakSlot',
    'talismansLimit' => 'wiki_limit_talismansLimit',
    'broochLimit' => 'wiki_limit_broochLimit',
    'agathionCharnLimit' => 'wiki_limit_agathionCharnLimit',

    // Progress & rewards
    'gradeExpertiseLevel' => 'wiki_progress_gradeExpertiseLevel',
    'ExpMultiplier' => 'wiki_progress_ExpMultiplier',
    'SpMultiplier' => 'wiki_progress_SpMultiplier',
    'RaidExpMultiplier' => 'wiki_progress_RaidExpMultiplier',
    'RaidSpMultiplier' => 'wiki_progress_RaidSpMultiplier',
    'ItemDropMultiplier' => 'wiki_progress_ItemDropMultiplier',
    'AdenaDropMultiplier' => 'wiki_progress_AdenaDropMultiplier',
    'SpoilDropMultiplier' => 'wiki_progress_SpoilDropMultiplier',
    'SealStonesMultiplier' => 'wiki_progress_SealStonesMultiplier',
    'QuestDropMultiplier' => 'wiki_progress_QuestDropMultiplier',
    'EnchantBonusMultiplier' => 'wiki_progress_EnchantBonusMultiplier',
    'EnchantSkillBonusMultiplier' => 'wiki_progress_EnchantSkillBonusMultiplier',

    // Auto-loot
    'autoLootAll' => 'wiki_autoloot_autoLootAll',
    'autoLootHerb' => 'wiki_autoloot_autoLootHerb',
    'autoLootAdena' => 'wiki_autoloot_autoLootAdena',

    // VIP
    'worldChatBonus' => 'wiki_vip_worldChatBonus',
    'vipBonusesSilverDropChance' => 'wiki_vip_vipBonusesSilverDropChance',
    'vipBonusesGoldDropChance' => 'wiki_vip_vipBonusesGoldDropChance',
    ];

    public static function get(string $code): ?string
    {
        if (!isset(self::$MAP[$code])) {
            return null;
        }
        $key = self::$MAP[$code];
        // Attempt to load phrase via lang system. If phrase missing, fallback to key.
        try {
            return \Ofey\Logan22\component\lang\lang::get_phrase($key);
        } catch (\Throwable $e) {
            return $key; // fallback (developer will see untranslated key)
        }
    }

    /**
     * Получить все характеристики
     * 
     * @return array<string,string> Массив всех характеристик
     */
    public static function all(): array
    {
        $out = [];
        foreach (self::$MAP as $code => $phraseKey) {
            $out[$code] = self::get($code) ?? $phraseKey;
        }
        return $out;
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
        $result = [];
        foreach (self::all() as $code => $label) {
            if (mb_stripos(mb_strtolower($label), $search) !== false) {
                $result[$code] = $label;
            }
        }
        return $result;
    }
}
