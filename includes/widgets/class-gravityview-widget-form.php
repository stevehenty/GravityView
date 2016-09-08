<?php

/**
 * Widget to add custom content
 *
 * @since 1.5.4
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_Gravity_Forms_Form extends GravityView_Widget {

	/**
	 * Does this get displayed on a single entry?
	 * @var boolean
	 */
	protected $show_on_single = false;

	function __construct() {
		global $post;

		$widget_label = __( 'Form', 'gravityview' );
		$widget_id = 'form';
		$this->widget_description = __('Insert Gravity Forms form', 'gravityview' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$view_id = empty($post) ? 0 : $post->ID;

		$settings = array(
			'form_id' => array(
				'type' => 'select',
				'label' => __( 'Form ID', 'gravityview' ),
				'desc' => __( 'What Gravity Forms form do you want to show?', 'gravityview' ),
				'options' => $this->get_forms_choices(),
				'value' => gravityview_get_form_id( $view_id ),
			),
			'form_title' => array(
				'type' => 'checkbox',
				'label' => __( 'Title' ),
			    'desc' => __('Display the form title'),
				'value' => '1',
			),
			'form_description' => array(
				'type' => 'checkbox',
				'label' => __( 'Description' ),
				'desc' => __('Display the form description'),
				'value' => '1',
			),
			'form_ajax' => array(
				'type' => 'checkbox',
				'label' => __( 'Enable AJAX' ),
				'desc' => __('Checking this option will enable your form to be submitted via AJAX. Submitting the form via AJAX allows the form to be submitted without requiring a page refresh.'),
				'value' => '',
			),
		);

		parent::__construct( $widget_label , $widget_id, $default_values, $settings );
	}

	private function get_forms_choices() {
		$forms = gravityview_get_forms( true );

		$return = array();

		foreach ( $forms as $form ) {
			$return[ $form['id'] ] = $form['title'];
		}

		return $return;
	}

	public function render_frontend( $widget_args, $content = '', $context = '') {

		if( !$this->pre_render_frontend() ) {
			return;
		}

		$form_id = rgar( $widget_args, 'form_id', 0 );

		if ( empty( $form_id ) ) {
			return;
		}

		$title = ! empty( $widget_args['form_title'] ) ? 'true' : 'false';
		$description = ! empty( $widget_args['form_description'] ) ? 'true' : 'false';
		$ajax = ! empty( $widget_args['form_ajax'] ) ? 'true' : 'false';

		echo do_shortcode( sprintf( '[gravityforms id="%d" title="%s" description="%s" ajax="%s"]', $form_id, $title, $description, $ajax ) );
	}

}

new GravityView_Widget_Gravity_Forms_Form;