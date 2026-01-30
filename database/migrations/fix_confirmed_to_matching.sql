-- CONFIRMED 상태이면서 매니저가 지원한 요청을 MATCHING 상태로 변경
-- (이미 지원했지만 상태가 변경되지 않은 경우 수정)

UPDATE service_requests sr
SET sr.status = 'MATCHING'
WHERE sr.status = 'CONFIRMED'
AND EXISTS (
    SELECT 1 
    FROM applications a 
    WHERE a.request_id = sr.id
);

-- 확인 쿼리
-- SELECT sr.id, sr.status, COUNT(a.id) as application_count
-- FROM service_requests sr
-- LEFT JOIN applications a ON a.request_id = sr.id
-- WHERE sr.status = 'CONFIRMED'
-- GROUP BY sr.id, sr.status
-- HAVING application_count > 0;
