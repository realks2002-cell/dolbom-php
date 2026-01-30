-- applications 테이블을 managers 테이블과 호환되도록 수정
-- manager_id를 INT로 변경하고 managers.id를 참조하도록 변경

-- 기존 외래키 제약조건 제거
ALTER TABLE `applications` 
DROP FOREIGN KEY `fk_applications_manager`;

-- manager_id 컬럼 타입 변경 (CHAR(36) -> INT UNSIGNED)
ALTER TABLE `applications` 
MODIFY COLUMN `manager_id` INT(10) UNSIGNED NOT NULL;

-- 새로운 외래키 제약조건 추가 (managers 테이블 참조)
ALTER TABLE `applications` 
ADD CONSTRAINT `fk_applications_manager_new` 
FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`) ON DELETE CASCADE;
