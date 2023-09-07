# Changelog for Ad Code Manager

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.6.0] - 2022-03-21

This version requires WordPress 5.5 and PHP 7.1 as a minimum.

### Added
- Added link to the Settings page from the plugin page to make it easier to find after plugin activation.
- Allow filtering the TTL for matching ad codes. Props @dlh01.

### Changed
- Increased minimum WordPress version to WP 5.5.
- Increased minimum PHP version to PHP 7.1.
- Moved the settings page from the Tools menu to the Settings menu to make it more intuitive to find.
- Refactored `action_acm_tag()` method to a method that returned the tag and another that echoed the returned tag.
- Added check for post types when deleting ad codes. Props @rbcorrales.
- Changed the assignment of $unit_sizes_output to an array. Props @victorholt.

### Fixed
- Fixed internationalization of PHP strings. Props @christianc1, @shantanu2704, and @trepmal.
- Fixed PHP 7 incompatibilities. Props @swissspidy, @shantanu2704, @alexiskulash, and @jonathanstegall.
- Fixed broken tests and workflows.

### Maintenance
- Remove parsing of readme.txt into contextual help with a Markdown parsing library.
- Improved some coding standards.
- Moved and reorganised many classes and how they are initialized.
- Refreshed screenshots.
- Moved previously-linked configuration guidance into Readme.
- Reorganised documentation sections.
- Refreshed the on-page contextual help.
- Added GitHub workflow to push the plugin to WordPress.org.
- Added script to more easily bump version numbers.
- Added script to populate release notes into Readme changelog for WordPress.org.
- Added dependabot configuration file.
- Added `.gitattributes` file.
- Added `LICENSE` file.

## [0.5] - 2016-04-13

### Added
- Added support for flex sized DFP Async ads.
- Added `robots.txt` entries for provider's crawlers.
- New Italian translation. Props @sniperwolf.

### Fixed
- Prevent global `$post` pollution if ad code is getting rendered inside a loop.

### Maintenance
- Using PHP5 constructs when initializing the widget.

## [0.4.1] - 2013-04-27

### Changed
- Disabled rendering of ads on preview to avoid crawling errors. Props @paulgibbs.

### Fixed
- Corrected "medium rectangle" ad size for DFP Async Provider. Props @NRG-R9T.

## [0.4] - 2013-03-19

### Added
- New filter `acm_output_html_after_tokens_processed` for rare cases where you might want to filter HTML after the tokens are processed.

### Changed
- Streamlined configuration for Doubleclick for Publishers Async and Google AdSense.
- Faster, cleaner JavaScript. Props @jeremyfelt and @carldanley.

## [0.3] - 2012-10-26

### Added
- Conditional operator logic can be set on an ad code by ad code basis. Props @jtsternberg.

### Fixed
- If an ad tag doesn't need a URL, ignore the allowlist check.
- Make sure that all providers list tables call `parent::get_columns()` to avoid conflicts with filters.

### Maintenance
- Coding standards cleanup.

## [0.2.3] - 2012-06-12

### Added
- Allow columns to be optional when creating and editing ad codes, introduced new filter `acm_ad_code_args`.

### Removed
- Remove `acm_provider_columns` filter.

## [0.2.2] - 2012-06-05

### Added
- New Google Ad Sense provider. Props @ethitter.
- Bulk delete action added for the `WP_List_Table` of ad codes. Delete more ad codes in one go.
- New `acm_register_provider_slug` for registering a provider that's included outside the plugin (e.g. a theme).

### Fixed
- Instantiate the WP List Table on the view, instead of on admin_init, to reduce conflicts with other list tables.

## [0.2.1] - 2012-05-15

### Changed
- Flush the cache whenever an ad code is created or deleted so you don't have to wait for a timeout with a persistent cache.

### Fixed
- Default to priority 10 when querying for ad codes if there is no priority set.

## [0.2] - 2012-05-07

### Added
- Added in-plugin contextual help.
- Implemented priority for ad code (allows to workaround ad code conflicts if any).
- Implemented the `[acm-tag]` shortcode.
- Implemented ACM Widget. Props @jtsternberg.

### Changed
- UI reworked from the ground up to look and work much more like the WordPress admin (using `WP_List_Table`).
- Abstracted ad network logic, so users can integrate other ad networks. Pull requests to add support to the plugin are always welcome.
- Initial loading of the ad codes is now cached using object cache.

### Fixed
- Enable using ad codes with empty filters using a filter.
- Setting the logical operator from OR to AND did not seem to result in the expected behaviour for displaying ads.
- Remove logical operator check when a conditional for an ad code is empty.

## [0.1.3] - 2012-02-14

### Changed

- UI cleanup for the admin, including styling and information on applying conditionals.

## [0.1.2] - 2012-02-10

### Added
- Readme with full description and examples.

### Fixed
- Save the proper value when editing actions.

## [0.1.1] - 2012-01-19

Bug fix release.

## 0.1 - 2012-01-18

Initial release.

[0.6.0]: https://github.com/Automattic/ad-code-manager/compare/0.5...0.6.0
[0.5]: https://github.com/Automattic/ad-code-manager/compare/0.4.1...0.5
[0.4.1]: https://github.com/Automattic/ad-code-manager/compare/0.4...0.4.1
[0.4]: https://github.com/Automattic/ad-code-manager/compare/0.3...0.4
[0.3]: https://github.com/Automattic/ad-code-manager/compare/0.2.3...0.3
[0.2.3]: https://github.com/Automattic/ad-code-manager/compare/0.2.2...0.2.3
[0.2.2]: https://github.com/Automattic/ad-code-manager/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/Automattic/ad-code-manager/compare/0.2...0.2.1
[0.2]: https://github.com/Automattic/ad-code-manager/compare/0.1.3...0.2
[0.1.3]: https://github.com/Automattic/ad-code-manager/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/Automattic/ad-code-manager/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/Automattic/ad-code-manager/compare/0.1...0.1.1
