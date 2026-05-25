# Changelog

## 1.1.8 - 2026-05-26

- Disabled and greyed out the six verification-code inputs while a lockout is active.
- Kept the inputs disabled immediately when an AJAX code check reaches the lockout limit.
- Added explicit lockout state to the client-area template data.

## 1.1.7 - 2026-05-25

- Replaced the 1.1.6 table-based email body with a plain `div`/`p` fragment to avoid WHMCS email CSS table borders.
- Kept the email body free of custom width wrappers so it does not affect the WHMCS email header or logo area.
- Kept the compact spacing and the expiry note below the verification button.

## 1.1.6 - 2026-05-25

- Removed the custom `max-width` wrapper from the default email body so WHMCS controls the email container width.
- Rebuilt the default email body as a compact email fragment without template newlines that can render as blank rows.
- Moved the code/link expiry note below the verification button.
- Tightened the default email spacing around the greeting, security code, verification button, and ignore notice.

## 1.1.5 - 2026-05-25

- Fixed Chinese email labels wrapping vertically on the client verification page.
- Kept the resend button label as `Get Code` / `获取验证码` after cooldown ends.
- Added an admin color picker for the client `Get Code` button.
- Refined the default verification email layout with the security code above the verification link.
- Replaced the solid blue email blocks with lighter, more transactional email styling.

## 1.1.4 - 2026-05-25

- Tightened the email summary row on the client verification page so the label and address sit closer together.
- Changed the resend cooldown display to `59 Get Code` / `59 获取验证码` while keeping the button width stable.
- Fixed the logout link hover color on non-Lagom themes.
- Added a 5-second checkout fallback countdown when WHMCS cannot send the immediate redirect header.
- Preserves the checkout return target during the fallback countdown so verified clients can return to checkout with cart data intact.

## 1.1.3 - 2026-05-25

- Changed checkout enforcement from an inline WHMCS validation error to a redirect to the email verification gate.
- Stores the current checkout URL as the post-verification return target.
- Keeps the WHMCS cart session untouched so cart items and applied promotions remain in place.
- Falls back to the old validation error only if headers have already been sent and redirecting is no longer possible.

## 1.1.2 - 2026-05-24

- Reworked the addon admin header into a dark product summary panel matching the PeakRack Turnstile Manager style.
- Moved the version badge and admin language switch into the header panel.
- Rewrote the English and Chinese README files for public repository visitors.
- Removed repository-maintainer metadata suggestions from the public README.

## 1.1.1 - 2026-05-24

- Added a live resend cooldown countdown on the client verification page.
- The resend button showed only the remaining seconds during cooldown in this release; 1.1.4 changed it to include the button label.
- The resend button stays disabled until the countdown reaches zero, then automatically restores the normal resend label.
- Server-side resend cooldown enforcement remains unchanged.

## 1.1.0 - 2026-05-24

- Redesigned the default English and Chinese verification email templates.
- Added a blue button-style 6-digit security-code block to the email.
- Kept the verification link as a clear email-safe button.
- Added automatic migration from the old default email template to the 1.1.0 default template.
- Added six-box client-area code entry with auto-advance, paste support, AJAX verification, and no submit button.
- Added successful verification redirect back to the originally requested client-area page.
- Changed the first resend button label to `Get Code` / `获取验证码`, then `Get New Code` / `重新获取验证码`.
- Refined client-facing email verification notice text.
- Avoided browser-native pattern validation errors on code entry.
- Added safer SendEmail failure rollback so failed email sends do not consume a resend or replace a previously active code.
- Improved legacy `tblclients.email_verified` fallback by checking for `updated_at` before writing it.
- Expanded English and Chinese README, upgrade notes, and release notes.

## 1.0.0 - 2026-05-24

- Initial release for WHMCS 9.0.3 / PHP 8.2-8.3.
- Added client-area email verification gate.
- Added custom resend email with HMAC-stored link token and 6-digit code.
- Added resend rate limits, code lockout, native WHMCS verification sync, admin logs, unlock, and cleanup tools.
