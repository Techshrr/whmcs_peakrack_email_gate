# Module Upgrade Notes

1. Back up the WHMCS database.
2. Replace the existing `modules/addons/peakrack_email_gate/` directory with this directory.
3. Open **Addons > PeakRack Email Verification Gate** once.
4. Review email templates and rate-limit settings.

Version 1.1.9 keeps settings, records, logs, and WHMCS cart session data. It improves the lockout wording and adds a silent client-side timer so the lockout notice disappears and the code inputs are released when the lock expires.
