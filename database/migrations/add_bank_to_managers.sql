-- Migration: add bank to managers
-- 실행: phpMyAdmin 또는 mysql 클라이언트에서 실행

ALTER TABLE `managers`
  ADD COLUMN `bank` VARCHAR(50) DEFAULT NULL COMMENT '은행명' AFTER `account_number`,
  ADD INDEX `idx_managers_bank` (`bank`);
