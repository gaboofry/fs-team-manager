=== Fabriel Team Manager ===
Contributors: fabrielsoftware
Donate link: https://paypal.me/fabergab
Tags: fussball, soccer, sports, tables, blocks
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Central management of teams, time periods, fixtures and league tables for fussball.de widgets.

== Description ==

**Enter once instead of searching every year.**

Every team page on a club website contains a fussball.de widget whose ID is a 36-character string. These IDs change with every season – separately for each team, and for the fixture list and the league table. With twelve teams that is more than twenty IDs that someone has to type in by hand once a year and enter into twenty different pages. Usually as a volunteer, usually in the evening, and a transposed digit often only becomes apparent weeks later.

Fabriel Team Manager ends that. All teams, all widget IDs and all time periods are stored in one central place. What is stored there applies across the entire website.

= The five most important benefits =

**1. One place instead of twenty pages.** Teams, IDs and time periods are maintained centrally in one place. Two Gutenberg blocks fetch the right data on their own.

**2. The season change is prepared, not experienced.** New IDs are entered with a start date – weeks in advance and at a calm pace. On the effective date the website switches over by itself. No one has to sit at the computer in the middle of the night.

**3. Typos are prevented, not just noticed.** Every ID is checked against the valid format before it is saved. Using the "Test" button you can view each widget directly in the backend at desktop, tablet and smartphone width.

**4. Routine work becomes added value.** Because old time periods are kept, a season archive is created automatically in the frontend: visitors choose above the table between the current season, the previous season or the first half of the season – without any extra maintenance step.

**5. Blocks that you configure once and never touch again.** Once a team is assigned to a page, the block automatically recognizes which page it is on. The same block configuration works on all team pages.

= Features =

**Team management**

* Unlimited number of teams, ordering via drag & drop
* Live search across all teams
* Assignment of a WordPress page per team
* Protection against losing unsaved changes

**Time period control**

* Unlimited number of time periods per team, e.g. season, first or second half
* Separate IDs for fixture list and league table per time period
* Switching between team and club fixture list
* Automatic activation based on date
* Warning for overlapping or missing end dates with color highlighting
* If nothing is set for today, the last valid time period is shown with a subtle notice – the page is never empty

**Two Gutenberg blocks**

* *Fixture list & league table*: fixed team or automatic detection, switching between fixture list and league table, selectable start time period, freely configurable colors
* *League table overview (Grid)*: all current league tables as a grid in one, two or three columns. Teams in the same league are automatically combined into one card instead of being shown twice
* Link to the team page directly in the card title
* Uniform card heights in the grid, responsive down to smartphone

**Control, data protection and security**

* Widget preview with device simulation directly in the backend
* Complete JSON backup with one click
* Restore either additively or replacing, with one-time undo
* Optional two-click solution: external content is loaded only after confirmation by the visitor
* Integration with consent-management plugins via a documented interface
* Optional credit notice below each card, switched off by default – no link to us appears on your website unless you explicitly enable it
* Clean uninstallation

**Our commitments**

* No account, no registration, no activation
* The plugin itself does not transmit any data to us
* Its own scripts and styles are loaded only on pages that contain a block
* Fully translatable

= Who is this for? =

For football clubs with more than two teams that maintain their website themselves. The benefit grows with each team. Also for agencies and web maintainers who support several clubs.

= Help in the plugin =

Under "Fabriel Software > Settings" you will find the "Manual & field reference" section. Every input field is explained there: what to enter, what interactions exist and where the setting has an effect in the block editor and in the frontend.

**Trademark notice**

This plugin is an independent product. It is not affiliated with, endorsed or sponsored by the German Football Association (DFB) or the operators of fussball.de. Trademarks and product names mentioned are the property of their respective owners and are used solely to describe technical compatibility.

== External services ==

This plugin embeds fixtures and league tables from the external service **fussball.de**. Without this service the plugin cannot display match data.

**When is data transferred?**

* In the frontend, as soon as a page containing one of the plugin's blocks is loaded and a widget ID is configured.
* In the backend, when a widget ID is opened in the preview via the "Test" button.

**What data is transferred?**

* The visitor's IP address
* The browser's user agent and technical connection data
* The widget ID and widget type configured in the plugin

The script `https://www.fussball.de/widgets.js` is loaded, along with the view embedded by that script under `https://next.fussball.de/`.

Service provider: DFB GmbH & Co. KG, fussball.de – https://www.fussball.de/
Terms of use: https://www.fussball.de/terms-of-use.action
Privacy policy: https://www.fussball.de/privacy-policy.action

Website operators are responsible for describing the use of this service in their privacy policy. With the "Enable two-click solution" setting, external content is only loaded after explicit confirmation by the visitor. In addition, loading can be fully prevented via the `fs_tm_load_remote_widgets` filter and thus coupled to a consent-management plugin.

== Installation ==

