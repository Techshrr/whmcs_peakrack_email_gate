# Upgrade Notes

## Upgrade to 1.1.3

1. Back up the WHMCS database.
2. Replace `modules/addons/peakrack_email_gate/` with the new `peakrack_email_gate/` directory.
3. Test checkout with an unverified client:
   - add a product to the cart,
   - apply a promotion code if needed,
   - continue to checkout,
   - confirm the client is redirected to the verification gate,
   - complete email verification,
   - confirm the client returns to checkout with cart contents and promotion still present.

No database migration is required from 1.1.2 to 1.1.3.

## Upgrade to 1.1.2

1. Back up the WHMCS database.
2. Replace `modules/addons/peakrack_email_gate/` with the new `peakrack_email_gate/` directory.
3. Open **Addons > PeakRack Email Verification Gate** and confirm the new dark admin header is displayed.
4. Clear the WHMCS template cache if the admin page still shows the previous header layout.

No database migration is required from 1.1.1 to 1.1.2.

## Upgrade to 1.1.1

1. Back up the WHMCS database.
2. Replace `modules/addons/peakrack_email_gate/` with the new `peakrack_email_gate/` directory.
3. Clear WHMCS template cache if the client-area page still shows the old button text.
4. Request a verification email and confirm the resend button counts down as `60`, `59`, `58` before it can be clicked again.

No database migration is required from 1.1.0 to 1.1.1.

## Upgrade to 1.1.0

1. Back up the WHMCS database.
2. Replace `modules/addons/peakrack_email_gate/` with the new `peakrack_email_gate/` directory.
3. Visit **Addons > PeakRack Email Verification Gate** once so the module runs schema and settings checks.
4. Review the English and Chinese email templates.
5. Send one test verification email and confirm:
   - the verification link opens correctly,
   - the blue 6-digit code block renders correctly,
   - code entry verifies automatically,
   - successful verification redirects back to the intended client-area page.

Upgrade behavior:

- Settings are kept.
- Verification records are kept.
- Logs are kept.
- Existing old default email templates are automatically replaced with the 1.1.0 default templates.
- Custom email templates that do not look like the old module defaults are kept.

## Upgrade From 1.0.0

Version 1.1.0 adds the new six-box code UI, AJAX auto-verification, return-to-original-page redirect, refreshed HTML email templates, and release documentation.

No manual database migration is required.

## Rollback

If you need to roll back:

1. Replace `modules/addons/peakrack_email_gate/` with the previous version.
2. Open **Addons > PeakRack Email Verification Gate**.
3. Review the email templates before sending more verification emails.

Rollback does not automatically restore old email template text if the 1.1.0 defaults have already been saved.
