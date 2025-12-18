<?php

return [
	[
		'enable' => 1,
		'method' => 'POST',
		'pattern' => '/admin/statistic/donate/clear',
		'func' => 'controller\\admin\\statistic::donateClear',
		'access' => [
			0 => 'admin',
		],
		'weight' => 0,
		'page' => '',
		'comment' => 'Admin clear donate by date range',
	],
];
