INSERT INTO `lc_settings` (`setting_group_key`, `type`, `title`, `description`, `key`, `value`, `function`, `priority`, `date_updated`, `date_created`)
VALUES ('listings', 'local', 'Auto Decimals', 'Show only decimals if there are any to display.', 'auto_decimals', '1', 'toggle("e/d")', 20, NOW(), NOW());
