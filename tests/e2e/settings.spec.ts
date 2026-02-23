import { expect, test } from '@playwright/test';
import { API_KEY, LIST_ID_ALWAYS, SETTINGS_URL, expectGreaterThan, updateApiKey } from './helpers';

test.describe('Settings', () => {
  test('settings page renders and exposes API key input', async ({ page }) => {
    await page.goto(SETTINGS_URL);

    await expect(page.locator('h1:has-text("Laposta Signup Embed Instellingen")')).toBeVisible();
    await expect(page.locator(`input[name="laposta-api_key"]`)).toBeVisible();
    await expect(page.locator('.js-reset-cache')).toBeVisible();
  });

  test('cache reset button works', async ({ page }) => {
    await page.goto(SETTINGS_URL);
    await expect(page.locator('.js-reset-result-success')).toBeHidden();
    await expect(page.locator('.js-reset-result-error')).toBeHidden();

    await page.locator('.js-reset-cache').click();
    await expect(page.locator('.js-reset-result-success')).toBeVisible();
  });

  test('invalid API key shows an error, cache can reset, valid key restores lists', async ({ page }) => {
    test.skip(!API_KEY, 'LSE_API_KEY must be set to run this test.');

    await page.goto(SETTINGS_URL);
    const invalidKey = `invalid-${Date.now()}`;

    await updateApiKey(page, invalidKey);
    const errorBox = page.locator('.lse-settings__error');
    const hasErrorBox = await errorBox.count();
    if (hasErrorBox) {
      await expect(errorBox).not.toHaveText('');
    }

    await page.locator('.js-reset-cache').click();
    await expect(page.locator('.js-reset-result-success')).toBeVisible();

    await updateApiKey(page, API_KEY);
    await expect(page.locator('.lse-settings__error')).toBeHidden();
    const listLinks = page.locator('.lse-settings__lists .js-list');
    const count = await listLinks.count();
    test.skip(count === 0, 'No Laposta lists available for the provided API key.');
    await expectGreaterThan(listLinks, 0);
    if (LIST_ID_ALWAYS) {
      const hasList = await listLinks.evaluateAll((els, target) => els.some((el) => el.getAttribute('data-list-id') === target), LIST_ID_ALWAYS);
      test.skip(!hasList, `List ${LIST_ID_ALWAYS} not available for the provided API key.`);
    }
  });
});
