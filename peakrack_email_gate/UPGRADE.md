# Module Upgrade Notes

1. Back up the WHMCS database.
2. Replace the existing `modules/addons/peakrack_email_gate/` directory with this directory.
3. Open **Addons > PeakRack Email Verification Gate** once.
4. Review email templates and rate-limit settings.

Version 1.1.8 keeps settings, records, logs, and WHMCS cart session data. It disables and greys out the six verification-code inputs while a lockout is active, including the moment an AJAX code check reaches the lockout limit.
