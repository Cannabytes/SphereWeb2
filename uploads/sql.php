<?php

return [
    'ALTER TABLE `support_read_topics`
  -- Удаляем текущий первичный ключ, если он есть
  DROP PRIMARY KEY,
  -- Добавляем автоинкремент и первичный ключ на `id`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`id`);
',
];
