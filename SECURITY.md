# Security Policy

## Reporting a vulnerability

Please do not open public GitHub issues for security vulnerabilities.

Report issues involving verification links, six-digit codes, HMAC hashing, resend limits, or checkout gating to:

security@peakrack.com

Please include:

- Affected addon version, WHMCS version, and PHP version
- Whether the issue affects the gate page, email link, code entry, or checkout redirect
- Description of the issue and reproduction steps
- Potential impact on email-verification enforcement
- Suggested mitigation, if available

## Supported versions

| Version | Supported |
|---|---|
| 1.x | Yes |
| < 1.0 | No |

## Sensitive data

Do not include production HMAC secrets, signed verification URLs, verification codes, client email addresses, WHMCS session values, admin credentials, mail logs, or server logs containing customer identifiers.

## Public issues

Installation problems, language issues, and documentation fixes may be submitted through GitHub Issues.

Security vulnerabilities must be reported privately by email.
