<?php

return [
    'ALTER TABLE `users` 
     ADD COLUMN `last_activity` DATETIME DEFAULT NULL,
     ADD INDEX `idx_last_activity` (`last_activity`);',
];
