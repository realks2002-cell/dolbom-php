-- 매니저 승인 상태 컬럼 추가
-- approval_status: 'pending' (승인 대기), 'approved' (승인됨), 'rejected' (거절됨)

ALTER TABLE `managers`
ADD COLUMN `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' COMMENT '승인 상태' AFTER `password_hash`,
ADD COLUMN `approved_at` TIMESTAMP NULL DEFAULT NULL COMMENT '승인 일시' AFTER `approval_status`,
ADD COLUMN `rejected_at` TIMESTAMP NULL DEFAULT NULL COMMENT '거절 일시' AFTER `approved_at`,
ADD COLUMN `rejection_reason` VARCHAR(500) NULL DEFAULT NULL COMMENT '거절 사유' AFTER `rejected_at`;

-- 기존 매니저는 모두 승인 상태로 설정 (기존 데이터 호환성)
UPDATE `managers` SET `approval_status` = 'approved', `approved_at` = `created_at` WHERE `approval_status` = 'pending';

-- 인덱스 추가 (승인 상태별 조회 성능 향상)
ALTER TABLE `managers` ADD INDEX `idx_managers_approval_status` (`approval_status`);
