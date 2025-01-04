<?php

return [
    'ALTER TABLE `support_read_topics`
     MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
     ADD PRIMARY KEY (`id`);
',
    'ALTER TABLE `support_message`
     MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
     ADD PRIMARY KEY (`id`);
',
];
