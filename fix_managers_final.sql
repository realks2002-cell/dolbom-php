-- Step 1: FK 제거
ALTER TABLE manager_device_tokens DROP FOREIGN KEY fk_manager_device_tokens_manager;

-- Step 2: manager_device_tokens.manager_id 타입 변경
ALTER TABLE manager_device_tokens MODIFY COLUMN manager_id CHAR(36) NOT NULL;

-- Step 3: managers.id 타입 변경
ALTER TABLE managers MODIFY COLUMN id CHAR(36) NOT NULL;

-- Step 4: FK 재추가
ALTER TABLE manager_device_tokens ADD CONSTRAINT fk_manager_device_tokens_manager FOREIGN KEY (manager_id) REFERENCES managers(id) ON DELETE CASCADE;

-- Step 5: 기타 필수 컬럼 NULL 허용
ALTER TABLE managers MODIFY COLUMN account_number VARCHAR(50) NULL DEFAULT NULL;

ALTER TABLE managers MODIFY COLUMN password_hash VARCHAR(255) NULL DEFAULT NULL;
