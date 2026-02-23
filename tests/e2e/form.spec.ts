import { expect, test } from '@playwright/test';
import {
  API_KEY,
  LIST_ID_ALWAYS,
  LIST_ID_INLINE,
  SETTINGS_URL,
  createPageWithContent,
  deletePage,
  expectGreaterThan,
  getRestNonce,
  readListsSettings,
  setListShowOption,
} from './helpers';

test.describe('Embed visibility', () => {
  test('show option "always" loads embed on pages without a shortcode', async ({ page }) => {
    test.skip(!API_KEY || !LIST_ID_ALWAYS, 'LSE_API_KEY and LSE_TEST_LIST_ID_EMBED_ALWAYS must be set to run this test.');

    await page.goto(SETTINGS_URL);
    const originalSettings = await readListsSettings(page);
    const previousOption = originalSettings[LIST_ID_ALWAYS]?.showOption;

    const restNonce = await getRestNonce(page);
    const { id: pageId, link } = await createPageWithContent(
      page,
      restNonce,
      'Laposta embed always setting check',
      'Page without shortcode',
    );

    try {
      await setListShowOption(page, LIST_ID_ALWAYS, 'always');
      await page.goto(link, { waitUntil: 'networkidle' });
      const embedScript = page.locator(`script[src*="${LIST_ID_ALWAYS}.js"]`);
      await expectGreaterThan(embedScript, 0);
    } finally {
      if (previousOption) {
        await setListShowOption(page, LIST_ID_ALWAYS, previousOption);
      } else {
        await setListShowOption(page, LIST_ID_ALWAYS, 'never');
      }
      await deletePage(page, restNonce, pageId);
    }
  });

  test('show option "shortcode" only loads embed where shortcode is present', async ({ page }) => {
    test.skip(!API_KEY || !LIST_ID_INLINE, 'LSE_API_KEY and LSE_TEST_LIST_ID_EMBED_INLINE must be set to run this test.');

    await page.goto(SETTINGS_URL);
    const originalSettings = await readListsSettings(page);
    const previousOption = originalSettings[LIST_ID_INLINE]?.showOption;

    const restNonce = await getRestNonce(page);
    const shortcode = `[laposta_signup_embed_form list_id="${LIST_ID_INLINE}"]`;
    const { id: withShortcodeId, link: withShortcodeLink } = await createPageWithContent(
      page,
      restNonce,
      'Laposta embed shortcode page',
      shortcode,
    );
    const { id: withoutShortcodeId, link: withoutShortcodeLink } = await createPageWithContent(
      page,
      restNonce,
      'Laposta embed no shortcode page',
      'Just some content without shortcode',
    );

    try {
      await setListShowOption(page, LIST_ID_INLINE, 'shortcode');

      await page.goto(withShortcodeLink, { waitUntil: 'networkidle' });
      const embedScript = page.locator(`script[src*="${LIST_ID_INLINE}.js"]`);
      await expectGreaterThan(embedScript, 0);

      await page.goto(withoutShortcodeLink, { waitUntil: 'networkidle' });
      await expect(embedScript).toHaveCount(0);
    } finally {
      if (previousOption) {
        await setListShowOption(page, LIST_ID_INLINE, previousOption);
      } else {
        await setListShowOption(page, LIST_ID_INLINE, 'never');
      }
      await deletePage(page, restNonce, withShortcodeId);
      await deletePage(page, restNonce, withoutShortcodeId);
    }
  });

  test('show option "never" hides embed even when shortcode is used', async ({ page }) => {
    test.skip(!API_KEY || !LIST_ID_INLINE, 'LSE_API_KEY and LSE_TEST_LIST_ID_EMBED_INLINE must be set to run this test.');

    await page.goto(SETTINGS_URL);
    const originalSettings = await readListsSettings(page);
    const previousOption = originalSettings[LIST_ID_INLINE]?.showOption;

    const restNonce = await getRestNonce(page);
    const shortcode = `[laposta_signup_embed_form list_id="${LIST_ID_INLINE}"]`;
    const { id: pageId, link } = await createPageWithContent(
      page,
      restNonce,
      'Laposta embed never setting check',
      shortcode,
    );

    try {
      await setListShowOption(page, LIST_ID_INLINE, 'never');
      await page.goto(link, { waitUntil: 'networkidle' });
      const embedScript = page.locator(`script[src*="${LIST_ID_INLINE}.js"]`);
      await expect(embedScript).toHaveCount(0);
    } finally {
      if (previousOption) {
        await setListShowOption(page, LIST_ID_INLINE, previousOption);
      } else {
        await setListShowOption(page, LIST_ID_INLINE, 'never');
      }
      await deletePage(page, restNonce, pageId);
    }
  });
});
