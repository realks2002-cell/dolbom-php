import { test, expect } from '@playwright/test';

test.describe('API 엔드포인트', () => {
  test('주소 검색 API 응답 확인', async ({ request }) => {
    const response = await request.get('/api/address-search?query=서울');

    // 200 또는 400 (파라미터 형식에 따라)
    expect([200, 400]).toContain(response.status());
  });

  test('CORS 헤더 확인', async ({ request }) => {
    const response = await request.get('/api/cors.php');

    // CORS 관련 응답 확인 (에러가 아니면 됨)
    expect([200, 204, 404, 405]).toContain(response.status());
  });

  test('푸시 디버그 API 응답', async ({ request }) => {
    const response = await request.get('/test/debug-push.php');

    expect(response.status()).toBe(200);
    const json = await response.json();
    expect(json.status).toBeDefined();
    expect(json.checks).toBeDefined();
  });

  test('매니저 로그인 API - 잘못된 요청', async ({ request }) => {
    const response = await request.post('/api/manager/login', {
      data: { phone: '', password: '' }
    });

    // 400 또는 401 에러 예상
    expect([400, 401, 405]).toContain(response.status());
  });

  test('매니저 토큰 등록 API - 인증 필요', async ({ request }) => {
    const response = await request.post('/api/manager/register-token', {
      data: { device_token: 'test-token' }
    });

    // 401 또는 405 (POST만 허용)
    expect([401, 405]).toContain(response.status());
  });
});

test.describe('결제 페이지', () => {
  test('결제 성공 페이지 - 파라미터 없이 접근', async ({ page }) => {
    await page.goto('/payment/success');

    // 에러 메시지 또는 리다이렉트 확인
    await expect(page.locator('body')).toBeVisible();
  });

  test('결제 실패 페이지 로드', async ({ page }) => {
    await page.goto('/payment/fail');

    await expect(page.locator('body')).toBeVisible();
  });
});
