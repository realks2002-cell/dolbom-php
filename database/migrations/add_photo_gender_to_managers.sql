-- Migration: add photo and gender to managers
-- 실행: phpMyAdmin 또는 mysql 클라이언트에서 실행

ALTER TABLE `managers`
  ADD COLUMN `photo` VARCHAR(255) DEFAULT NULL COMMENT '프로필 사진 경로',
  ADD COLUMN `gender` ENUM('M','F') DEFAULT NULL COMMENT '성별: M=남, F=여',
  ADD INDEX `idx_managers_gender` (`gender`);
