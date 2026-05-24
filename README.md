# PeakRack Email Verification Gate

PeakRack Email Verification Gate is a WHMCS 9 addon module for protecting client-area workflows until the user's email address is verified. It keeps WHMCS native Email Verification enabled, gates unverified logged-in users, and adds a controlled custom resend flow with both a verification link and a 6-digit security code.

Target runtime:

- WHMCS 9.0.3
- PHP 8.2 / 8.3
- MySQL 8.0
- Native WHMCS templates Six, Twenty-One, Nexus
- Lagom Client Theme compatible

## Features

- No WHMCS core file changes.
- No Lagom core file changes.
- Addon Module plus `hooks.php`.
- `ClientAreaPage` redirects unverified users to `index.php?m=peakrack_email_gate`.
- `ShoppingCartValidateCheckout` blocks checkout as a fallback.
- `UserAdd` initializes records without sending a custom email on first registration.
- `UserEmailVerificationComplete` clears module tokens after native WHMCS verification.
- Bilingual English and Chinese client verification page.
- Six-box security-code entry with auto-advance, paste support, AJAX auto-check, and no submit button.
- Successful code verification redirects users back to the page they originally tried to access.
- Custom resend email contains a styled verification button and a blue 6-digit security-code block.
- HMAC-only storage for custom links and security codes; no plaintext token or code is stored.
- 10-minute code lifetime and 30-minute custom link lifetime by default.
- 60-second resend cooldown and 5 resends per hour by default.
- 5 failed code attempts, then a 15-minute lock by default.
- WHMCS Local API `SendEmail` with `customsubject`, `custommessage`, and `customvars`.
- WHMCS 9 user email verification sync first, with `tblusers` field-detection fallback and legacy `tblclients.email_verified` sync where available.
- WHMCS Activity Log plus module log table.
- Admin settings, records, logs, unlock, expired-token cleanup, log-retention cleanup, and HMAC secret rotation tools.
- Standard WHMCS Smarty client-area template.

## Behavior

The first registration email remains the native WHMCS email verification email. The module only initializes its tracking record on `UserAdd`.

After login, an unverified user is redirected to the gate page. From the second and later verification attempts, the user can request a custom email containing:

- one custom verification link,
- one 6-digit security code.

Clicking the custom link or entering the 6-digit code completes verification and synchronizes the WHMCS native email verification state.

## Allowed While Unverified

The gate allows:

- the gate page,
- resend action,
- code submission action,
- logout,
- password reset,
- WHMCS native email verification callbacks,
- static assets.

Normal client-area pages, services, billing, checkout, invoice payment, tickets, and profile changes are redirected or blocked.

## Install

1. Upload `peakrack_email_gate/` to `modules/addons/peakrack_email_gate/`.
2. In WHMCS admin, go to **System Settings > Addon Modules**.
3. Activate **PeakRack Email Verification Gate**.
4. Open **Addons > PeakRack Email Verification Gate**.
5. Review rate limits, email templates, and the admin language setting.
6. Keep WHMCS native **Email Verification** enabled.

This package also includes a local WHMCS runtime copy under `modules/addons/peakrack_email_gate` for this checkout. For public release, the installable module folder is `peakrack_email_gate/`.

## Upgrade

See [UPGRADE.md](UPGRADE.md).

Short version:

1. Back up the WHMCS database.
2. Replace `modules/addons/peakrack_email_gate/` with the new `peakrack_email_gate/` directory.
3. Visit **Addons > PeakRack Email Verification Gate** once so the module runs its schema and settings checks.
4. Review the upgraded email templates.

Upgrades do not delete settings, verification records, or logs.

## Uninstall

Deactivation keeps settings, records, and logs.

The uninstall function also keeps data by default. Data is deleted only when an explicit `DELETE` confirmation is posted to the uninstall handler.

## Security Notes

- Custom link tokens and 6-digit codes are stored only as HMAC hashes.
- The HMAC secret is generated on activation and can be rotated in the admin tools tab.
- Rotating the HMAC secret invalidates outstanding custom links and codes.
- Failed code attempts and resend limits are enforced server-side.
- Return URLs are sanitized to prevent external redirects.

## Repository Metadata

Recommended GitHub description:

`WHMCS 9 email verification gate with custom resend links, six-digit codes, rate limits, and bilingual templates.`

Recommended GitHub topics:

`whmcs`, `whmcs-addon`, `email-verification`, `peakrack`, `php83`, `lagom`, `security`

Recommended release tag for this build:

`v1.1.0`

## License

MIT. See [LICENSE](LICENSE).
