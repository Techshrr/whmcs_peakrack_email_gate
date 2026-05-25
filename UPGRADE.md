# Upgrade Guide

This guide explains how to upgrade this module from an older version.

## Before upgrading

1. Back up the WHMCS files.
2. Back up the WHMCS database.
3. Make a copy of `modules/addons/peakrack_email_gate/`.
4. Review [CHANGELOG.md](CHANGELOG.md).
5. Check whether the upgrade includes database changes.

## Upgrade steps

1. Download the latest release from the official repository:

   https://github.com/Techshrr/whmcs_peakrack_email_gate

2. Replace the addon files in:

   `modules/addons/peakrack_email_gate/`

3. Keep existing WHMCS module settings and local templates unless the release notes require changes.
4. Log in to the WHMCS admin area.
5. Open **Addons > PeakRack Email Verification Gate** and verify all options.
6. Clear the WHMCS template cache if the client-area output does not update.

## Database migrations

This version does not require manual database migration.

The addon creates or updates its module tables during activation, upgrade, admin access, and selected runtime flows.

## Version-specific notes

### Upgrade from 1.0.x to 1.1.x

- No breaking changes.
- New settings use built-in defaults.
- Existing settings, verification records, and logs are preserved.
- Outstanding custom links and codes remain valid unless the HMAC secret is rotated.

## Rollback

To roll back:

1. Restore the previous `modules/addons/peakrack_email_gate/` directory.
2. Restore the database backup if the upgrade changed module tables.
3. Clear the WHMCS template cache.
4. Check the WHMCS activity log and module logs for errors.

## Notes

Do not overwrite production credentials, local configuration files, custom templates, callback secrets, or payment credentials unless the upgrade notes explicitly require it.