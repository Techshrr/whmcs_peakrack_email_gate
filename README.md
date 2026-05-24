# PeakRack Email Verification Gate

PeakRack Email Verification Gate is a WHMCS email-verification addon that keeps the native WHMCS verification flow in place while giving unverified clients a clearer gate page, custom resend emails, six-digit code entry, and administrator audit tools.

It is designed for WHMCS sites that want clients to verify their email address before using the full client area, placing orders, paying invoices, or opening tickets.

## Highlights

- Detects unverified logged-in clients and guides them to a dedicated verification page.
- Leaves the first registration verification email to WHMCS native email verification.
- Sends a custom verification email only when the client requests a new code from the gate page.
- Includes both a verification button and a six-digit security code in the custom email.
- Provides six-box code entry with auto-advance, paste support, and automatic verification.
- Shows a plain numeric resend countdown during the 60-second cooldown.
- Returns the client to the page they originally tried to access after successful verification.
- Supports English and Chinese client text, email subjects, email bodies, and admin UI.
- Provides configurable code lifetime, link lifetime, hourly resend limit, failed-attempt lockout, and log retention.
- Includes admin records, logs, unlock tools, expired-token cleanup, and HMAC secret rotation.
- Can mirror key events to the WHMCS Activity Log.

## Security

- Custom verification link tokens and six-digit codes are stored only as HMAC hashes.
- Plaintext tokens and codes are never written to the module tables.
- Failed code attempts trigger a temporary lockout after the configured limit.
- Resend limits are enforced server-side and shown client-side with a countdown.
- Return URLs are sanitized before redirecting the client after verification.
- Rotating the HMAC secret invalidates outstanding custom links and codes.

## Compatibility

- WHMCS 9.0.3
- PHP 8.2 / 8.3
- MySQL 8.0
- WHMCS Nexus, Six, and Twenty-One templates
- Lagom Client Theme

## Installation

1. Upload `peakrack_email_gate/` to `modules/addons/peakrack_email_gate/`.
2. In WHMCS admin, open **System Settings > Addon Modules**.
3. Activate **PeakRack Email Verification Gate**.
4. Open **Addons > PeakRack Email Verification Gate**.
5. Review the module switches, rate limits, email templates, and admin language.
6. Keep WHMCS native **Email Verification** enabled.

## Client Flow

When the module is enabled, unverified clients are redirected to the gate page after login. They can use the native WHMCS verification email first, or request a new custom email from the gate page.

The custom email supports two verification methods:

- click the verification button,
- enter the six-digit security code on the gate page.

Either method completes verification, synchronizes the WHMCS email verification status, and sends the client back to the originally requested page.

## Admin Area

The addon admin area includes four sections:

- **Settings**: module switches, gate enforcement, checkout blocking, lifetimes, limits, and email templates.
- **Records**: client verification status, send count, failed attempts, and lock state.
- **Logs**: module event history.
- **Tools**: unlock client records, clean expired tokens, apply log retention, and rotate the HMAC secret.

## Upgrade

See [UPGRADE.md](UPGRADE.md).

Back up the WHMCS database before upgrading. Upgrades do not delete settings, verification records, or logs.

## Uninstall

Deactivation keeps settings, verification records, and logs. The uninstall function also keeps data by default; module data is removed only after an explicit delete confirmation.

## License

MIT. See [LICENSE](LICENSE).