1. Upload and activate the plugin via "Plugins > Install New Plugin".
2. Open "Fabriel Software > Teams & Widgets" in the admin menu.
3. Create a team and optionally assign it to a WordPress page.
4. Enter the widget IDs from the fussball.de embed code into the time period.
5. Insert the "Fixture list & table" or "Tables overview (Grid)" block on the desired page.

== Frequently Asked Questions ==

= Where do I get the widget IDs? =

The IDs are in the embed code that fussball.de outputs for a widget. They are the value of the `data-id` attribute, a UUID in the format `01234567-89ab-cdef-0123-456789abcdef`.

= What happens if no time period is configured for today's date? =

The last valid time period is shown and marked discreetly as "Last period" in the card header.

= Will my data be overwritten on import? =

Only in "Replace" mode. The default is "Add". In both cases the previous state is saved and can be restored once directly after the import.

= What data does the plugin store? =

Teams are stored as the internal post type `fs_tm_team`; the page assignment and time periods are stored as post meta on it. In addition there are the options `fs_tm_version`, `fs_tm_click_to_load` and `fs_tm_teams_restore_point` plus a transient for the page list. All of these are removed on uninstallation.

= I updated from an older version. Do I need to do anything? =

No. On the first request after the update the teams are automatically migrated to the new format. The previous state is additionally kept in the option `fs_tm_teams_pre_cpt`. A backup before the update is still recommended.

= How do I connect the plugin to a consent management plugin such as Complianz? =

There are three ways, and they can be combined.

1. **Built-in two-click solution.** Enable it under "Fabriel Software > Settings > Data protection & external content". The decision is made in the browser, so it also works behind a page cache.
2. **The `fs_tm_load_remote_widgets` filter (recommended).** It is evaluated before every widget is rendered. When it returns `false` neither the widget container nor the two-click box is written to the page and the provider script is not enqueued at all – no request reaches fussball.de. Example for Complianz:

`add_filter( 'fs_tm_load_remote_widgets', function ( $enabled ) { return function_exists( 'cmplz_has_consent' ) ? (bool) cmplz_has_consent( 'marketing' ) : $enabled; } );`

   Because the filter runs while the page is built, combine it with a cache that varies by consent state – or use the two-click solution.
3. **Script blocking by the consent plugin.** Without the two-click solution the provider script is enqueued normally (handle `fussballde-widgets`), so script blockers recognise it. If they neutralise it, the plugin detects this and no longer loads any content when a visitor switches between periods either.

Sample code for Complianz, Borlabs Cookie and Real Cookie Banner is in `docs/CONSENT-MANAGEMENT.md` in the source repository.

== Source code & development ==

This plugin contains no obfuscated, minified-only or otherwise unreadable code. Every compiled file in `build/` has its human-readable counterpart bundled in `src/`, and both are shipped in the distributed package:

* `build/admin.js` and `build/admin.css` ← `src/admin/admin.js`, `src/admin/admin.scss`
* `build/frontend.js` and `build/frontend.css` ← `src/frontend/frontend.js`, `src/frontend/frontend.scss`
* `build/blocks/fussball-widget/index.js` ← `src/blocks/fussball-widget/index.js`, `edit.js`, `block.json`
* `build/blocks/tables-overview/index.js` ← `src/blocks/tables-overview/index.js`, `edit.js`, `block.json`

Every generated JavaScript file carries a header comment naming the source file it was compiled from and the public repository.

Public source repository (source code, build tools and full history):
https://github.com/gaboofry/fs-team-manager

Clone it with: `git clone https://github.com/gaboofry/fs-team-manager.git`

= Build tools & steps to regenerate the compiled files =

The build tooling is bundled with the plugin: `package.json`, `package-lock.json` and `webpack.config.js`.

1. Install the exact build dependencies: `npm ci` (or `npm install`)
2. Compile JavaScript, SCSS and block assets into `build/`: `npm run build`
3. Optional: rebuild the translation template with WP-CLI: `wp i18n make-pot . languages/fabriel-team-manager.pot --slug=fabriel-team-manager --domain=fabriel-team-manager --exclude=node_modules,vendor,src`

The build is driven by `@wordpress/scripts` (webpack + Babel + Dart Sass); no other build system is required. The repository additionally contains the ready-made scripts `build.sh` (Linux/macOS) and `build.ps1` (Windows), which run the full build and produce the distribution zip. Those scripts are not part of the shipped plugin; clone the repository to use them.

= Directory assets (banner, screenshots) =

The banner and screenshot images are not part of the plugin package. They live in `.wordpress-org/` in the source repository and belong in the `assets/` directory of the WordPress.org SVN repository, so they are never downloaded by users installing the plugin.

= Third-party libraries =

