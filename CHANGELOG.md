# Changelog

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
- Expanded English and Chinese README, upgrade notes, release notes, and recommended GitHub metadata.

## 1.0.0 - 2026-05-24

- Initial release for WHMCS 9.0.3 / PHP 8.2-8.3.
- Added client-area email verification gate.
- Added custom resend email with HMAC-stored link token and 6-digit code.
- Added resend rate limits, code lockout, native WHMCS verification sync, admin logs, unlock, and cleanup tools.
