# Air Raid Alert UA

Embed live air-raid alert widgets from [alerts.na-agency.com](https://alerts.na-agency.com) in WordPress using a shortcode, Gutenberg block, or classic widget.

## Features

- Live air-raid alert widget with multiple embed types and sizes.
- Region picker with searchable Select2 dropdowns in the admin and classic widget.
- Shortcode, Gutenberg block, and classic widget support.
- Light, dark, and automatic color themes.
- Custom colors and scoped custom CSS.
- Optional CSP-safe image fallback.
- Optional source attribution link.
- Multisite support with network defaults and per-site inheritance or overrides.
- Bundled region snapshot for use when the remote region source is unavailable.
- English (`en_US`) and Ukrainian (`uk`) translations.
- No build step required.

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer

## Installation

1. Copy the `air-raid-alert-ua` directory into `wp-content/plugins/`.
2. Activate **Air Raid Alert UA** from the WordPress Plugins screen.
3. Open **Settings > Air Raid Alert UA** and configure the default widget.

For multisite installations, activate the plugin network-wide and use **Network Admin > Settings > Air Raid Alert UA** to configure network defaults. Each site can inherit those defaults or override them on its own settings page.

## Usage

### Shortcode

```text
[air_raid_alert]
```

Examples:

```text
[air_raid_alert region="4" type="card" size="m" theme="auto"]
[air_raid_alert type="ua" size="l" theme="dark"]
[air_raid_alert type="line" fallback="1" attribution="0" class="my-alert"]
```

| Attribute | Description | Values |
| --- | --- | --- |
| `region` | Numeric region ID | A region ID from the settings page |
| `type` | Embed type | `card`, `line`, `map`, `stats`, `ua` |
| `size` | Widget size | `s`, `m`, `l` |
| `theme` | Color theme | `auto`, `light`, `dark` |
| `fallback` | Use the CSP-safe image fallback | `1` or `0` |
| `attribution` | Show the source attribution link | `1` or `0` |
| `class` | Additional CSS class | Any valid class name |

The `ua` type shows the whole-Ukraine view and ignores the selected region.

### Gutenberg block

Add the **Air Raid Alert** block from the **Widgets** category. The block inspector provides the same region, type, size, theme, fallback, and attribution options as the shortcode.

### Classic widget

Add **Air Raid Alert** from **Appearance > Widgets**. Leave a field set to **Use plugin setting** to inherit the value from the plugin settings page.

## Settings

The site settings page controls:

- Whether the widget is enabled.
- The default region and embed type.
- Widget size and theme.
- Custom colors and custom CSS.
- Image fallback and source attribution.
- Region cache lifetime and manual region refresh.
- Whether plugin data should be deleted on uninstall.

On multisite, the network settings page provides defaults for all sites. Site administrators can inherit those defaults or override them for their own site.

## Region data

The plugin uses region data from `alerts.na-agency.com`. A bundled snapshot in `data/regions.json` keeps the plugin usable when the remote source cannot be reached. The region list can be refreshed from the settings page.

## Data source and privacy

Embedding an iframe or image requests content from `alerts.na-agency.com`. That request sends the visitor's IP address, user agent, and referring origin to the external site. The source site states that it does not use cookies or analytics.

If your site has a Content Security Policy that blocks frames, enable the image fallback. The fallback is also available per shortcode with `fallback="1"`.

## Localization

The plugin text domain is `air-raid-alert-ua` and translation files live in `languages/`.

Included locales:

- English (`en_US`)
- Ukrainian (`uk`)

Block editor translations are provided through `wp_set_script_translations()` and the matching JSON files.

## Third-party assets

Select2 4.1.0-rc.0 is bundled in `admin/assets/vendor/select2/` and is licensed under the MIT License. Its license text is included in that directory.

## Development

The plugin does not require a build step. Assets are loaded directly from the plugin directory.

Key entry points:

- `air-raid-alert-ua.php` - plugin bootstrap and lifecycle hooks.
- `includes/` - settings, regions, rendering, assets, shortcode, block, widget, admin, and plugin classes.
- `blocks/widget/` - Gutenberg block metadata and editor script.
- `admin/` - admin styles, scripts, views, and bundled Select2.
- `public/` - front-end styles and scripts.
- `languages/` - translation templates and compiled translations.

## License

MIT License. See [LICENSE](LICENSE).

Copyright (c) 2026 Abelixer.
