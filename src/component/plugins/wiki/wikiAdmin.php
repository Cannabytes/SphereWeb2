<?php

namespace Ofey\Logan22\component\plugins\wiki;

use Ofey\Logan22\model\admin\validation;

/**
 * Minimal admin controller for the wiki plugin.
 * Only exposes a protected settings entry. Heavy admin CRUD and moderation
 * features were intentionally removed.
 */
class wikiAdmin
{
    public static function ensureAdmin(): void
    {
        validation::user_protection('admin');
    }

    public static function index(): void
    {
        self::ensureAdmin();
        $wiki = new wiki();
        $wiki->setting();
    }

    public static function setting(): void
    {
        self::ensureAdmin();
        $wiki = new wiki();
        $wiki->setting();
    }
}
