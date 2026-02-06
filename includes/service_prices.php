<?php
/**
 * 서비스 가격 관련 헬퍼 함수
 *
 * DB 조회 실패 시 예외를 던지거나 false를 반환합니다.
 * 호출하는 곳에서 적절한 오류 처리를 해야 합니다.
 */

/**
 * 모든 서비스 가격 조회
 * @param PDO $pdo
 * @return array|false [service_type => price_per_hour, ...] 또는 실패 시 false
 */
function get_all_service_prices(PDO $pdo) {
    try {
        $stmt = $pdo->query('SELECT service_type, price_per_hour FROM service_prices WHERE is_active = 1');
        if (!$stmt) {
            return false;
        }

        $prices = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $prices[$row['service_type']] = (int) $row['price_per_hour'];
        }

        // 가격 데이터가 하나도 없으면 실패로 처리
        if (empty($prices)) {
            return false;
        }

        return $prices;
    } catch (PDOException $e) {
        error_log('get_all_service_prices error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 특정 서비스 가격 조회
 * @param PDO $pdo
 * @param string $serviceType
 * @return int|false 시간당 가격 또는 실패 시 false
 */
function get_service_price(PDO $pdo, string $serviceType) {
    try {
        $stmt = $pdo->prepare('SELECT price_per_hour FROM service_prices WHERE service_type = ? AND is_active = 1');
        if (!$stmt->execute([$serviceType])) {
            return false;
        }

        $result = $stmt->fetchColumn();

        // 해당 서비스 타입의 가격이 없으면 실패
        if ($result === false) {
            error_log('Service price not found for type: ' . $serviceType);
            return false;
        }

        return (int) $result;
    } catch (PDOException $e) {
        error_log('get_service_price error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 서비스 가격 업데이트 (관리자용)
 * @param PDO $pdo
 * @param string $serviceType
 * @param int $pricePerHour
 * @return bool 성공 여부
 */
function update_service_price(PDO $pdo, string $serviceType, int $pricePerHour): bool {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO service_prices (service_type, price_per_hour)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE price_per_hour = ?, updated_at = NOW()
        ');
        return $stmt->execute([$serviceType, $pricePerHour, $pricePerHour]);
    } catch (PDOException $e) {
        error_log('update_service_price error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 모든 서비스 가격 조회 (관리자용 - 상세 정보 포함)
 * @param PDO $pdo
 * @return array|false 전체 레코드 배열 또는 실패 시 false
 */
function get_all_service_prices_detailed(PDO $pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM service_prices ORDER BY price_per_hour DESC');
        if (!$stmt) {
            return false;
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('get_all_service_prices_detailed error: ' . $e->getMessage());
        return false;
    }
}
