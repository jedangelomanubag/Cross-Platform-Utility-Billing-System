-- Add BillingImage column to billingstatement table
ALTER TABLE `billingstatement` 
ADD COLUMN `BillingImage` VARCHAR(255) NULL AFTER `TotalAmount`,
ADD COLUMN `ImageUploadDate` TIMESTAMP NULL AFTER `BillingImage`,
ADD COLUMN `UploadedBy` INT NULL AFTER `ImageUploadDate`,
ADD CONSTRAINT `fk_billing_uploader` 
    FOREIGN KEY (`UploadedBy`) 
    REFERENCES `utilityreader` (`ReaderID`) 
    ON DELETE SET NULL;

-- Add index for better performance
CREATE INDEX `idx_billing_image` ON `billingstatement` (`BillingImage`);
