CREATE TABLE IF NOT EXISTS `wechat_official_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `app_id` VARCHAR(64) NOT NULL,
  `app_secret` VARCHAR(128) NOT NULL,
  `token` VARCHAR(128) NOT NULL,
  `aes_key` VARCHAR(128) DEFAULT NULL,
  `original_id` VARCHAR(64) DEFAULT NULL,
  `encoding_type` VARCHAR(20) NOT NULL DEFAULT 'plaintext',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `menu_config` JSON DEFAULT NULL,
  `menu_published_at` TIMESTAMP NULL DEFAULT NULL,
  `remark` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wechat_official_accounts_app_id` (`app_id`),
  KEY `idx_wechat_official_accounts_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wechat_reply_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `msg_type` VARCHAR(32) NOT NULL DEFAULT '*',
  `event` VARCHAR(64) DEFAULT NULL,
  `keyword` VARCHAR(255) DEFAULT NULL,
  `keyword_match` VARCHAR(20) NOT NULL DEFAULT 'contains',
  `reply_type` VARCHAR(32) NOT NULL DEFAULT 'text',
  `reply_content` JSON NOT NULL,
  `priority` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wechat_reply_rules_account` (`account_id`),
  KEY `idx_wechat_reply_rules_match` (`account_id`, `is_active`, `msg_type`, `event`, `priority`),
  CONSTRAINT `fk_wechat_reply_rules_account`
    FOREIGN KEY (`account_id`) REFERENCES `wechat_official_accounts` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
