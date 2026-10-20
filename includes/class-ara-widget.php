<?php
/**
 * Classic widget.
 *
 * @package AirRaidAlertUA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Air Raid Alert classic widget.
 */
class ARA_Widget extends WP_Widget {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action(
			'widgets_init',
			static function () {
				register_widget( 'ARA_Widget' );
			}
		);
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'ara_air_raid_alert',
			__( 'Air Raid Alert', 'air-raid-alert-ua' ),
			array(
				'classname'                   => 'ara-air-raid-alert-widget',
				'description'                 => __( 'Embed the live air-raid alert widget.', 'air-raid-alert-ua' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $sidebar_args Sidebar arguments.
	 * @param array $instance     Widget instance.
	 * @return void
	 */
	public function widget( $sidebar_args, $instance ) {
		$args = array();

		if ( ! empty( $instance['region'] ) && is_scalar( $instance['region'] ) ) {
			$args['region'] = absint( $instance['region'] );
		}
		if ( ! empty( $instance['type'] ) && is_scalar( $instance['type'] ) ) {
			$args['type'] = sanitize_key( (string) $instance['type'] );
		}
		if ( ! empty( $instance['size'] ) && is_scalar( $instance['size'] ) ) {
			$args['size'] = sanitize_key( (string) $instance['size'] );
		}
		if ( ! empty( $instance['theme'] ) && is_scalar( $instance['theme'] ) ) {
			$args['theme'] = sanitize_key( (string) $instance['theme'] );
		}
		if ( isset( $instance['fallback_image'] ) ) {
			$args['fallback_image'] = (int) $instance['fallback_image'];
		}
		if ( isset( $instance['attribution'] ) ) {
			$args['attribution'] = (int) $instance['attribution'];
		}

		$html = ARA_Renderer::render( $args );

		if ( '' === $html ) {
			return;
		}

		echo $sidebar_args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $instance['title'] ) ) {
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			echo $sidebar_args['before_title'] . esc_html( $title ) . $sidebar_args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo $sidebar_args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Admin form.
	 *
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title'          => '',
				'region'         => 0,
				'type'           => '',
				'size'           => '',
				'theme'          => '',
				'fallback_image' => (int) ara_get_setting( 'fallback_image', 0 ),
				'attribution'    => (int) ara_get_setting( 'attribution', 1 ),
			)
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'air-raid-alert-ua' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
				value="<?php echo esc_attr( $instance['title'] ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'region' ) ); ?>">
				<?php esc_html_e( 'Region:', 'air-raid-alert-ua' ); ?>
			</label>
			<select class="widefat ara-region-select" id="<?php echo esc_attr( $this->get_field_id( 'region' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'region' ) ); ?>">
				<?php echo ARA_Regions::get_select_options_html( absint( $instance['region'] ), __( 'Use plugin setting', 'air-raid-alert-ua' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</select>
		</p>

		<?php
		$this->select_field(
			'type',
			__( 'Type:', 'air-raid-alert-ua' ),
			array(
				'card'  => __( 'Card', 'air-raid-alert-ua' ),
				'line'  => __( 'Line', 'air-raid-alert-ua' ),
				'map'   => __( 'Map', 'air-raid-alert-ua' ),
				'stats' => __( 'Stats', 'air-raid-alert-ua' ),
				'ua'    => __( 'Whole Ukraine', 'air-raid-alert-ua' ),
			),
			$instance['type']
		);

		$this->select_field(
			'size',
			__( 'Size:', 'air-raid-alert-ua' ),
			array(
				's' => __( 'Small', 'air-raid-alert-ua' ),
				'm' => __( 'Medium', 'air-raid-alert-ua' ),
				'l' => __( 'Large / 100%', 'air-raid-alert-ua' ),
			),
			$instance['size']
		);

		$this->select_field(
			'theme',
			__( 'Theme:', 'air-raid-alert-ua' ),
			array(
				'auto'  => __( 'Auto (system)', 'air-raid-alert-ua' ),
				'light' => __( 'Light', 'air-raid-alert-ua' ),
				'dark'  => __( 'Dark', 'air-raid-alert-ua' ),
			),
			$instance['theme']
		);
		?>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( ! empty( $instance['fallback_image'] ) ); ?>
				id="<?php echo esc_attr( $this->get_field_id( 'fallback_image' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'fallback_image' ) ); ?>" value="1">
			<label for="<?php echo esc_attr( $this->get_field_id( 'fallback_image' ) ); ?>">
				<?php esc_html_e( 'Use image fallback (CSP-safe)', 'air-raid-alert-ua' ); ?>
			</label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( ! empty( $instance['attribution'] ) ); ?>
				id="<?php echo esc_attr( $this->get_field_id( 'attribution' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'attribution' ) ); ?>" value="1">
			<label for="<?php echo esc_attr( $this->get_field_id( 'attribution' ) ); ?>">
				<?php esc_html_e( 'Show source attribution', 'air-raid-alert-ua' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Save widget instance.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Old values.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title'] = isset( $new_instance['title'] ) && is_scalar( $new_instance['title'] ) ? sanitize_text_field( (string) $new_instance['title'] ) : '';

		$instance['region'] = isset( $new_instance['region'] ) && is_scalar( $new_instance['region'] ) ? absint( $new_instance['region'] ) : 0;

		$instance['type'] = isset( $new_instance['type'] ) && is_scalar( $new_instance['type'] ) ? sanitize_key( (string) $new_instance['type'] ) : '';
		if ( ! in_array( $instance['type'], ARA_Settings::types(), true ) ) {
			$instance['type'] = '';
		}

		$instance['size'] = isset( $new_instance['size'] ) && is_scalar( $new_instance['size'] ) ? sanitize_key( (string) $new_instance['size'] ) : '';
		if ( ! in_array( $instance['size'], ARA_Settings::sizes(), true ) ) {
			$instance['size'] = '';
		}

		$instance['theme'] = isset( $new_instance['theme'] ) && is_scalar( $new_instance['theme'] ) ? sanitize_key( (string) $new_instance['theme'] ) : '';
		if ( ! in_array( $instance['theme'], ARA_Settings::themes(), true ) ) {
			$instance['theme'] = '';
		}

		$instance['fallback_image'] = empty( $new_instance['fallback_image'] ) ? 0 : 1;
		$instance['attribution']    = empty( $new_instance['attribution'] ) ? 0 : 1;

		return $instance;
	}

	/**
	 * Render a select field.
	 *
	 * @param string $key     Field key.
	 * @param string $label   Field label.
	 * @param array  $choices Choices.
	 * @param string $value   Current value.
	 * @return void
	 */
	private function select_field( $key, $label, $choices, $value ) {
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>">
				<?php echo esc_html( $label ); ?>
			</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>">
				<option value="" <?php selected( '', $value ); ?>><?php esc_html_e( 'Use plugin setting', 'air-raid-alert-ua' ); ?></option>
				<?php foreach ( $choices as $choice_value => $choice_label ) : ?>
					<option value="<?php echo esc_attr( $choice_value ); ?>" <?php selected( $choice_value, $value ); ?>>
						<?php echo esc_html( $choice_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}
}
