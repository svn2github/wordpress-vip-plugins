<?php

	/*
	* Grab the settings from $instance and fill out default
	* values as needed.
	*/
	$widget_id = $this->id;

	$title         = empty( $instance['title'] ) ? ' ' : apply_filters( 'widget_title', esc_attr( $instance['title'] ) );
	$source        = empty( $instance['source'] ) ? get_bloginfo( 'url' ) : esc_attr( $instance['source'] );
	$lo_event_name = empty( $instance['lo_event_name'] ) ? '' : esc_attr( $instance['lo_event_name'] );

if ( ! empty( $instance['sailthru_list'] ) ) {
	if ( is_array( $instance['sailthru_list'] ) ) {
		$sailthru_list = implode( ',', $instance['sailthru_list'] );
	} else {
		$sailthru_list = $instance['sailthru_list'];
	}
} else {
	$sailthru_list = '';
}

	// display options
	$customfields = get_option( 'sailthru_forms_options' );
	$sailthru     = get_option( 'sailthru_setup_options' );
	// nonce
	$nonce = wp_create_nonce( 'add_subscriber_nonce' );

	?>
	<div class="sailthru-signup-widget">
		<span class="sailthru-signup-widget-close"><a href="#sailthru-signup-widget">Close</a></span>
		<div class="sailthru_form">

			<?php
				// title
			if ( ! empty( $title ) ) {
				if ( ! isset( $before_title ) ) {
					$before_title = '';
				}
				if ( ! isset( $after_title ) ) {
					$after_title = '';
				}

				echo  wp_kses_post( $before_title . trim( $title ) . $after_title );
			}

				// success message
			if ( empty( $customfields['sailthru_customfield_success'] ) ) {
				$success = 'Thank you for subscribing!';
			} else {
				$success = $customfields['sailthru_customfield_success'];
			}


			?>

			<div class="success" hidden="hidden"><?php echo esc_html( $success ); ?></div>

			<form method="post" action="#" class="sailthru-add-subscriber-form" id="<?php echo esc_attr( $widget_id ) ?>">

				<div class="sailthru-add-subscriber-errors"></div>

				<div class="sailthru_form_input form-group">
					<label class="sailthru-widget-label">Email</label>
					<input type="text" name="email" value="" class="form-control sailthru_email" />
				</div>

				<?php


					$key = get_option( 'sailthru_forms_key' );


					// figure out display needs and order when using short code
				if ( isset( $instance['using_shortcode'] ) && $instance['using_shortcode'] ) {

					if ( ! empty( $instance['fields'] ) ) {
						$order  = '';
						$fields = explode( ',', $instance['fields'] );
						foreach ( $fields as $field ) {
							$field         = trim( $field );
							$name_stripped = preg_replace( '/[^\da-z]/i', '_', $field );
							$instance[ 'show_' . $name_stripped . '_name' ]     = true;
							$instance[ 'show_' . $name_stripped . '_required' ] = false;
							for ( $i = 1; $i <= $key; $i++ ) {
								if ( isset( $customfields[ $i ] ) ) {
									$db_name_stripped = preg_replace( '/[^\da-z]/i', '_', $customfields[ $i ]['sailthru_customfield_name'] );

									if ( $name_stripped === $db_name_stripped ) {
										$order .= $i . ',';
										break;
									}
								}
							}
						}
						$order_list = explode( ',', $order );
					}
				} else {
					// figure out which fields we need to show when NOT using shortcodde
					foreach ( $instance as $field ) {

						if ( is_array( $field ) ) {
							continue;
						}

						$name_stripped                                      = preg_replace( '/[^\da-z]/i', '_', $field );
						$instance[ 'show_' . $name_stripped . '_name' ]     = true;
						$instance[ 'show_' . $name_stripped . '_required' ] = false;

					} // end foreach


					// determine order of fields.
					if ( empty( $order ) && isset( $instance['field_order'] ) ) {
						$order = $instance['field_order'];
					}

					if ( empty( $order ) ) {
						$order = get_option( 'sailthru_customfield_order' );
					}


					if ( isset( $order ) && '' !== $order ) {
						$order_list = explode( ',', $order );
					}
				}


					// widget is rendered using Appearance > Widget
				if ( isset( $order_list ) ) {

					//for ($j = 0; $j < count($order_list); $j++){
					for ( $i = 0; $i < count( $order_list ); $i++ ) {
						$field_key = (int) $order_list[ $i ];

						if ( ! empty( $customfields[ $field_key ] ) ) {
							$name_stripped = preg_replace( '/[^\da-z]/i', '_', $customfields[ $field_key ]['sailthru_customfield_name'] );


							if ( ! empty( $instance[ 'show_' . $name_stripped . '_name' ] ) ) {

								if ( ! empty( $customfields[ $field_key ]['sailthru_customfield_attr'] ) ) {
									$attributes = $customfields[ $field_key ]['sailthru_customfield_attr'];
								} else {
									$attributes = '';
								}

								echo '<div class="sailthru_form_input form-group">';

								if ( 'select' === $customfields[ $field_key ]['sailthru_customfield_type'] ) {

									echo '<label for="custom_' . esc_attr( $name_stripped ) . '">' . esc_html( $customfields[ $field_key ]['sailthru_customfield_label'] ) . '</label>';
									echo '<select ' . esc_attr( sailthru_field_class( $customfields[ $field_key ]['sailthru_customfield_class'] ) ) . ' ' . esc_attr( sailthru_attributes( $attributes ) ) . 'name="custom_' . esc_attr( $name_stripped ) . '" id="sailthru_' . esc_attr( $name_stripped ) . '_id" class="form-control">';

									$items = explode( ',', $customfields[ $field_key ]['sailthru_customfield_value'] );
									echo '<option value=""></option>';
									foreach ( $items as $item ) {
										if ( ! empty( $item ) ) {
											$vals = explode( ':', $item );
											echo '<option value="' . esc_attr( $vals[1] ) . '">' . esc_attr( $vals[0] ) . '</option>';
										}
									}

									echo '</select>';

								} elseif ( 'radio' === $customfields[ $field_key ]['sailthru_customfield_type'] ) {

									$items = explode( ',', $customfields[ $field_key ]['sailthru_customfield_value'] );
									echo '<div class="radio">';
									echo '<label for="custom_' . esc_attr( $name_stripped ) . '">' . esc_html( $customfields[ $field_key ]['sailthru_customfield_label'] ) . '</label>';

									foreach ( $items as $item ) {
										if ( ! empty( $item ) ) {
											$vals = explode( ':', $item );
											echo '<input ';

											if ( 'checked' === $instance[ 'show_' . esc_attr( $name_stripped ) . '_required' ] ) {
												echo 'required=required ';
											}
											echo 'type="radio" name="custom_' . esc_attr( $name_stripped ) . '" value="' . esc_attr( $vals[1] ) . '" ' . esc_attr( sailthru_field_class( $customfields[ $field_key ]['sailthru_customfield_class'], $customfields[ $field_key ]['sailthru_customfield_type'] ) ) . ' ' . esc_attr( sailthru_attributes( $attributes ) ) . '> ' . esc_html( $vals[0] ) . '';
										}
									}

									echo '</div>';
								} elseif ( 'checkbox' === $customfields[ $field_key ]['sailthru_customfield_type'] ) {
									$items = explode( ',', $customfields[ $field_key ]['sailthru_customfield_value'] );
									
									echo '<div class="checkbox">';
									echo '<label for="custom_' . esc_attr( $name_stripped ) . '">' . esc_html( $customfields[ $field_key ]['sailthru_customfield_label'] ) . '</label>';
									
									foreach ( $items as $item ) {
										
										if ( ! empty( $item ) ) {
											$vals = explode( ':', $item );

											echo '<input ';

											if ( 'checked' === $instance[ 'show_' . esc_attr( $name_stripped ) . '_required' ] ) {
												echo 'required=required ';
											}


											
											echo 'type="checkbox" name="custom_' . esc_attr( $name_stripped ) .  (count( $items) > 1 ? '[]' : '') . '" value="' . esc_attr( $vals[1] ) . '"  ' . esc_attr( sailthru_field_class( $customfields[ $field_key ]['sailthru_customfield_class'], $customfields[ $field_key ]['sailthru_customfield_type'] ) ) . ' ' . esc_attr( sailthru_attributes( $attributes ) ) . '> ' . esc_html( $vals[0] ) . '';
										}
									}
									echo '</div>';

								} else {

									//check if the field is required
									if ( 'checked' === $instance[ 'show_' . $name_stripped . '_required' ] ) {

										echo '<label for="custom_' . esc_attr( $name_stripped ) . '">' . esc_html( $customfields[ $field_key ]['sailthru_customfield_label'] ) . '</label>';

										echo '<input ' . esc_attr( sailthru_field_class( $customfields[ $field_key ]['sailthru_customfield_class'] ) ) . ' type="' . esc_attr( $customfields[ $field_key ]['sailthru_customfield_type'] ) . '" ';

										if ( 'hidden' === $customfields[ $field_key ]['sailthru_customfield_type']  ) {
											echo 'value="' . esc_attr( $customfields[ $field_key ]['sailthru_customfield_value'] ) . '" ';
										}

										echo esc_attr( sailthru_attributes( $attributes ) ) . 'required="required" name="custom_' . esc_attr( $name_stripped ) . '" id="sailthru_' . esc_attr( $name_stripped ) . '_name" class="form-control"/>';

									} else {

										echo '<label for="custom_' . esc_attr( $name_stripped ) . '">' . esc_html( $customfields[ $field_key ]['sailthru_customfield_label'] ) . '</label>';

										echo '<input ';

										if ( 'hidden' === $customfields[ $field_key ]['sailthru_customfield_type'] ) {
											echo 'value="' . esc_attr( $customfields[ $field_key ]['sailthru_customfield_value'] ) . '" ';
										}

										echo esc_attr( sailthru_field_class( $customfields[ $field_key ]['sailthru_customfield_class'] ) ) . ' type="' . esc_attr( $customfields[ $field_key ]['sailthru_customfield_type'] ) . '" ' . esc_attr( sailthru_attributes( $attributes ) ) . 'name="custom_' . esc_attr( $name_stripped ) . '" id="sailthru_' . esc_attr( $name_stripped ) . '_id"  class="form-control"/>';

									}
								}

								echo '</div>'; // end of .sailthru_form_input .form-group

							} //end if !empty name
						} // end if !empty field key
					}// end for loop


					// widget is rendered using shortcode.
					// $order is specified in the way the user listed the fields
					// not by some saved fields_order
				} else {

					for ( $i = 0; $i < $key; $i++ ) {
						$field_key = $i + 1;


						if ( ! empty( $customfields[ $field_key ] ) ) {
							$name_stripped = preg_replace( '/[^\da-z]/i', '_', $customfields[ $field_key ]['sailthru_customfield_name'] );

							if ( ! empty( $instance[ 'show_' . $name_stripped . '_name' ] ) ) {

								if ( ! empty( $customfields[ $field_key ]['sailthru_customfield_attr'] ) ) {
									$attributes = $customfields[ $field_key ]['sailthru_customfield_attr'];
								} else {
									$attributes = '';
								}
							}

							echo '<div class="sailthru_form_input form-group">';

							if ( 'select' === $customfields[ $field_key ]['sailthru_customfield_type'] ) {
								echo '<label for="custom_' . esc_attr( $name_stripped ) . '">' . esc_html( $customfields[ $field_key ]['sailthru_customfield_label'] ) . '</label>
									<select ' . esc_attr( sailthru_field_class( $customfields[ $field_key ]['sailthru_customfield_class'] ) ) . ' ' . esc_attr( sailthru_attributes( $attributes ) ) . 'name="custom_' . esc_attr( $name_stripped ) . '" id="sailthru_' . esc_attr( $name_stripped ) . '_id" class="form-control">';
								$items = explode( ',', $customfields[ $field_key ]['sailthru_customfield_value'] );

								foreach ( $items as $item ) {
									$vals = explode( ':', $item );
									echo '<option value="' . esc_attr( $vals[1] ) . '">' . esc_attr( isset( $vals[0] ) ? $vals[0] : '' ) . '</option>';
								}

								echo '</select>';
							} elseif ( 'radio' === $customfields[ $field_key ]['sailthru_customfield_type'] ) {
								$items = explode( ',', $customfields[ $field_key ]['sailthru_customfield_value'] );
								echo '<div class="radio">';
								echo '<label for="custom_' . esc_attr( $name_stripped ) . '">' . esc_html( $customfields[ $field_key ]['sailthru_customfield_label'] ) . '</label>';

								foreach ( $items as $item ) {
									$vals = explode( ':', $item );
									echo '<input ';

									if ('checked' === $instance[ 'show_' . esc_attr( $name_stripped ) . '_required' ] ) {
										echo 'required=required ';
									}

									echo 'type="radio" name="custom_' . esc_attr( $name_stripped ) . '" value="' . esc_attr( $vals[1] ) . '"> ' . esc_html( isset( $vals[0] ) ? $vals[0] : '' ) . '';
								}

								echo '</div>';
							} elseif ( 'checkbox' === $customfields[ $field_key ]['sailthru_customfield_type'] ) {
								$items = explode( ',', $customfields[ $field_key ]['sailthru_customfield_value'] );

								echo '<div class="checkbox">';
								echo '<label for="custom_' . esc_attr( $name_stripped ) . '">' . esc_html( $customfields[ $field_key ]['sailthru_customfield_label'] ) . '</label>';

								foreach ( $items as $item ) {
									$vals = explode( ':', $item );
									echo '<input ';

									if ( isset( $instance[ 'show_' . esc_attr( $name_stripped ) . '_required' ] ) && 'checked' === $instance[ 'show_' . esc_attr( $name_stripped ) . '_required' ]  ) {
										echo 'required=required ';
									}

									echo 'type="checkbox" name="custom_' . esc_attr( $name_stripped ) .  (count( $items) > 1 ? '[]' : '') . '" value="' . esc_attr( $vals[1] ) . '"> ' . esc_html( isset( $vals[0] ) ? $vals[0] : '' ) . '';
								}

								echo '</div>';
							} else {

								if ( ! empty( $customfields[ $field_key ]['sailthru_customfield_attr'] ) ) {
									$attributes = $customfields[ $field_key ]['sailthru_customfield_attr'];
								} else {
									$attributes = '';
								}

								//check if the field is required
								if ( isset( $instance[ 'show_' . $name_stripped . '_required' ] ) && 'checked' === $instance[ 'show_' . $name_stripped . '_required' ] ) {

									echo '<input ' . esc_attr( sailthru_field_class( $customfields[ $field_key ]['sailthru_customfield_class'] ) ) . ' type="' . esc_attr( $customfields[ $field_key ]['sailthru_customfield_type'] ) . '" ';

									if ( 'hidden' === $customfields[ $field_key ]['sailthru_customfield_type'] ) {
										echo 'value="' . esc_attr( $customfields[ $field_key ]['sailthru_customfield_value'] ) . '" ';
									}

									echo esc_attr( sailthru_attributes( $attributes ) ) . 'required="required" name="custom_' . esc_attr( $name_stripped ) . '" id="sailthru_' . esc_attr( $name_stripped ) . '_name" class="form-control"/>';

								} else {

									echo '<input ';

									if ( 'hidden' === $customfields[ $field_key ]['sailthru_customfield_type'] ) {
										echo 'value="' . esc_attr( $customfields[ $field_key ]['sailthru_customfield_value'] ) . '" ';
									}

									echo esc_attr( sailthru_field_class( $customfields[ $field_key ]['sailthru_customfield_class'] ) ) . ' type="' . esc_attr( $customfields[ $field_key ]['sailthru_customfield_type'] ) . '" ' . esc_attr( sailthru_attributes( $attributes ) ) . 'name="custom_' . esc_attr( $name_stripped ) . '" id="sailthru_' . esc_attr( $name_stripped ) . '_id"  class="form-control"/>';

								}
							}

							echo '</div>'; // end .sailthru_form_input .form-group

						} //end if !empty name
					} // end for
				} // end if there are fields
				?>
				<input type="hidden" name="sailthru_nonce" value="<?php echo esc_attr( $nonce) ; ?>" />
				<input type="hidden" name="sailthru_email_list" value="<?php echo esc_attr( $sailthru_list ); ?>" />
				<input type="hidden" name="action" value="add_subscriber" />
				<input type="hidden" name="source" value="<?php echo esc_attr( $source ); ?>" />
				<input type="hidden" name="lo_event_name" value="<?php echo esc_attr( $lo_event_name ); ?>" />

				<span class="input-group-btn">
					<button class="btn btn-reverse" type="submit">
						Submit
					</button>
				</span>
		</form>
	</div>
</div>
