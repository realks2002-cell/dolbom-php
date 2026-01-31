-- 랜덤 고객 요청 20개 생성
-- 기존 고객 사용자 ID를 사용하여 랜덤한 서비스 요청 데이터 생성

-- 1. 기존 고객 사용자 ID 가져오기 (CUSTOMER 역할)
SET @customer_ids = (
    SELECT GROUP_CONCAT(id SEPARATOR ',')
    FROM users
    WHERE role = 'CUSTOMER'
    LIMIT 10
);

-- 2. 랜덤 서비스 요청 20개 생성
INSERT INTO `service_requests` (
    `id`,
    `customer_id`,
    `service_type`,
    `service_date`,
    `start_time`,
    `duration_minutes`,
    `address`,
    `address_detail`,
    `lat`,
    `lng`,
    `details`,
    `status`,
    `estimated_price`,
    `created_at`
)
SELECT
    UUID() as id,
    (SELECT id FROM users WHERE role = 'CUSTOMER' ORDER BY RAND() LIMIT 1) as customer_id,
    ELT(FLOOR(1 + RAND() * 6), '병원 동행', '가사돌봄', '생활동행', '노인 돌봄', '아이 돌봄', '기타') as service_type,
    DATE_ADD(CURDATE(), INTERVAL FLOOR(1 + RAND() * 30) DAY) as service_date,
    SEC_TO_TIME(FLOOR(8 * 3600 + RAND() * 12 * 3600)) as start_time, -- 08:00 ~ 20:00 사이
    (FLOOR(1 + RAND() * 5) * 60) as duration_minutes, -- 60분 ~ 300분 (1시간 ~ 5시간)
    CONCAT(
        ELT(FLOOR(1 + RAND() * 8), '서울특별시 강남구', '서울특별시 서초구', '서울특별시 송파구', '경기도 성남시', '경기도 용인시', '경기도 수원시', '인천광역시', '부산광역시'),
        ' ',
        ELT(FLOOR(1 + RAND() * 5), '테헤란로', '강남대로', '역삼로', '선릉로', '삼성로'),
        ' ',
        FLOOR(100 + RAND() * 900)
    ) as address,
    CASE WHEN RAND() > 0.5 THEN CONCAT(FLOOR(100 + RAND() * 900), '호') ELSE NULL END as address_detail,
    37.4 + (RAND() * 0.5) as lat, -- 서울/경기 지역 위도 범위
    126.9 + (RAND() * 0.5) as lng, -- 서울/경기 지역 경도 범위
    CASE 
        WHEN RAND() > 0.6 THEN CONCAT('특기사항: ', ELT(FLOOR(1 + RAND() * 5), '친절하게 부탁드립니다', '시간 엄수 부탁드립니다', '안전하게 진행 부탁드립니다', '상세한 설명 부탁드립니다', '편안하게 진행 부탁드립니다'))
        ELSE NULL
    END as details,
    ELT(FLOOR(1 + RAND() * 5), 'PENDING', 'MATCHING', 'CONFIRMED', 'COMPLETED', 'CANCELLED') as status,
    (FLOOR(1 + RAND() * 5) * 20000) as estimated_price, -- 20,000원 ~ 100,000원
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY) as created_at -- 최근 30일 내 생성
FROM (
    SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
    UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
    UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
) as numbers
LIMIT 20;
