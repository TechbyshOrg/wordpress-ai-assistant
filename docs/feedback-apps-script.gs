/** @OnlyCurrentDoc */

/**
 * Google Apps Script for the plugin's deactivation feedback survey (hardened version).
 * Not part of the plugin package (docs/ is excluded via .distignore).
 *
 * The @OnlyCurrentDoc line above limits this script to the spreadsheet it is attached to,
 * instead of every spreadsheet in your Google account.
 *
 * Setup: Google Sheet -> Extensions -> Apps Script -> paste this -> Save ->
 * Deploy -> New deployment -> Web app (Execute as: Me, Who has access: Anyone).
 * Put the resulting /exec URL in includes/class-wacdmg-deactivation-feedback.php (ENDPOINT_URL).
 *
 * Updating an existing deployment (keeps the same URL):
 * Deploy -> Manage deployments -> Edit (pencil) -> Version: New version -> Deploy.
 *
 * Deployed web app (2026-11-13):
 * https://script.google.com/macros/s/AKfycbxH4AIQgQpxVqcGsIutXYaWL5Nx-Wg5AVsaLbfo2_I5MhWrjUzrK9V0Rjam5M077X7b/exec
 *
 * SECRET must match SECRET_TOKEN in includes/class-wacdmg-deactivation-feedback.php.
 */

const SECRET = 'RObugXJsyrGIlwX4UEpXS591iPqcrkNftrZZBoWn';
const SHEET_NAME = 'Feedback';
const HEADERS = ['Timestamp', 'Plugin', 'Plugin Version', 'Reason', 'Details', 'WP Version', 'PHP Version', 'Locale', 'Multisite', 'Active Theme', 'Site URL', 'Admin Email'];

// Only these plugin slugs are accepted. 'wordpress-ai-assistant' is the local development folder name.
const ALLOWED_PLUGINS = ['wacdmg-ai-content-assistant', 'wordpress-ai-assistant'];
const ALLOWED_REASONS = ['no_longer_needed', 'better_plugin', 'not_working', 'broke_site', 'missing_feature', 'too_complicated', 'temporary', 'other'];

// Spam limits (shared across all senders).
const MAX_ROWS_PER_MINUTE = 20;
const MAX_ROWS_PER_HOUR = 200;
const MAX_TOTAL_ROWS = 50000;

function doPost(e) {
  const p = (e && e.parameter) || {};

  if (p.token !== SECRET) {
    return json({ ok: false, error: 'unauthorized' });
  }

  const row = validate(p);
  if (!row) {
    return json({ ok: false, error: 'invalid' });
  }

  const lock = LockService.getScriptLock();
  if (!lock.tryLock(10000)) {
    return json({ ok: false, error: 'busy' });
  }
  try {
    if (!withinRateLimit()) {
      return json({ ok: false, error: 'rate_limited' });
    }

    const ss = SpreadsheetApp.getActiveSpreadsheet();
    let sheet = ss.getSheetByName(SHEET_NAME);
    if (!sheet) {
      sheet = ss.insertSheet(SHEET_NAME);
    }
    if (sheet.getLastRow() === 0) {
      sheet.appendRow(HEADERS);
      sheet.setFrozenRows(1);
    }
    if (sheet.getLastRow() >= MAX_TOTAL_ROWS) {
      return json({ ok: false, error: 'full' });
    }

    sheet.appendRow([new Date()].concat(row));
  } finally {
    lock.releaseLock();
  }
  return json({ ok: true });
}

function doGet() {
  return json({ ok: true, message: 'Feedback endpoint is running.' });
}

/**
 * Check the fields. Returns the row values (without timestamp), or null to reject.
 *
 * Only an unknown plugin or reason rejects the submission. Any other malformed field is
 * left blank, so real feedback from unusual hosts (odd PHP version strings, etc.) is kept.
 */
function validate(p) {
  const plugin = str(p.plugin, 100);
  const reason = str(p.reason, 50);
  if (ALLOWED_PLUGINS.indexOf(plugin) === -1 || ALLOWED_REASONS.indexOf(reason) === -1) {
    return null;
  }

  const version = /^[0-9A-Za-z.\-+~_]*$/;     // e.g. 2.1.1, 7.2-beta1, 8.3.6-1+ubuntu22.04.1
  const locale = /^[A-Za-z0-9_@\-]*$/;         // e.g. en_US, es_419, pt_PT_ao90, de_DE_formal
  const url = /^https?:\/\/[^\s]+$/i;
  const email = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  return [
    clean(plugin),
    clean(match(str(p.plugin_version, 20), version)),
    clean(reason),
    clean(str(p.details, 1000)),
    clean(match(str(p.wp_version, 40), version)),
    clean(match(str(p.php_version, 60), version)),
    clean(match(str(p.locale, 20), locale)),
    clean(['yes', 'no'].indexOf(str(p.multisite, 3)) === -1 ? '' : str(p.multisite, 3)),
    clean(str(p.active_theme, 100)),
    clean(match(str(p.site_url, 200), url)),
    clean(match(str(p.admin_email, 200), email))
  ];
}

/** The value if it matches the pattern, otherwise an empty string. */
function match(v, pattern) {
  return pattern.test(v) ? v : '';
}

/** Trimmed string, cut to max length. */
function str(v, max) {
  return String(v === undefined || v === null ? '' : v).trim().substring(0, max);
}

/**
 * Stop spreadsheet formula injection: values starting with = + - @ (or a tab / carriage return)
 * are stored as plain text.
 */
function clean(v) {
  return String(v).replace(/^[=+\-@\t\r]/, "'$&");
}

/**
 * Global per-minute and per-hour caps. Called while holding the script lock, so counts are exact.
 */
function withinRateLimit() {
  const cache = CacheService.getScriptCache();
  const now = new Date();
  const minuteKey = 'm' + Math.floor(now.getTime() / 60000);
  const hourKey = 'h' + Math.floor(now.getTime() / 3600000);
  const perMinute = parseInt(cache.get(minuteKey) || '0', 10);
  const perHour = parseInt(cache.get(hourKey) || '0', 10);
  if (perMinute >= MAX_ROWS_PER_MINUTE || perHour >= MAX_ROWS_PER_HOUR) {
    return false;
  }
  cache.put(minuteKey, String(perMinute + 1), 120);
  cache.put(hourKey, String(perHour + 1), 3700);
  return true;
}

function json(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}
