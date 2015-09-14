<?php
/**
 * checkbox input type
 */
class GravityView_FieldType_checkbox extends GravityView_FieldType {

	function render_option() {
		?>
		<label for="<?php echo $this->get_field_id(); ?>" class="<?php echo $this->get_label_class(); ?>">
			<?php $this->render_input(); ?>
			&nbsp;<?php echo $this->get_field_label() . $this->get_tooltip() . $this->get_field_desc(); ?>
		</label>
		<?php
	}

	function render_setting( $override_input = NULL ) {

		if( $this->get_field_left_label() ) : ?>

			<td scope="row">
				<label for="<?php echo $this->get_field_id(); ?>">
					<?php echo $this->get_field_left_label() . $this->get_tooltip(); ?>
				</label>
			</td>
			<td>
				<label>
					<?php $this->render_input( $override_input ); ?>
					&nbsp;<?php echo $this->get_field_label() . $this->get_tooltip() . $this->get_field_desc(); ?>
				</label>
			</td>

		<?php else: ?>

			<td scope="row" colspan="2">
				<label for="<?php echo $this->get_field_id(); ?>">
					<?php $this->render_input( $override_input ); ?>
					&nbsp;<?php echo $this->get_field_label() . $this->get_tooltip() . $this->get_field_desc(); ?>
				</label>
			</td>

		<?php endif;
	}

	function render_input( $override_input = NULL ) {
		if( isset( $override_input ) ) {
			echo $override_input;
			return;
		}

		if( !empty( $this->field['options'] )) {
			?>
			<ul class="gv-label-checkboxes">
			<?php
			foreach ( $this->field['options'] as $value => $label ) { ?>
				<li><input name="<?php printf( '%s[%s]', esc_attr( $this->name ), esc_attr( $value ) ); ?>" value="0" type="hidden" />
					<label class="<?php echo $this->get_label_class(); ?>">
						<input name="<?php printf( '%s[%s]', esc_attr( $this->name ), esc_attr( $value ) ); ?>"
						       id="<?php echo $this->get_field_id(); ?>-<?php echo esc_attr( $value ); ?>" type="checkbox"
						       value="1" <?php checked( ! empty( $this->value[ $value ] ), true, true ); ?> />&nbsp;<?php echo esc_html( $label ); ?>
					</label>
				</li>
				<?php
			}
			?>
			</ul>
			<?php
		} else {
			?>
			<input name="<?php echo esc_attr( $this->name ); ?>" type="hidden" value="0"/>
			<input name="<?php echo esc_attr( $this->name ); ?>" id="<?php echo $this->get_field_id(); ?>"
			       type="checkbox" value="1" <?php checked( $this->value, '1', true ); ?> />
			<?php
		}
	}

}
