<?php

namespace Ofey\Logan22\component\plugins\wiki;

use Ofey\Logan22\component\lang\lang;

class custom_twig
{
	/**
	 * Returns an HTML backlink for an NPC. Example output:
	 * <a href="/wiki/npcs/monsters/21-30" class="back-link ...">...Монстры</a>
	 * or for raids:
	 * <a href="/wiki/npcs/raidboses/51-60" ...>Рейд Боссы</a>
	 *
	 * @param array|object $npc
	 * @return string HTML string (safe to output as raw)
	 */
	public function npc_backlink($npc)
	{
		// normalize access to fields whether $npc is array or object
		$get = function ($key) use ($npc) {
			if (is_array($npc)) return $npc[$key] ?? null;
			if (is_object($npc) && isset($npc->$key)) return $npc->$key;
			return null;
		};

		$type = strtolower((string)($get('type') ?? ''));
		$isRaid = $type !== '' && strpos($type, 'raid') !== false;

		$level = $get('level');
		$range = '';
		if ($level !== null && $level !== '') {
			$lvl = (int)$level;
			$n = $lvl - 1;
			$rem = $n % 10;
			$base = ($n - $rem) / 10;
			$start = ($base * 10) + 1;
			$end = $start + 9;
			$range = "$start-$end";
		}

			if ($isRaid) {
				$url = '/wiki/npcs/raidboses' . ($range ? '/' . $range : '');
				$label = lang::get_phrase('Raid Bosses');
			} else {
				$url = '/wiki/npcs/monsters' . ($range ? '/' . $range : '');
				$label = lang::get_phrase('Monsters');
			}

		// build safe HTML (values are controlled here)
		$html = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="back-link d-inline-flex align-items-center gap-2">';
		$html .= '<span class="chevron" aria-hidden="true">←</span>';
		$html .= '<span class="label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
		$html .= '</a>';
		return $html;
	}

}
