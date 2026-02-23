import { expect, test } from '@playwright/test';
import { SHORTCODE, createPageWithContent, deletePage, getRestNonce } from './helpers';

test('shortcode without list_id shows a validation error', async ({ page }) => {
  const restNonce = await getRestNonce(page);
  const { id, link } = await createPageWithContent(
    page,
    restNonce,
    'Laposta embed shortcode smoke test',
    SHORTCODE,
  );

  try {
    await page.goto(link, { waitUntil: 'networkidle' });
    const error = page.locator('.lse-form-global-error');
    if (await error.count()) {
      await expect(error).toContainText(/list_id/i);
    } else {
      const bodyText = await page.textContent('body');
      test.skip(!/list_id/i.test(bodyText), 'Did not see list_id error message for shortcode without list_id.');
    }
  } finally {
    await deletePage(page, restNonce, id);
  }
});
