# Changelog

All notable changes to this project are documented in this file.

This project follows Semantic Versioning where practical.

## [1.1.9] - 2026-05-26

### Fixed

- Clarified the client lockout message after too many incorrect code attempts.
- Added a client-side lockout timer that releases the code inputs when the lock expires.
- Returned lockout wait time from both active-lock and newly-triggered-lock AJAX responses.

## [1.1.7] - 2026-05-25

### Changed

- Rebuilt the default verification email as a compact `div` and `p` fragment to avoid unwanted WHMCS email table borders.
- Kept email expiry notes below the verification button.

## [1.1.5] - 2026-05-25

### Added

- Added an admin color picker for the client `Get Code` button.

### Fixed

- Fixed Chinese email labels wrapping vertically on the client verification page.
- Kept the resend button label stable after cooldown ends.

## [1.1.3] - 2026-05-25

### Changed

- Changed checkout enforcement from an inline validation error to a redirect to the email verification gate.
- Stored the checkout return URL so verified clients can return to checkout with the same session.

## [1.1.0] - 2026-05-24

### Added

- Added six-box client-area code entry with auto-advance, paste support, AJAX verification, and post-verification redirect.
- Added default English and Chinese verification email templates with a six-digit security code and verification link.
- Added safer SendEmail rollback when custom email delivery fails.

## [1.0.0] - 2026-05-24

### Added

- Initial release for WHMCS 9.0.3 and PHP 8.2-8.3.
- Added client-area email verification gate, custom resend email, HMAC-stored token/code checks, rate limits, lockout, native WHMCS verification sync, admin logs, unlock, and cleanup tools.