import { test, expect } from '@playwright/test';

test.describe('서비스 요청', () => {
  test('서비스 요청 페이지 로드', async ({ page }) => {
    await page.goto('/requests/new');

    // 로그인 리다이렉트 또는 폼 표시
    const isLoginPage = page.url().includes('login');
    if (isLoginPage) {
      // 비로그인 시 로그인 페이지로 리다이렉트 확인
      await expect(page).toHaveURL(/login/i);
    } else {
      // 로그인 상태면 폼 확인
      await expect(page.locator('form')).toBeVisible();
    }
  });

  test('서비스 안내 페이지 로드', async ({ page }) => {
    await page.goto('/service-guide');

    // 페이지 콘텐츠 확인
    await expect(page.locator('body')).toBeVisible();
    await expect(page.locator('h1, h2').first()).toBeVisible();
  });

  test('FAQ 페이지 로드', async ({ page }) => {
    await page.goto('/faq');

    // FAQ 콘텐츠 확인
    await expect(page.locator('body')).toBeVisible();
  });

  test('회사 소개 페이지 로드', async ({ page }) => {
    await page.goto('/about');

    // 페이지 콘텐츠 확인
    await expect(page.locator('body')).toBeVisible();
  });
});

test.describe('예약 관리', () => {
  test('예약 목록 페이지 - 비로그인 시 리다이렉트', async ({ page }) => {
    await page.goto('/bookings');

    // 로그인 페이지로 리다이렉트 확인
    await expect(page).toHaveURL(/login/i);
  });
});
