<?php
/**
 * Settings page view.
 *
 * @package AirRaidAlertUA
 *
 * @var array      $settings     Settings to display.
 * @var bool       $inherit      Whether the current site inherits network settings.
 * @var bool       $is_network   Whether this is the network settings page.
 * @var array|null $notice       Admin notice.
 * @var array      $last_refresh Last refresh metadata.
 * @var string     $form_action  admin-post.php URL.
 * @var string     $refresh_url  Region refresh URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$action      = $is_network ? 'ara_save_network' : 'ara_save_settings';
$title       = $is_network
	? __( 'Air Raid Alert UA — Network defaults', 'air-raid-alert-ua' )
	: __( 'Air Raid Alert UA', 'air-raid-alert-ua' );
$preview_url = $is_network
	? ARA_Renderer::get_url( array( 'network' => true ) )
	: ARA_Renderer::get_url();
?>
<div class="wrap ara-settings">
	<h1><?php echo esc_html( $title ); ?></h1>

	<p>
		<a class="button" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener">
			<?php esc_html_e( 'Preview current widget', 'air-raid-alert-ua' ); ?>
		</a>
	</p>

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $is_network ) : ?>
		<div class="notice notice-info inline">
			<p>
				<?php esc_html_e( 'These defaults apply to every site in the network that has “Inherit network settings” enabled. Each site can still use its own widget switch.', 'air-raid-alert-ua' ); ?>
			</p>
		</div>
	<?php elseif ( is_multisite() ) : ?>
		<div class="notice notice-info inline">
			<p>
				<?php esc_html_e( 'This site can inherit the network defaults or override them below.', 'air-raid-alert-ua' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( $form_action ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
		<?php wp_nonce_field( $is_network ? 'ara_save_network' : 'ara_save_settings' ); ?>

		<h2 class="title"><?php esc_html_e( 'Status', 'air-raid-alert-ua' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr data-ara-inherit-ignore="1">
				<th scope="row"><?php esc_html_e( 'Widget switch', 'air-raid-alert-ua' ); ?></th>
				<td>
					<label for="ara-enabled">
						<input type="checkbox" id="ara-enabled" name="ara_settings[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>>
						<?php esc_html_e( 'Enable the widget, shortcode, block, and classic widget.', 'air-raid-alert-ua' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'When disabled, the shortcode, block, and classic widget render nothing on the front end.', 'air-raid-alert-ua' ); ?>
						<?php if ( ! $is_network && is_multisite() ) : ?>
							<?php esc_html_e( 'The site switch works even while inheriting network defaults.', 'air-raid-alert-ua' ); ?>
						<?php endif; ?>
					</p>
				</td>
			</tr>

			<?php if ( ! $is_network && is_multisite() ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Network defaults', 'air-raid-alert-ua' ); ?></th>
					<td>
						<label for="ara-inherit">
							<input type="checkbox" id="ara-inherit" name="ara_settings[inherit]" value="1" <?php checked( $inherit ); ?>>
							<?php esc_html_e( 'Inherit network settings for this site.', 'air-raid-alert-ua' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Uncheck to give this site its own region and appearance settings. The stored values below become active when inheritance is disabled.', 'air-raid-alert-ua' ); ?>
						</p>
					</td>
				</tr>
			<?php endif; ?>
		</table>

		<h2 class="title"><?php esc_html_e( 'Default embed', 'air-raid-alert-ua' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="ara-region"><?php esc_html_e( 'Region', 'air-raid-alert-ua' ); ?></label>
				</th>
				<td>
					<select id="ara-region" name="ara_settings[region]" class="regular-text ara-region-select">
						<?php echo ARA_Regions::get_select_options_html( absint( $settings['region'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Choose an oblast, raion, or hromada. The whole-Ukraine type does not use a region.', 'air-raid-alert-ua' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Type', 'air-raid-alert-ua' ); ?></th>
				<td>
					<fieldset>
						<label>
							<select name="ara_settings[type]" id="ara-type">
								<option value="card" <?php selected( 'card', $settings['type'] ); ?>><?php esc_html_e( 'Card', 'air-raid-alert-ua' ); ?></option>
								<option value="line" <?php selected( 'line', $settings['type'] ); ?>><?php esc_html_e( 'Line', 'air-raid-alert-ua' ); ?></option>
								<option value="map" <?php selected( 'map', $settings['type'] ); ?>><?php esc_html_e( 'Map', 'air-raid-alert-ua' ); ?></option>
								<option value="stats" <?php selected( 'stats', $settings['type'] ); ?>><?php esc_html_e( 'Stats', 'air-raid-alert-ua' ); ?></option>
								<option value="ua" <?php selected( 'ua', $settings['type'] ); ?>><?php esc_html_e( 'Whole Ukraine', 'air-raid-alert-ua' ); ?></option>
							</select>
						</label>
					</fieldset>
					<p class="description">
						<?php esc_html_e( 'The widget type also controls the recommended dimensions.', 'air-raid-alert-ua' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Size', 'air-raid-alert-ua' ); ?></th>
				<td>
					<select name="ara_settings[size]">
						<option value="s" <?php selected( 's', $settings['size'] ); ?>><?php esc_html_e( 'Small', 'air-raid-alert-ua' ); ?></option>
						<option value="m" <?php selected( 'm', $settings['size'] ); ?>><?php esc_html_e( 'Medium', 'air-raid-alert-ua' ); ?></option>
						<option value="l" <?php selected( 'l', $settings['size'] ); ?>><?php esc_html_e( 'Large / 100% width', 'air-raid-alert-ua' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Theme', 'air-raid-alert-ua' ); ?></th>
				<td>
					<select name="ara_settings[theme]">
						<option value="auto" <?php selected( 'auto', $settings['theme'] ); ?>><?php esc_html_e( 'Auto (system)', 'air-raid-alert-ua' ); ?></option>
						<option value="light" <?php selected( 'light', $settings['theme'] ); ?>><?php esc_html_e( 'Light', 'air-raid-alert-ua' ); ?></option>
						<option value="dark" <?php selected( 'dark', $settings['theme'] ); ?>><?php esc_html_e( 'Dark', 'air-raid-alert-ua' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Appearance', 'air-raid-alert-ua' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Colors', 'air-raid-alert-ua' ); ?></th>
				<td>
					<label for="ara-use-custom-colors">
						<input type="checkbox" id="ara-use-custom-colors" name="ara_settings[use_custom_colors]" value="1" <?php checked( ! empty( $settings['use_custom_colors'] ) ); ?>>
						<?php esc_html_e( 'Use custom colors', 'air-raid-alert-ua' ); ?>
					</label>
					<div id="ara-colors-panel" class="ara-colors-panel">
						<p>
							<label for="ara-bg"><?php esc_html_e( 'Background', 'air-raid-alert-ua' ); ?></label>
							<span class="ara-color-input">#<input type="text" id="ara-bg" name="ara_settings[bg]" value="<?php echo esc_attr( ltrim( $settings['bg'], '#' ) ); ?>" maxlength="6" pattern="[0-9a-fA-F]{6}"></span>
							<label for="ara-fg"><?php esc_html_e( 'Text', 'air-raid-alert-ua' ); ?></label>
							<span class="ara-color-input">#<input type="text" id="ara-fg" name="ara_settings[fg]" value="<?php echo esc_attr( ltrim( $settings['fg'], '#' ) ); ?>" maxlength="6" pattern="[0-9a-fA-F]{6}"></span>
						</p>
						<p>
							<label for="ara-accent"><?php esc_html_e( 'Accent', 'air-raid-alert-ua' ); ?></label>
							<span class="ara-color-input">#<input type="text" id="ara-accent" name="ara_settings[accent]" value="<?php echo esc_attr( ltrim( $settings['accent'], '#' ) ); ?>" maxlength="6" pattern="[0-9a-fA-F]{6}"></span>
							<label for="ara-red"><?php esc_html_e( 'Alert', 'air-raid-alert-ua' ); ?></label>
							<span class="ara-color-input">#<input type="text" id="ara-red" name="ara_settings[red]" value="<?php echo esc_attr( ltrim( $settings['red'], '#' ) ); ?>" maxlength="6" pattern="[0-9a-fA-F]{6}"></span>
							<label for="ara-yellow"><?php esc_html_e( 'Warning', 'air-raid-alert-ua' ); ?></label>
							<span class="ara-color-input">#<input type="text" id="ara-yellow" name="ara_settings[yellow]" value="<?php echo esc_attr( ltrim( $settings['yellow'], '#' ) ); ?>" maxlength="6" pattern="[0-9a-fA-F]{6}"></span>
						</p>
						<p class="description">
							<?php esc_html_e( 'Enter six-digit hex values without the # sign.', 'air-raid-alert-ua' ); ?>
						</p>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="ara-custom-css"><?php esc_html_e( 'Custom CSS', 'air-raid-alert-ua' ); ?></label>
				</th>
				<td>
					<textarea id="ara-custom-css" name="ara_settings[custom_css]" rows="8" class="large-text code" spellcheck="false"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Added inline after the plugin stylesheet. Use .ara-widget to scope rules. Scripts, @import, and closing style tags are removed.', 'air-raid-alert-ua' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Fallback and attribution', 'air-raid-alert-ua' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Image fallback', 'air-raid-alert-ua' ); ?></th>
				<td>
					<label for="ara-fallback">
						<input type="checkbox" id="ara-fallback" name="ara_settings[fallback_image]" value="1" <?php checked( ! empty( $settings['fallback_image'] ) ); ?>>
						<?php esc_html_e( 'Use the /widget-image SVG fallback instead of an iframe.', 'air-raid-alert-ua' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Useful when the site Content-Security-Policy blocks frames. Shortcode and block instances can still choose the other mode.', 'air-raid-alert-ua' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Attribution', 'air-raid-alert-ua' ); ?></th>
				<td>
					<label for="ara-attribution">
						<input type="checkbox" id="ara-attribution" name="ara_settings[attribution]" value="1" <?php checked( ! empty( $settings['attribution'] ) ); ?>>
						<?php esc_html_e( 'Show the link to alerts.na-agency.com below the widget.', 'air-raid-alert-ua' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'The source site asks embedders to keep this link.', 'air-raid-alert-ua' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Region cache', 'air-raid-alert-ua' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="ara-cache-ttl"><?php esc_html_e( 'Cache lifetime', 'air-raid-alert-ua' ); ?></label>
				</th>
				<td>
					<input type="number" id="ara-cache-ttl" name="ara_settings[cache_ttl]" value="<?php echo esc_attr( (int) $settings['cache_ttl'] ); ?>" min="1" max="168" step="1" class="small-text">
					<?php esc_html_e( 'hours', 'air-raid-alert-ua' ); ?>
					<p class="description">
						<?php esc_html_e( 'The region list is scraped server-side and cached. A daily WP-Cron job refreshes it in the background.', 'air-raid-alert-ua' ); ?>
					</p>
				</td>
			</tr>
			<tr data-ara-inherit-ignore="1">
				<th scope="row"><?php esc_html_e( 'Source status', 'air-raid-alert-ua' ); ?></th>
				<td>
					<?php if ( ! empty( $last_refresh['fetched'] ) ) : ?>
						<p>
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: date/time, 2: region count */
									__( 'Last refresh: %1$s — %2$d regions cached.', 'air-raid-alert-ua' ),
									wp_date( 'Y-m-d H:i:s', (int) $last_refresh['fetched'] ),
									isset( $last_refresh['count'] ) ? (int) $last_refresh['count'] : 0
								)
							);
							?>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'No remote refresh has been stored yet. The bundled snapshot is used if the source is unreachable.', 'air-raid-alert-ua' ); ?></p>
					<?php endif; ?>
					<a class="button" href="<?php echo esc_url( $refresh_url ); ?>"><?php esc_html_e( 'Refresh region list now', 'air-raid-alert-ua' ); ?></a>
					<p class="description">
						<?php esc_html_e( 'The refresh runs once, immediately, using the same parser as the daily job.', 'air-raid-alert-ua' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Data', 'air-raid-alert-ua' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr data-ara-inherit-ignore="1">
				<th scope="row"><?php esc_html_e( 'Uninstall', 'air-raid-alert-ua' ); ?></th>
				<td>
					<label for="ara-delete-data">
						<input type="checkbox" id="ara-delete-data" name="ara_settings[delete_data]" value="1" <?php checked( ! empty( $settings['delete_data'] ) ); ?>>
						<?php esc_html_e( 'Delete settings and cached regions when the plugin is uninstalled.', 'air-raid-alert-ua' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'On multisite, each site controls its own settings. The network page controls shared network defaults; region caches are refreshed per site.', 'air-raid-alert-ua' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save settings', 'air-raid-alert-ua' ) ); ?>
	</form>

	<hr>

	<h2><?php esc_html_e( 'How to embed', 'air-raid-alert-ua' ); ?></h2>
	<p><?php esc_html_e( 'The plugin settings are the defaults. Each shortcode, block, or classic widget can override region, type, size, theme, fallback, and attribution.', 'air-raid-alert-ua' ); ?></p>

	<h3><?php esc_html_e( 'Shortcode', 'air-raid-alert-ua' ); ?></h3>
	<pre class="ara-code">[air_raid_alert]</pre>
	<pre class="ara-code">[air_raid_alert region="68" type="card" size="m" theme="auto"]</pre>
	<pre class="ara-code">[air_raid_alert type="ua" size="l"]</pre>
	<pre class="ara-code">[air_raid_alert region="68" fallback="1" attribution="0"]</pre>

	<h3><?php esc_html_e( 'Gutenberg', 'air-raid-alert-ua' ); ?></h3>
	<p><?php esc_html_e( 'Add the “Air Raid Alert” block from the Widgets category. Its inspector exposes the same options.', 'air-raid-alert-ua' ); ?></p>

	<h3><?php esc_html_e( 'Classic widget', 'air-raid-alert-ua' ); ?></h3>
	<p><?php esc_html_e( 'Appearance → Widgets → Air Raid Alert. Leave a field on “Use plugin setting” to inherit the value from this page.', 'air-raid-alert-ua' ); ?></p>

	<h3><?php esc_html_e( 'Theme template', 'air-raid-alert-ua' ); ?></h3>
	<pre class="ara-code">&lt;?php echo ara_widget( array( 'region' =&gt; 68, 'type' =&gt; 'card' ) ); ?&gt;</pre>

	<h3><?php esc_html_e( 'CSP note', 'air-raid-alert-ua' ); ?></h3>
	<p>
		<?php esc_html_e( 'The iframe requires frame-src https://alerts.na-agency.com; the image fallback requires img-src https://alerts.na-agency.com. If frames are blocked, enable the image fallback or adjust the Content-Security-Policy.', 'air-raid-alert-ua' ); ?>
	</p>

	<h3><?php esc_html_e( 'Privacy note', 'air-raid-alert-ua' ); ?></h3>
	<p>
		<?php esc_html_e( 'Embedding the iframe or image requests content from alerts.na-agency.com, which receives the visitor IP address, user agent, and referring origin as part of that request. The source site states that it does not use cookies or analytics.', 'air-raid-alert-ua' ); ?>
	</p>
</div>
