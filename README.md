# PeakRack Email Verification Gate

> Official repository: https://github.com/Techshrr/whmcs_peakrack_email_gate
> License: Apache License 2.0

PeakRack Email Verification Gate is a WHMCS addon that directs unverified clients to an email verification page before selected client-area actions.

## Overview

The addon keeps WHMCS native email verification enabled and adds a separate gate page for unverified clients. From that page, clients can request a custom verification email containing a signed link and a six-digit code.

It stores module settings, verification records, and logs in module tables. Deactivation keeps those records; uninstall only removes data when the administrator confirms deletion.

## Features

- Redirects unverified logged-in clients to a dedicated verification page.
- Blocks unverified checkout attempts and returns clients to checkout after verification.
- Sends custom verification emails with a signed link and six-digit code.
- Stores verification links and codes as HMAC hashes.
- Applies resend cooldown, hourly resend limits, failed-code lockout, and token expiry.
- Provides English, Simplified Chinese, and Hong Kong Traditional Chinese client text, email templates, and admin UI.
- Includes admin records, logs, unlock, cleanup, and HMAC secret rotation tools.
- Can mirror key events to the WHMCS Activity Log.

## Requirements

- WHMCS 9.0.x
- PHP 8.2 or later
- MySQL 5.7 / 8.0
- WHMCS native email verification enabled

## Installation

1. Download the latest release from the official repository.
2. Upload the addon directory to:

   `modules/addons/peakrack_email_gate/`

3. Log in to the WHMCS admin area.
4. Go to **System Settings > Addon Modules** and activate **PeakRack Email Verification Gate**.
5. Open **Addons > PeakRack Email Verification Gate** and review the settings before using it in production.

## Configuration

| Option | Description | Default |
|---|---|---|
| Enable module | Enables or disables custom gate behavior | Enabled |
| Force unverified users to gate page | Redirects unverified users from client-area pages | Enabled |
| Redirect unverified checkout to verification | Redirects checkout attempts to the gate page | Enabled |
| Mirror key events to WHMCS Activity Log | Copies key events to the WHMCS activity log | Enabled |
| Resend cooldown seconds | Minimum time between custom email requests | 60 |
| Max resends per hour | Per-user resend limit | 5 |
| Code lifetime minutes | Validity period for six-digit codes | 10 |
| Link lifetime minutes | Validity period for signed links | 30 |
| Max failed code attempts | Failed code attempts before temporary lockout | 5 |
| Lock minutes | Temporary lock duration | 15 |
| Log retention days | Age-based module log cleanup | 180 |
| Maximum log rows | Count-based module log cleanup | 10000 |
| Get Code button color | Client-area resend button color | #2563eb |
| Email subject/body templates | English, Simplified Chinese, and Traditional Chinese verification email content | Built-in templates |
| Gate notice templates | English, Simplified Chinese, and Traditional Chinese client-page notices | Built-in notices |

## Usage

The administrator activates the addon, keeps WHMCS native email verification enabled, and reviews the gate, checkout, rate-limit, email-template, and log-retention settings.

An unverified logged-in client is redirected to the verification page. The client can use the native WHMCS verification email, request a custom email, click the signed link, or enter the six-digit code. When verification succeeds, the addon updates the WHMCS email verification state and redirects the client back to the remembered local page.

## Database Tables

- `mod_peakrack_email_gate_settings`
- `mod_peakrack_email_gate_records`
- `mod_peakrack_email_gate_logs`

## Upgrade

See [UPGRADE.md](UPGRADE.md).

## Chinese Documentation

See [README.zh-CN.md](README.zh-CN.md).

## Security

Do not commit production credentials, API keys, database passwords, payment secrets, WHMCS license data, customer data, identity documents, or private signing keys.

To report a security issue, see [SECURITY.md](SECURITY.md).

## License

This project is licensed under the Apache License 2.0. See [LICENSE](LICENSE) for details.

Additional project notices are available in [NOTICE](NOTICE).
