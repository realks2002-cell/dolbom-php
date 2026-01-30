import { test, expect } from '@playwright/test';

test.describe('사용자 인증', () => {
  test('로그인 페이지 로드', async ({ page }) => {
    await page.goto('/auth/login');

    // 로그인 폼 확인
    await expect(page.locator('form')).toBeVisible();
    await expect(page.locator('input[type="email"], input[type="text"], input[name*="email"], input[name*="phone"]').first()).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
  });

  test('회원가입 페이지 로드', async ({ page }) => {
    await page.goto('/auth/signup');

    // 회원가입 폼 확인
    await expect(page.locator('form')).toBeVisible();
  });

  test('빈 로그인 폼 제출 시 에러', async ({ page }) => {
    await page.goto('/auth/login');

    // 빈 폼 제출
    const submitBtn = page.locator('button[type="submit"], input[type="submit"]').first();
    await submitBtn.click();

    // HTML5 validation 또는 에러 메시지 확인
    const emailInput = page.locator('input[type="email"], input[type="text"], input[name*="email"], input[name*="phone"]').first();
    const isInvalid = await emailInput.evaluate((el: HTMLInputElement) => !el.validity.valid);
    expect(isInvalid).toBeTruthy();
  });
});

test.describe('매니저 인증', () => {
  test('매니저 로그인 페이지 로드', async ({ page }) => {
    await page.goto('/manager/login');

    // 로그인 폼 확인
    await expect(page.locator('form')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
  });

  test('매니저 로그인 실패 - 잘못된 정보', async ({ page }) => {
    await page.goto('/manager/login');

    // 잘못된 정보 입력
    await page.fill('input[type="text"], input[name*="phone"]', '01012345678');
    await page.fill('input[type="password"]', 'wrongpassword');

    // 제출
    await page.click('button[type="submit"], input[type="submit"]');

    // 에러 메시지 또는 여전히 로그인 페이지에 있는지 확인
    await expect(page).toHaveURL(/login|error/i);
  });

  test('비로그인 시 대시보드 접근 차단', async ({ page }) => {
    await page.goto('/manager/dashboard');

    // 로그인 페이지로 리다이렉트 확인
    await expect(page).toHaveURL(/login/i);
  });
});
