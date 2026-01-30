import { test, expect } from '@playwright/test';

test.describe('홈페이지', () => {
  test('홈페이지 로드 확인', async ({ page }) => {
    await page.goto('/');

    // 페이지 타이틀 확인
    await expect(page).toHaveTitle(/Hangbok77/);

    // 메인 콘텐츠 확인
    await expect(page.locator('body')).toBeVisible();
  });

  test('네비게이션 메뉴 확인', async ({ page }) => {
    await page.goto('/');

    // 헤더/네비게이션 존재 확인
    const header = page.locator('header, nav').first();
    await expect(header).toBeVisible();
  });

  test('서비스 안내 페이지 이동', async ({ page }) => {
    await page.goto('/');

    // 서비스 안내 링크 클릭
    const serviceLink = page.getByRole('link', { name: /서비스|안내/i }).first();
    if (await serviceLink.isVisible()) {
      await serviceLink.click();
      await expect(page).toHaveURL(/service|guide/i);
    }
  });
});
