import { expect, Locator, Page } from '@playwright/test';

export const SHORTCODE = '[laposta_signup_embed_form]';
export const API_KEY = process.env.LSE_API_KEY;
export const LIST_ID_ALWAYS = process.env.LSE_TEST_LIST_ID_EMBED_ALWAYS || process.env.WP_TEST_LIST_ID;
export const LIST_ID_INLINE = process.env.LSE_TEST_LIST_ID_EMBED_INLINE;

const BASE_URL = process.env.WP_BASE_URL || 'http://localhost:8889';
const SETTINGS_PATH = '/wp-admin/options-general.php?page=laposta_signup_embed_settings';
export const SETTINGS_URL = new URL(SETTINGS_PATH, BASE_URL).toString();

export async function getRestNonce(page: Page): Promise<string> {
  await page.goto('/wp-admin/post-new.php?post_type=page');

  const restNonce = await page.evaluate(() => {
    const win = window as typeof window & {
      wpApiSettings?: { nonce?: string };
      wp?: { apiFetch?: { nonceMiddleware?: { nonce?: string } } };
    };
    return (
      win.wpApiSettings?.nonce ||
      win.wp?.apiFetch?.nonceMiddleware?.nonce ||
      document.querySelector('meta[name="api-nonce"]')?.getAttribute('content')
    );
  });

  if (!restNonce) {
    throw new Error('Could not read wpApiSettings.nonce; ensure the test user can access wp-admin.');
  }

  return restNonce;
}

export async function createPageWithContent(page: Page, restNonce: string, title: string, content: string) {
  const response = await page.request.post('/wp-json/wp/v2/pages', {
    headers: { 'X-WP-Nonce': restNonce },
    data: { title, content, status: 'publish' },
  });

  expect(response.ok()).toBeTruthy();
  const body = await response.json();
  return {
    id: body.id as number,
    link: body.link as string,
  };
}

export async function deletePage(page: Page, restNonce: string, pageId: number) {
  await page.request.delete(`/wp-json/wp/v2/pages/${pageId}?force=true`, {
    headers: { 'X-WP-Nonce': restNonce },
  });
}

export async function dismissWelcomeGuide(page: Page) {
  await page.evaluate(() => {
    window.localStorage.setItem('wpcom_block_editor_welcome_guide_hidden', 'true');
    window.sessionStorage.setItem('wpcom_block_editor_welcome_guide_hidden', 'true');
  });

  const closeWelcome = page.getByRole('button', { name: /Close dialog/i });
  if ((await closeWelcome.count()) && (await closeWelcome.isVisible().catch(() => false))) {
    await closeWelcome.click();
  }
}

export async function createPostViaUi(page: Page, shortcode: string) {
  await page.goto('/wp-admin/post-new.php');
  await dismissWelcomeGuide(page);

  const canvas = page.frameLocator('iframe[name="editor-canvas"]');

  const title = `Laposta embed e2e ${Date.now()}`;
  const titleBox = canvas.getByRole('textbox', { name: /titel toevoegen/i });
  await expect(titleBox).toBeVisible();
  await titleBox.click();
  await titleBox.fill(title);

  await page.evaluate((sc) => {
    const wpAny = (window as typeof window & { wp: any }).wp;
    const { createBlock } = wpAny.blocks;
    const { dispatch } = wpAny.data;
    dispatch('core/block-editor').insertBlocks([createBlock('core/shortcode', { text: sc })]);
  }, shortcode);

  const content = await page.evaluate(
    () => (window as typeof window & { wp: any }).wp.data.select('core/editor').getEditedPostContent(),
  );
  expect(content).toContain(shortcode);

  await page.evaluate(() => {
    const { dispatch } = (window as typeof window & { wp: any }).wp.data;
    dispatch('core/editor').editPost({ status: 'publish' });
    dispatch('core/editor').savePost();
  });

  await expect
    .poll(
      () =>
        page.evaluate(() => {
          const sel = (window as typeof window & { wp: any }).wp.data.select('core/editor');
          return sel.isSavingPost() || sel.isPublishingPost();
        }),
      { timeout: 20000, message: 'Waiting for post publish to complete' },
    )
    .toBeFalsy();

  const { postId, permalink } = await page.evaluate(() => {
    const sel = (window as typeof window & { wp: any }).wp.data.select('core/editor');
    return { postId: sel.getCurrentPostId(), permalink: sel.getPermalink() };
  });

  const viewUrl = permalink || `${BASE_URL}/?p=${postId}`;

  const numericPostId = Number(postId);
  if (!numericPostId) {
    throw new Error('Failed to read post ID after publishing.');
  }

  return { viewUrl, postId: numericPostId };
}

export async function updateApiKey(page: Page, apiKey: string) {
  const input = page.locator('input[name="laposta-api_key"]');
  await input.fill(apiKey);
  const saveButton = page.getByRole('button', { name: /(Save Changes|Wijzigingen opslaan)/i });
  await Promise.all([
    page.waitForURL(/options-general\.php\?page=laposta_signup_embed_settings/, { timeout: 15000 }),
    saveButton.click(),
  ]);
  await page.locator('.notice-success').first().waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
}

export async function resolveListId(page: Page, preferredListId?: string) {
  await page.goto(SETTINGS_URL);
  if (API_KEY) {
    await updateApiKey(page, API_KEY);
  }
  const listLinks = page.locator('.lse-settings__lists .js-list');
  const count = await listLinks.count();
  if (!count) {
    throw new Error('No Laposta lists available for the current API key.');
  }

  const ids = await listLinks.evaluateAll((els) => els.map((el) => el.getAttribute('data-list-id')));

  if (preferredListId && ids.includes(preferredListId)) {
    return preferredListId;
  }

  const firstId = ids.find(Boolean);
  if (!firstId) {
    throw new Error('Could not read list_id from the settings page.');
  }

  return firstId;
}

export async function expectGreaterThan(locator: Locator, min: number) {
  const count = await locator.count();
  expect(count).toBeGreaterThan(min);
}

export type ShowOption = 'never' | 'always' | 'shortcode';

export async function readListsSettings(page: Page) {
  const input = page.locator('.js-lists-settings').first();
  await input.waitFor({ state: 'attached' });
  const raw = await input.inputValue();
  if (!raw) return {};
  try {
    return JSON.parse(raw) as Record<string, { listId: string; showOption: ShowOption }>;
  } catch (e) {
    return {};
  }
}

export async function setListShowOption(page: Page, listId: string, showOption: ShowOption) {
  await page.goto(SETTINGS_URL);
  if (API_KEY) {
    await updateApiKey(page, API_KEY);
  }

  const listLink = page.locator(`.js-list[data-list-id="${listId}"]`);
  await expect(listLink).toBeVisible();
  await listLink.click();

  const radio = page.locator(`.js-show-option-input[value="${showOption}"]`);
  await expect(radio).toBeVisible();
  await radio.check({ force: true });

  await expect
    .poll(
      async () => {
        const settings = await readListsSettings(page);
        return settings[listId]?.showOption || null;
      },
      { message: 'Waiting for list settings input to update' },
    )
    .toBe(showOption);

  const saveButton = page.getByRole('button', { name: /(Save Changes|Wijzigingen opslaan)/i });
  await Promise.all([
    page.waitForURL(/options-general\.php\?page=laposta_signup_embed_settings/, { timeout: 15000 }),
    saveButton.click(),
  ]);

  await expect
    .poll(
      async () => {
        const settings = await readListsSettings(page);
        return settings[listId]?.showOption || null;
      },
      { message: 'Waiting for saved list settings' },
    )
    .toBe(showOption);
}
