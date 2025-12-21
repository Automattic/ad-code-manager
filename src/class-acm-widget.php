<?php
/**
 * ACM custom widget to choose ad tags to display in widget areas.
 *
 * @since v0.2
 */

class ACM_Ad_Zones extends WP_Widget {

	// Process the new widget
	function __construct() {
		$widget_ops = array(
			'classname'   => 'acm_ad_zones',
			'description' => __( 'Display an Ad Code Manager ad zone within a widget area', 'ad-code-manager' ),
		);
		parent::__construct( 'ACM_Ad_Zones', __( 'Ad Code Manager Ad Zone', 'ad-code-manager' ), $widget_ops );
	}

	// Build the widget settings form
	function form( $instance ) {
		$defaults = array(
			'title'   => '',
			'ad_zone' => '',
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title    = $instance['title'];
		$zone     = $instance['ad_zone'];
		global $ad_code_manager;
		?>
			<p><label>Title: <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"  type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>

			<p>
			<?php if ( ! empty( $ad_code_manager->ad_codes ) ) : ?>
			<label for="<?php echo esc_attr( $this->get_field_id( 'ad_zone' ) ); ?>">Choose Ad Zone</label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'ad_zone' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'ad_zone' ) ); ?>">
				<?php
				foreach ( $ad_code_manager->ad_codes as $key => $value ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $zone ); ?>><?php echo esc_html( $key ); ?></option>
					<?php
				}
				?>
			</select>
			<?php else : ?>
				<?php $create_url = add_query_arg( 'page', $ad_code_manager->plugin_slug, admin_url( 'options-general.php' ) ); ?>
			<span class="description"><?php
				// translators: %s is the URL to the Ad Code Manager settings page.
				echo sprintf( __( "No ad codes have been added yet. <a href='%s'>Please create one</a>.", 'ad-code-manager' ), esc_url( $create_url ) );
			?></span>
			<?php endif; ?>
			</p>

		<?php
	}

	// Save the widget settings
	function update( $new_instance, $old_instance ) {
		$instance            = $old_instance;
		$instance['title']   = sanitize_text_field( $new_instance['title'] );
		$instance['ad_zone'] = sanitize_text_field( $new_instance['ad_zone'] );
		return $instance;
	}

	// Display the widget
	function widget( $args, $instance ) {
		// Capture the ad content to check if we have anything to display.
		// This fixes issue #72: don't output empty widget wrapper when no ad code found.
		ob_start();
		do_action( 'acm_tag', $instance['ad_zone'] );
		$ad_content = ob_get_clean();

		/**
		 * Filters whether to display the widget when no ad content is found.
		 *
		 * @since 0.8.0
		 *
		 * @param bool   $display     Whether to display the widget. Default false.
		 * @param string $ad_zone     The ad zone ID.
		 * @param array  $args        Widget display arguments.
		 * @param array  $instance    Widget instance settings.
		 */
		if ( empty( $ad_content ) && ! apply_filters( 'acm_display_empty_widget', false, $instance['ad_zone'], $args, $instance ) ) {
			return;
		}

		echo $args['before_widget'];
		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ad content is escaped during token replacement in get_acm_tag().
		echo $ad_content;
		echo $args['after_widget'];
	}
}
