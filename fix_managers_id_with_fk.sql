-- managers.id를 CHAR(36)으로 변경 (FK 제약조건 처리)
-- phpMyAdmin에서 한 줄씩 실행하세요

-- 1. applications 테이블의 FK 제거
ALTER TABLE applications DROP FOREIGN KEY fk_applications_manager;

-- 2. bookings 테이블의 FK 제거 (있는 경우)
ALTER TABLE bookings DROP FOREIGN KEY fk_bookings_manager;

-- 3. applications.manager_id 타입 변경
ALTER TABLE applications MODIFY COLUMN manager_id CHAR(36) NULL;

-- 4. bookings.manager_id 타입 변경
ALTER TABLE bookings MODIFY COLUMN manager_id CHAR(36) NULL;

-- 5. managers.id 타입 변경
ALTER TABLE managers MODIFY COLUMN id CHAR(36) NOT NULL;

-- 6. FK 제약조건 재추가
ALTER TABLE applications ADD CONSTRAINT fk_applications_manager FOREIGN KEY (manager_id) REFERENCES managers(id) ON DELETE CASCADE;

ALTER TABLE bookings ADD CONSTRAINT fk_bookings_manager FOREIGN KEY (manager_id) REFERENCES managers(id) ON DELETE SET NULL;
