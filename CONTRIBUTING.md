# Contributing

Thanks for considering a contribution. This guide explains the workflow,
the quality bar, and how to get your change merged.

## Code of conduct

Be respectful. Disagree on technical merit, never on people. Reports of
abusive behaviour to `alejandro.rios@clearis.es`.

## How to propose a change

1. **Open an issue first** for anything bigger than a typo. Discussing
   the approach before coding saves both sides time. Use the
   `enhancement`, `bug`, or `question` labels as appropriate.
2. **Fork** the repository and create a branch from `main`.
3. **Make your change** following the rules in *Quality bar* below.
4. **Open a pull request** against `main`. Reference the issue it
   addresses.

## Quality bar

All of the following must pass before a PR is mergeable. CI runs them on
every push; running them locally first saves you a round-trip.

### Coding standard (ECS)

```bash
vendor/bin/ecs check
```

Auto-fix anything fixable with `vendor/bin/ecs check --fix`.

### Static analysis (PHPStan, level max)

```bash
vendor/bin/phpstan analyse
```

Level stays at `max`. If you cannot satisfy it for a genuine reason
(typically DBAL `mixed` returns, framework integration limits), document
why in a docblock and add a **targeted** `ignoreErrors` entry in
`phpstan.neon` — never lower the level.

### Tests (PHPUnit)

```bash
vendor/bin/phpunit
```

New behaviour must come with tests. Aim for Unit at minimum; Behat
features for admin workflows are strongly preferred.

### Behat (optional but appreciated)

```bash
vendor/bin/behat
```

Behat features run an embedded Sylius application. See `behat.yml.dist`
and `tests/Behat/` for the setup.

## Commit messages

We aim for conventional, scannable subject lines. Format:

```
type(scope): summary in the imperative

Optional body that explains *why*, not *what* (the diff shows the what).

Refs #123    (or "Closes #123" for bug fixes)
```

Examples:

- `feat(numbering): support yearly counter reset per series`
- `fix(importer): handle missing taxes_total column gracefully`
- `docs(migration): clarify rollback procedure`
- `chore(deps): bump phpstan/phpstan to ^1.13`

## What not to do

- Do not bump the PHP / Sylius / Symfony minimum requirements without an
  issue and a clear rationale. The plugin targets the same window Sylius
  itself supports.
- Do not break the public events API (`InvoiceIssuedEvent`,
  `InvoiceRectifiedEvent`, `InvoiceCancelledEvent`) without a major
  version bump and an `UPGRADE.md` entry — third-party integrations
  depend on it.
- Do not introduce non-fixable lint errors and merge them to silence
  them. Either fix them properly or open an issue to debate the rule.

## Releasing (maintainers only)

1. Update `CHANGELOG.md` with the new version section.
2. Bump the `branch-alias` in `composer.json` if appropriate.
3. Tag: `git tag -a vX.Y.Z -m "Release vX.Y.Z"` and push the tag.
4. Packagist picks it up automatically via the webhook.

## License

By contributing, you agree that your contributions will be licensed
under the [MIT License](LICENSE) that covers the project.
