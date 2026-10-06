import { createRequire } from 'node:module';
import assert from 'node:assert/strict';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.VISTIQ_PLAYWRIGHT_MODULE ?? 'playwright');
const browser = await chromium.launch({ headless: true, args: ['--no-proxy-server'] });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
await context.route('**/*', route => ['localhost', '127.0.0.1'].includes(new URL(route.request().url()).hostname) ? route.continue() : route.abort());
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
try {
    await page.goto('http://127.0.0.1:9001/login', { waitUntil: 'load', timeout: 60000 });
    assert.ok((await page.locator('#admin-email').boundingBox()).width > 300);
    await page.getByRole('button', { name: 'Hiện mật khẩu' }).click();
    assert.equal(await page.locator('#admin-password').getAttribute('type'), 'text');
    await page.getByRole('button', { name: 'Ẩn mật khẩu' }).click();
    assert.equal(await page.locator('#admin-password').getAttribute('type'), 'password');
    await page.screenshot({ path: 'storage/browser-admin-login-desktop.png', fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
    await page.screenshot({ path: 'storage/browser-admin-login-mobile.png', fullPage: true });
    await page.locator('#admin-email').fill('unknown@acceptance.invalid');
    await page.locator('#admin-password').fill('Incorrect-password-2026!');
    await page.getByRole('button', { name: 'Đăng nhập', exact: true }).click();
    await page.locator('#admin-email-error').waitFor();
    assert.equal(await page.locator('#admin-email').getAttribute('aria-invalid'), 'true');
    assert.equal(await page.locator('#admin-password').inputValue(), '');
    assert.deepEqual(errors, []);
    console.log('Admin login passed: desktop/mobile layout, password visibility, validation feedback, no browser exceptions.');
} finally {
    await browser.close();
}
