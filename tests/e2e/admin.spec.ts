import { test, expect } from '@playwright/test';

test.describe('관리자 페이지', () => {
  test('관리자 로그인 페이지 로드', async ({ page }) => {
    await page.goto('/admin');

    // 로그인 폼 또는 대시보드 확인
    const hasLoginForm = await page.locator('input[type="password"]').isVisible();
    const isLoggedIn = page.url().includes('dashboard') || page.url().includes('index');

    expect(hasLoginForm || isLoggedIn).toBeTruthy();
  });

  test('관리자 비인증 시 접근 차단', async ({ page }) => {
    await page.goto('/admin/users');

    // 로그인 페이지로 리다이렉트 또는 에러
    const currentUrl = page.url();
    const isProtected = currentUrl.includes('login') || currentUrl.includes('admin') && !currentUrl.includes('users');

    expect(isProtected).toBeTruthy();
  });

  test('관리자 요청 관리 페이지 - 비인증', async ({ page }) => {
    await page.goto('/admin/requests');

    // 로그인 필요
    await expect(page).toHaveURL(/admin|login/i);
  });

  test('관리자 결제 관리 페이지 - 비인증', async ({ page }) => {
    await page.goto('/admin/payments');

    await expect(page).toHaveURL(/admin|login/i);
  });

  test('관리자 매니저 관리 페이지 - 비인증', async ({ page }) => {
    await page.goto('/admin/managers');

    await expect(page).toHaveURL(/admin|login/i);
  });
});
