-- Add BillingPreference to consumer table
ALTER TABLE `consumer` 
ADD COLUMN `BillingPreference` ENUM('paper', 'mobile_app') NOT NULL DEFAULT 'paper' AFTER `Status`,
ADD COLUMN `AppNotificationToken` VARCHAR(255) NULL AFTER `BillingPreference`;

-- Add Source to meterreadingdata table
ALTER TABLE `meterreadingdata` 
ADD COLUMN `Source` ENUM('admin', 'mobile_app', 'paper') NOT NULL DEFAULT 'admin' AFTER `Status`,
ADD COLUMN `ReadingImage` VARCHAR(255) NULL AFTER `Source`;

-- Add indexes for better performance
CREATE INDEX `idx_consumer_billing_preference` ON `consumer` (`BillingPreference`);
CREATE INDEX `idx_reading_source` ON `meterreadingdata` (`Source`);

-- Add a table for push notification logs
CREATE TABLE IF NOT EXISTS `notification_logs` (
    `NotificationID` INT NOT NULL AUTO_INCREMENT,
    `ConsumerID` INT NOT NULL,
    `Title` VARCHAR(100) NOT NULL,
    `Message` TEXT NOT NULL,
    `Type` ENUM('billing', 'reading', 'announcement', 'other') NOT NULL,
    `Status` ENUM('sent', 'delivered', 'failed') NOT NULL,
    `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`NotificationID`),
    INDEX `idx_consumer` (`ConsumerID`),
    INDEX `idx_created_at` (`CreatedAt`),
    CONSTRAINT `fk_notification_consumer` 
        FOREIGN KEY (`ConsumerID`) 
        REFERENCES `consumer` (`ConsumerID`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
