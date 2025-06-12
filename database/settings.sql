CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Online Routing System'),
('site_description', 'A system for managing and routing service requests'),
('max_file_size', '5'),
('allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx'),
('request_auto_assign', '0'),
('enable_email_notifications', '0'),
('maintenance_mode', '0')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`); 