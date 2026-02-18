# Ad Code Manager

WordPress plugin for easy ad code management.

## Project Knowledge

| Property | Value |
|----------|-------|
| **Main file** | `ad-code-manager.php` |
| **Text domain** | `ad-code-manager` |
| **Namespace** | `Automattic\AdCodeManager` |
| **Source directory** | `src/` |
| **Version** | 0.8.0 |
| **Requires PHP** | 7.4+ |
| **Requires WP** | 6.4+ |

### Directory Structure

```
ad-code-manager/
├── src/                    # Main plugin classes (PSR-4)
│   ├── Providers/          # Ad provider implementations (DoubleClick, Google AdSense)
│   └── UI/                 # Admin UI components (autocomplete, contextual help)
├── tests/
│   ├── Unit/               # Unit tests (Brain Monkey)
│   └── Integration/        # Integration tests (wp-env)
├── languages/              # Translation files
├── .github/workflows/      # CI: cs-lint, integration, unit, deploy
└── .phpcs.xml.dist         # PHPCS configuration
```

### Key Classes

- `Ad_Code_Manager` — Main plugin class
- `Acm_Widget` — WordPress widget for ad display
- `Acm_Wp_List_Table` — Admin list table for managing ad codes
- `Acm_Provider` — Base class for ad providers
- `Providers/` — Concrete providers: DoubleClick, Google AdSense variants

### Dependencies

- `automattic/vipwpcs` — WordPress VIP coding standards
- `yoast/wp-test-utils` — Test utilities (Brain Monkey, WP integration)

## Commands

```bash
composer cs                # Check code standards (PHPCS)
composer cs-fix            # Auto-fix code standard violations
composer lint              # PHP syntax lint
composer test:unit         # Run unit tests
composer test:integration  # Run integration tests (requires wp-env: npx wp-env start)
composer test:integration-ms  # Run multisite integration tests
composer coverage          # Run tests with HTML coverage report
```

## Conventions

Follow the standards documented in `~/code/plugin-standards/` for full details. Key points:

- **Commits**: Use the `/commit` skill. Favour explaining "why" over "what".
- **PRs**: Use the `/pr` skill. Squash and merge by default.
- **Branch naming**: `feature/description`, `fix/description` from `develop`.
- **Testing**: Write integration tests for WordPress-dependent behaviour, unit tests for isolated logic. Use `Yoast\WPTestUtils\WPIntegration\TestCase` for integration, `Yoast\WPTestUtils\BrainMonkey\YoastTestCase` for unit. Test files named `*Test.php`, one logical concept per test, Arrange-Act-Assert pattern.
- **Code style**: WordPress coding standards via PHPCS. Tabs for indentation. PHPDoc on all public methods.
- **i18n**: All user-facing strings must use the `ad-code-manager` text domain.

## Architectural Decisions

- **Provider pattern**: Ad networks are abstracted behind the `Acm_Provider` base class. New ad networks should be added as new provider classes in `src/Providers/`, not by modifying the core plugin.
- **PSR-4 autoloading**: Classes in `src/` use PSR-4 under the `Automattic\AdCodeManager` namespace.
- **WordPress.org deployment**: Has a deploy workflow for WordPress.org SVN. Do not manually modify SVN assets.

## Common Pitfalls

- Do not edit WordPress core files or bundled dependencies in `vendor/`.
- Do not add ad provider logic directly to the main plugin class — create a new provider in `src/Providers/`.
- Run `composer cs` before committing. CI will reject code standard violations.
- Integration tests require `npx wp-env start` running first. If tests fail with connection errors, check that wp-env is up.
- The widget class extends `WP_Widget` directly — do not refactor this to a block without explicit agreement, as it would be a breaking change for existing users.
