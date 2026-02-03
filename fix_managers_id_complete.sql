-- managers.id를 CHAR(36)으로 변경 (모든 FK 처리)
-- phpMyAdmin에서 한 줄씩 실행하세요

-- 1. 모든 FK 제거
ALTER TABLE applications DROP FOREIGN KEY fk_applications_manager;

ALTER TABLE bookings DROP FOREIGN KEY fk_bookings_manager;

ALTER TABLE manager_device_tokens DROP FOREIGN KEY fk_manager_device_tokens_manager;

ALTER TABLE reviews DROP FOREIGN KEY fk_reviews_manager;

-- 2. 관련 테이블의 manager_id 타입 변경
ALTER TABLE applications MODIFY COLUMN manager_id CHAR(36) NULL;

ALTER TABLE bookings MODIFY COLUMN manager_id CHAR(36) NULL;

ALTER TABLE manager_device_tokens MODIFY COLUMN manager_id CHAR(36) NOT NULL;

ALTER TABLE reviews MODIFY COLUMN manager_id CHAR(36) NOT NULL;

-- 3. managers.id 타입 변경
ALTER TABLE managers MODIFY COLUMN id CHAR(36) NOT NULL;

-- 4. 모든 FK 재추가
ALTER TABLE applications ADD CONSTRAINT fk_applications_manager FOREIGN KEY (manager_id) REFERENCES managers(id) ON DELETE CASCADE;

ALTER TABLE bookings ADD CONSTRAINT fk_bookings_manager FOREIGN KEY (manager_id) REFERENCES managers(id) ON DELETE SET NULL;

ALTER TABLE manager_device_tokens ADD CONSTRAINT fk_manager_device_tokens_manager FOREIGN KEY (manager_id) REFERENCES managers(id) ON DELETE CASCADE;

ALTER TABLE reviews ADD CONSTRAINT fk_reviews_manager FOREIGN KEY (manager_id) REFERENCES managers(id) ON DELETE CASCADE;