The plugin bundles no third-party JavaScript or PHP libraries. `build/` contains only code compiled from `src/` plus the webpack runtime of `@wordpress/scripts` (GPL-2.0-or-later, https://github.com/WordPress/gutenberg/tree/trunk/packages/scripts). All WordPress packages used by the blocks (`wp.blocks`, `wp.blockEditor`, `wp.components`, `wp.i18n`, `wp.element`) are loaded from WordPress itself as registered script dependencies and are not shipped with the plugin. The external `https://www.fussball.de/widgets.js` is loaded from the provider and is not part of this package (see "External services").

== Security ==

The public block filters `fs_tm_widget_card_html`, `fs_tm_tables_overview_html`, `fs_tm_card_body_before` and `fs_tm_card_body_after` are extension points for add-ons. Filter callbacks receive the fully escaped HTML and must return escaped HTML only. All dynamic values are escaped with `esc_html()`, `esc_attr()` or `esc_url()` before the filters run, and the value returned by the block render callbacks – including anything a filter added – passes through `wp_kses()` with the plugin's own allow list (`fs_tm_allowed_card_html`) as the last line of defense. That list is `wp_kses_allowed_html( 'post' )` extended by exactly the elements the cards need (`template`, `select`, `option`, `optgroup`, `input`), because `wp_kses_post()` would silently strip them.

== Screenshots ==

1. Central management of all teams and time periods
2. "Fixture list & table" block in the frontend with period selection
3. "Tables overview (Grid)" block

== Changelog ==

= 1.2.2 =
* Fixed: with the two-click solution switched off, every fixture list and league table was embedded twice – the provider script was loaded a second time by the frontend script although WordPress had already enqueued it
* New: a small animated loading indicator in the plugin's own branding is shown while the fixture list or league table is being fetched from the provider
* Changed: in the two-click box the checkbox "Remember this choice for this browser" now comes before the "Load content" button; the button shows a spinner while the content is being inserted
* Changed: the active section is now also highlighted in the WordPress submenu after switching between the plugin's pages
* Changed: if a consent management plugin neutralises the provider script, the plugin no longer embeds content of its own accord when a visitor switches between periods
* Improved: height messages of the embedded content are now handled by a single listener that verifies the sender's origin
* Improved: documented integration with consent management plugins (Complianz, Borlabs Cookie, Real Cookie Banner) in the FAQ, in the manual and in `docs/CONSENT-MANAGEMENT.md`

= 1.2.1 =
* Changed: the "Included by Fabriel Software Teammanager" credit under each card is now opt-in and switched off by default (WordPress.org plugin guideline 10); the consent reset link of the two-click solution is unaffected
* Changed: uploaded backup files are read through the WordPress filesystem API instead of `file_get_contents()`
* Changed: banner and screenshot images are no longer part of the plugin package; they belong in the `assets/` directory of the WordPress.org SVN repository
* Improved: every value of an upload is checked for existence and sanitized individually
* Fixed: invalid `Banner tag:` header and non-standard screenshot notation removed from the readme

= 1.2.0 =
* New: dedicated "Settings" page — data protection, backup and manual moved there from "Teams & Widgets"
* New: continuous navigation bar with brand, sections and support; the separate header area is removed
* New: page switching and saving now only reload the content area instead of the whole page
* New: notices appear as floating toasts and no longer shift the content
* Improved: help texts are no longer cut off by the card edge
* Improved: a team now starts with only "Basic data & assignment" open
* New: extension points for add-ons (`fs_tm_admin_pages`, `fs_tm_admin_toolbar`, `fs_tm_admin_header_actions`, `fs_tm_admin_footer_links`, `fs_tm_admin_version_label`, `fs_tm_admin_notices`, `fs_tm_settings_sections`, `fs_tm_settings_sections_before`, `fs_tm_show_core_backup`, `fs_tm_team_edit_sections_before`, `fs_tm_team_form_saved`)

= 1.1.0 =
* Changed: teams are stored as a dedicated post type instead of a single option; the migration runs automatically and the old state remains backed up
* New: extension points for add-ons (`fs_tm_capability`, `fs_tm_addon_active`, `fs_tm_widget_card_html`, `fs_tm_card_body_before`, `fs_tm_card_body_after`, `fs_tm_tables_overview_html`, `fs_tm_sanitize_period`, `fs_tm_team_data`, `fs_tm_team_saved`, `fs_tm_before_team_deleted`)
* Improved: teams are now loaded only once per request and kept in the object cache

= 1.0.1 =
* New: two-click solution for external content including the `fs_tm_load_remote_widgets` filter
* New: import as "Add" or "Replace", with one-time restore of the previous state
* New: if no time period is configured for today's date, the last valid period is shown with a notice
* Improved: frontend assets are now only loaded on pages containing one of the plugin's blocks
* Fixed: server-side validation errors were discarded without a message; inputs are now preserved
* Security: additional checks for JSON import

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.1.0 =
Teams are automatically migrated to a new storage format on the first request after the update. Please create a backup beforehand.

= 1.0.1 =
Fixes a bug where failed save operations discarded inputs, and adds a two-click solution for external content.
