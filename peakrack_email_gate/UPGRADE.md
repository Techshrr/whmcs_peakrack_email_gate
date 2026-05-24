# Module Upgrade Notes

1. Back up the WHMCS database.
2. Replace the existing `modules/addons/peakrack_email_gate/` directory with this directory.
3. Open **Addons > PeakRack Email Verification Gate** once.
4. Review email templates and rate-limit settings.

Version 1.1.6 keeps settings, records, logs, and WHMCS cart session data. It removes the custom email-body width wrapper, tightens the default email spacing, and moves the expiry note below the verification button. Old default email templates are upgraded automatically unless they appear to be custom administrator templates.
