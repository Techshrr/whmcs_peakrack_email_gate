# Module Upgrade Notes

1. Back up the WHMCS database.
2. Replace the existing `modules/addons/peakrack_email_gate/` directory with this directory.
3. Open **Addons > PeakRack Email Verification Gate** once.
4. Review email templates and rate-limit settings.

Version 1.1.5 keeps settings, records, logs, and WHMCS cart session data. It updates the client email-label layout, adds the Get Code button color setting, keeps the ready button label as Get Code, and refreshes the default email layout. Old default email templates are upgraded automatically unless they appear to be custom administrator templates.
