# PeakRack Email Verification Gate Module

Install this directory as:

`modules/addons/peakrack_email_gate/`

Keep WHMCS native Email Verification enabled.

## What It Does

- Gates unverified logged-in users after registration.
- Keeps the first registration email as the native WHMCS verification email.
- Sends custom verification emails only when the user clicks `Get Code`.
- Includes both a custom verification link and a 6-digit security code.
- Stores custom tokens and codes only as HMAC hashes.
- Provides a six-box code UI with automatic verification and redirect back to the originally requested page.
- Redirects unverified checkout attempts to the gate page and returns clients to checkout after verification without clearing the WHMCS cart session.

## Admin

Open **Addons > PeakRack Email Verification Gate** to configure settings, email templates, logs, records, unlock tools, cleanup tools, and HMAC secret rotation.

See the repository-level README and UPGRADE files for full installation and upgrade notes.
