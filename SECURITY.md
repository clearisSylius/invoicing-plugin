# Security Policy

## Reporting a vulnerability

If you believe you have found a security vulnerability in
`clearissylius/invoicing-plugin`, please report it responsibly:

- **Do not open a public GitHub issue.** Public reports can be exploited
  against shops that have not yet upgraded.
- **E-mail** `alejandro.rios@clearis.es` with:
  - A description of the vulnerability and its potential impact.
  - Reproduction steps (preferably a minimal failing test case or a Sylius
    setup that demonstrates the issue).
  - The affected version range, if you know it.
  - Whether you intend to disclose publicly and on what timeline.

You will receive an acknowledgement within **72 hours**. After triage, we
target a fix and a coordinated disclosure within **30 days** for
high-severity issues, longer for lower-severity ones.

## Supported versions

Only the latest minor release of `1.x` receives security fixes during
this initial release window. Once `2.x` ships, the previous major
receives security fixes for six months.

| Version | Supported |
|---------|-----------|
| 1.x     | ✅        |
| < 1.0   | ❌ (pre-release, not for production) |

## Scope

Security reports we want to hear about:

- Authentication / authorisation flaws in the admin controllers.
- SQL injection or DBAL-level injection in the migration importer.
- Path traversal in the PDF storage or template loading.
- Information disclosure (e.g. invoice PDFs reachable by unauthenticated
  users, customer tax IDs leaking into logs).
- Cross-site scripting in any rendered Twig template.

Out of scope:

- Bugs in upstream Sylius, Symfony, or Doctrine — please report those
  directly to those projects.
- Issues that require a compromised host filesystem or shell access to
  exploit.
- Theoretical attacks without a working proof-of-concept.

## Credit

Reporters are credited in the `CHANGELOG.md` for the release that
contains the fix, unless they prefer to remain anonymous.
