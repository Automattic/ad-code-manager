# Contributing to Ad Code Manager

Thanks for helping to improve Ad Code Manager. Please follow [Automattic's code of conduct](https://automattic.com/code-of-conduct/) in issues and pull requests.

## Reporting bugs

Before opening an [issue](https://github.com/Automattic/ad-code-manager/issues), check that you're running the latest versions of the plugin and WordPress, and that the problem still happens with other plugins deactivated and a default theme active. Include the steps to reproduce, what you expected to happen, and what actually happened.

Please report security vulnerabilities privately through the repository's [Security tab](https://github.com/Automattic/ad-code-manager/security/advisories/new), rather than in a public issue.

## Development setup

You'll need [Composer](https://getcomposer.org/), and [Docker](https://www.docker.com/) for the integration tests, which run in [`wp-env`](https://www.npmjs.com/package/@wordpress/env).

```bash
git clone git@github.com:Automattic/ad-code-manager.git
cd ad-code-manager
composer install
npx wp-env start
```

## Making changes

1. Fork the repository and create a branch from `develop` (for example `fix/widget-output` or `feature/new-provider`).
2. Make your changes, adding or updating tests to cover them.
3. Check code standards and run the tests:

   ```bash
   composer cs                   # PHPCS, using .phpcs.xml.dist
   composer lint                 # PHP syntax lint
   composer test:unit            # Unit tests
   composer test:integration     # Integration tests (needs wp-env running)
   composer test:integration-ms  # Integration tests on multisite
   ```

4. Open a pull request against `develop`.

Code follows the [WordPress VIP coding standards](https://github.com/Automattic/VIP-Coding-Standards), and new ad networks should be added as provider classes in `src/Providers/` rather than in the main plugin class.

## Pull requests

- Keep each pull request focused on one change, and explain why the change is needed as well as what it does.
- Tidy your branch before it's merged. Pull requests are merged with a merge commit, so your individual commits and their messages are kept in the history.
- **Commits must be signed.** The `develop` and `main` branches only accept commits with a verified signature, so a pull request containing an unsigned commit can't be merged until it's re-signed. See [GitHub's guide to signing commits](https://docs.github.com/en/authentication/managing-commit-signature-verification/signing-commits). If you've already pushed unsigned commits, re-sign them (for example with `git rebase --exec 'git commit --amend --no-edit -S' develop`) and force-push your branch.
- CI runs code standards checks, unit tests and integration tests on every pull request. A maintainer from the VIP plugins team will review it.

## Releases

Releases are handled by the maintainers: a release branch is merged into `main` through a pull request, and publishing the GitHub release deploys it to WordPress.org.
