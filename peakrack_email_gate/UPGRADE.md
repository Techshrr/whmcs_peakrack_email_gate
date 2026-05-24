# Module Upgrade Notes

1. Back up the WHMCS database.
2. Replace the existing `modules/addons/peakrack_email_gate/` directory with this directory.
3. Open **Addons > PeakRack Email Verification Gate** once.
4. Review email templates and rate-limit settings.

Version 1.1.7 keeps settings, records, logs, and WHMCS cart session data. It replaces the broken 1.1.6 table-based default email body with a plain div/p fragment that does not trigger WHMCS email table borders. Old default email templates are upgraded automatically unless they appear to be custom administrator templates.
