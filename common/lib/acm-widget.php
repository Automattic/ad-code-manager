<?php
/**
 * ACM custom widget to choose ad tags to display in widget areas.
 *
 * @since v0.2
 */

class ACM_Ad_Zones extends WP_Widget {

	// Process the new widget
	function ACM_Ad_Zones() {
		$widget_ops = array(
			'classname' => 'acm_ad_zones',
			'description' => 'Display Ad Code Manger ad zones in widget areas'
			);
		$this->WP_Widget( 'ACM_Ad_Zones', 'Ad Code Manager Ad Zones', $widget_ops );
	}

	 // Build the widget settings form
	function form( $instance ) {
		$defaults = array( 'title' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = $instance['title'];
		$zone = $instance['ad_zone'];
		global $ad_code_manager;
			?>
			<p><label>Title: <input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>"  type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id( 'ad_zone' ); ?>">Choose Ad Zone</label>
			<select id="<?php echo $this->get_field_id( 'ad_zone' ); ?>" name="<?php echo $this->get_field_name( 'ad_zone' ); ?>">
				<?php
				foreach ( $ad_code_manager->ad_codes as $key => $value ) {
					?>
					<option value="<?php echo $key; ?>" <?php selected( $key, $zone); ?>><?php echo $key; ?></option>
					<?php
				}
				?>
			</select></p>

		<?php
	}

	// Save the widget settings
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['ad_zone'] = sanitize_text_field( $new_instance['ad_zone'] );
		return $instance;
	}

	// Display the widget
	function widget( $args, $instance ) {
		extract( $args );
		echo $before_widget;
		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( !empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}
		do_action( 'acm_tag', $instance['ad_zone'] );
		echo $after_widget;
	}
}