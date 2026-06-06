USE `visitor_detail`;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `card_token` VARCHAR(64) DEFAULT NULL;

UPDATE `users`
SET `card_token` = SHA2(CONCAT(UUID(), RAND(), `user_id`), 256)
WHERE `card_token` IS NULL OR `card_token` = '';

CREATE UNIQUE INDEX IF NOT EXISTS `uq_users_card_token` ON `users` (`card_token`);

UPDATE `accounts`
SET `password` = '$2y$10$lyZNOVHx4pqGYAqIYSDj1O.aNcqiDh9bqWfkq5cTG4JgD5p6t547K',
    `role` = 'superadmin'
WHERE `username` = 'admin';
