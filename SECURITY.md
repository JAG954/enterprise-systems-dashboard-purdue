# Security Policy

## Supported Versions

This is an educational portfolio project, not a production service. Security fixes should target the current `main` branch.

## Reporting a Vulnerability

Please report suspected vulnerabilities privately to the repository owner. Do not open a public issue containing credentials, exploit details, or sensitive data.

If this repository is made public on GitHub, enable private vulnerability reporting in the repository settings when available.

## Known Security Limitations

- The original assignment schema used MD5 password hashes. Replace this with `password_hash()` and `password_verify()` before production use.
- The app depends on a local MySQL database schema and should not be connected to a public database without review.
- Local config files such as `www/config.local.php` and `.env` must stay untracked.
- Any database credentials previously committed or shared should be rotated before publication.
