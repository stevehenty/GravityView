<?php
/**
 * GravityView default widgets and generic widget class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



/**
 * Widget to display pagination info
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_Pagination_Info extends GravityView_Widget {

	/**
	 * Does this get displayed on a single entry?
	 * @var boolean
	 */
	protected $show_on_single = false;

	function __construct() {
		$default_values = array( 'header' => 1, 'footer' => 1 );
		$settings = array();
		parent::__construct( __( 'Show Pagination Info', 'gravity-view' ) , 'page_info', $default_values, $settings );
	}

	public function render_frontend( $widget_args, $content = '', $context = '') {
		global $gravityview_view;

		if( empty( $gravityview_view )) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend]: $gravityview_view not instantiated yet.', get_class($this)) );
			return;
		}

		$offset = $gravityview_view->paging['offset'];
		$page_size = $gravityview_view->paging['page_size'];
		$total = $gravityview_view->total_entries;


		// displaying info
		if( $total == 0 ) {
			$first = $last = 0;
		} else {
			$first = empty( $offset ) ? 1 : $offset + 1;
			$last = $offset + $page_size > $total ? $total : $offset + $page_size;
		}

		$output = '<div class="gv-widget-pagination"><p>'. sprintf(__( 'Displaying %1$s - %2$s of %3$s', 'gravity-view' ), $first , $last , $total ) . '</p></div>';

		echo apply_filters( 'gravityview_pagination_output', $output, $first, $last, $total );

	}

} // GravityView_Widget_Pagination_Info



/**
 * Widget to display page links
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_Page_Links extends GravityView_Widget {

	protected $show_on_single = false;

	function __construct() {
		$default_values = array( 'header' => 1, 'footer' => 1 );
		$settings = array( 'show_all' => array(
			'type' => 'checkbox',
			'label' => __( 'Show each page number', 'gravity-view' ),
			'desc' => __('Show every page number instead of summary (eg: 1 2 3 ... 8 »)', 'gravity-view'),
			'default' => false
		));
		parent::__construct( __( 'Show Page Links', 'gravity-view' ) , 'page_links', $default_values, $settings );

	}

	public function render_frontend( $widget_args, $content = '', $context = '') {
		global $gravityview_view, $post;

		if( empty( $gravityview_view )) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend]: $gravityview_view not instantiated yet.', get_class($this)) );

			return;
		}

		$page_size = $gravityview_view->paging['page_size'];
		$total = $gravityview_view->total_entries;

		$atts = shortcode_atts( array(
			'show_all' => !empty( $this->settings['show_all']['default'] ),
		), $widget_args, 'gravityview_widget_page_links' );

		// displaying info
		$curr_page = empty( $_GET['pagenum'] ) ? 1 : intval( $_GET['pagenum'] );

		$page_links = array(
			'base' => add_query_arg('pagenum','%#%'),
			'format' => '&pagenum=%#%',
			'add_args' => array(), //
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'type' => 'list',
			'total' => empty( $page_size ) ? 0 : ceil( $total / $page_size ),
			'current' => $curr_page,
			'show_all' => !empty( $atts['show_all'] ), // to be available at backoffice
		);

		$page_links = paginate_links( $page_links );

		if(!empty($page_links)) {
			echo '<div class="gv-widget-page-links">'. $page_links .'</div>';
		}

	}

} // GravityView_Widget_Page_Links






/**
 * Widget to display search bar (free search, field and date filters)
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_Search_Bar extends GravityView_Widget {

	private $search_filters = array();

	function __construct() {
		$default_values = array( 'header' => 0, 'footer' => 0 );

		$settings = array(
			'search_free' => array(
				'type' => 'checkbox',
				'label' => __( 'Show search input', 'gravity-view' ),
				'default' => true
			),
			'search_date' => array(
				'type' => 'checkbox',
				'label' => __( 'Show date filters', 'gravity-view' ),
				'default' => false
			),
		);
		parent::__construct( __( 'Show Search Bar', 'gravity-view' ) , 'search_bar', $default_values, $settings );

		add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ) );

		// add field options (specific for this widget)
		add_filter( 'gravityview_template_field_options', array( $this, 'assign_field_options' ), 10, 4 );
	}

	function assign_field_options( $field_options, $template_id, $field_id, $context ) {

		if($context !== 'single' && $field_id !== 'entry_link' ) {
			$field_options = array_merge( $field_options, array(
			'search_filter' => array(
				'type' => 'checkbox',
				'label' => __( 'Use this field as a search filter', 'gravity-view' ),
				'default' => false
			)) );
		}

		return $field_options;
	}

	function filter_entries( $search_criteria ) {

		// add free search
		if( !empty( $_GET['gv_search'] ) ) {
			$search_criteria['field_filters'][] = array(
				'key' => null, // The field ID to search
				'value' => esc_attr(rgget('gv_search')), // The value to search
				'operator' => 'contains', // What to search in. Options: `is` or `contains`
			);
		}

		// add specific fields search
		$search_filters = $this->get_search_filters();
		if( !empty( $search_filters ) && is_array( $search_filters ) ) {
			foreach( $search_filters as $k => $filter ) {
				if( !empty( $filter['value'] ) ) {
					$search_criteria['field_filters'][] = $filter;
				}
			}
		}

		//start date & end date
		$curr_start = esc_attr(rgget('gv_start'));
		$curr_end = esc_attr(rgget('gv_end'));
		if( !empty( $curr_start ) && !empty( $curr_end ) ) {
			$search_criteria['start_date'] = $curr_start;
			$search_criteria['end_date'] = $curr_end;
		}

		return $search_criteria;
	}


	public function render_frontend( $widget_args, $content = '', $context = '') {
		global $gravityview_view;

		if( empty( $gravityview_view )) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend]: $gravityview_view not instantiated yet.', get_class($this)) );
			return;
		}

		// get configured search filters (fields)
		$gravityview_view->search_fields = $this->render_search_fields();


		$atts = shortcode_atts( array(
			'search_date' => !empty( $this->settings['search_date']['default'] ),
			'search_free' => !empty( $this->settings['search_free']['default'] )
		), $widget_args, 'gravityview_widget_search_bar' );

		$gravityview_view->search_free = $atts['search_free'];
		$gravityview_view->search_date = $atts['search_date'];

		if( !empty( $gravityview_view->search_date ) ) {

			// enqueue datepicker stuff only if needed!
			$scheme = is_ssl() ? 'https://' : 'http://';
			wp_enqueue_style( 'jquery-ui-datepicker', $scheme.'ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css' );
			wp_enqueue_script( 'gform_datepicker_init' );

			$datepicker_class = apply_filters( 'gravityview_search_datepicker_class', 'gv-datepicker datepicker ymd-dash' );
			$gravityview_view->datepicker_class = $datepicker_class;
		}

		// Search box and filters
		$gravityview_view->curr_search = esc_attr(rgget('gv_search'));
		$gravityview_view->curr_start = esc_attr(rgget('gv_start'));
		$gravityview_view->curr_end = esc_attr(rgget('gv_end'));

		$gravityview_view->render('widget', 'search', false );
	}

	function render_search_fields() {
		global $gravityview_view;

		$output = '';

		if( $search_filters = $this->get_search_filters() ) {
			$form = gravityview_get_form( $gravityview_view->form_id );
			foreach( $search_filters as $filter ) {
				$field = gravityview_get_field( $form, $filter['key'] );

				if( in_array( $field['type'] , array( 'select', 'checkbox', 'radio', 'post_category' ) ) ) {

					// post_category specifics
					if( !empty( $field['displayAllCategories'] ) && empty( $field['choices'] ) ) {
						$field['choices'] = self::get_post_categories_choices();
					}

					if( 'post_category' === $field['type'] && !empty( $filter['value'] ) ) {
						$value = explode( ':', $filter['value'] );
						$filter['value'] = !empty( $value[1] ) ? $value[1] : '';
					}

					$output .= self::render_search_dropdown( $field['label'], 'filter_'.$field['id'], $field['choices'], $filter['value'] ); //Label, name attr, choices
				} else {
					if(empty($field)) {
						$output .= self::render_search_input( $filter['label'], 'filter_'.$filter['key'], $filter['value'] ); //label, attr name
					} else {
						$output .= self::render_search_input( $field['label'], 'filter_'.$field['id'], $filter['value'] ); //label, attr name
					}
				}
			}
		}

		return $output;
	}

	/**
	 * render_search_dropdown function.
	 *
	 * @access private
	 * @static
	 * @param string $label (default: '')
	 * @param string $name (default: '')
	 * @param mixed $choices
	 * @param string $current_value (default: '')
	 * @return void
	 */
	static private function render_search_dropdown( $label = '', $name = '', $choices, $current_value = '' ) {

		if( empty( $choices ) || !is_array( $choices ) || empty( $name ) ) {
			return '';
		}

		$output = '<div class="gv-search-box">';
		$output .= '<label for=search-box-'.$name.'>' . $label . '</label>';
		$output .= '<p><select name="'.$name.'" id="search-box-'. $name.'">';
		$output .= '<option value="" '. selected( '', $current_value, false ) .'>---</option>';
		foreach( $choices as $choice ) {
			$output .= '<option value="'. $choice['value'] .'" '. selected( $choice['value'], $current_value, false ) .'>'. $choice['text'] .'</option>';
		}
		$output .= '</select></p>';
		$output .= '</div>';

		return $output;

	}

	static private function get_post_categories_choices() {
		$args = array(
			'type'                     => 'post',
			'child_of'                 => 0,
			'orderby'                  => 'name',
			'order'                    => 'ASC',
			'hide_empty'               => 0,
			'hierarchical'             => 1,
			'taxonomy'                 => 'category',
		);
		$categories = get_categories( $args );

		if( empty( $categories ) ) {
			return array();
		}

		$choices = array();

		foreach( $categories as $category ) {
			$choices[] = array( 'text' => $category->name, 'value' => $category->term_id );
		}

		return $choices;
	}


	/**
	 * render_search_input function.
	 *
	 * @access private
	 * @static
	 * @param string $label (default: '')
	 * @param string $name (default: '')
	 * @param string $current_value (default: '')
	 * @return void
	 */
	static private function render_search_input( $label = '', $name = '', $current_value = '' ) {

		if( empty( $name ) ) {
			return '';
		}

		$output = '<div class="gv-search-box">';
		$output .= '<label for=search-box-'. $name .'>' . $label . '</label>';
		$output .= '<p><input type="text" name="'. $name .'" id="search-box-'. $name .'" value="'. $current_value .'"></p>';
		$output .= '</div>';

		return $output;

	}

	private function get_search_filters() {
		if( !empty( $this->search_filters ) ) {
			return $this->search_filters;
		}

		global $gravityview_view;

		// get configured search filters (fields)
		$search_filters = array();
		$view_fields = $gravityview_view->fields;
		$form = gravityview_get_form( $gravityview_view->form_id );

		if( !empty( $view_fields ) && is_array( $view_fields ) ) {
			foreach( $view_fields as $t => $fields ) {
				foreach( $fields as $field ) {
					if( !empty( $field['search_filter'] ) ) {
						$value = esc_attr(rgget('filter_'. $field['id']));
						$form_field = gravityview_get_field( $form, $field['id'] );

						// convert value (category_id) into 'name:id'
						if( 'post_category' === $form_field['type'] && !empty( $value ) ) {
							$cat = get_term( $value, 'category' );
							$value = esc_attr( $cat->name ) . ':' . $value;

						}

						$search_filters[] = array( 'key' => $field['id'], 'label' => $field['label'], 'value' => $value );
					}
				}
			}
		}

		$this->search_filters = $search_filters;

		return $search_filters;
	}




} // GravityView_Widget_Page_Links





/**
 * Main GravityView widget class
 */
class GravityView_Widget {

	/**
	 * Widget admin label
	 * @var string
	 */
	protected $widget_label;

	/**
	 * Widget admin id
	 * @var string
	 */
	protected $widget_id;

	/**
	 * default configuration for header and footer
	 * @var array
	 */
	protected $defaults;

	/**
	 * Widget admin advanced settings
	 * @var array
	 */
	protected $settings;

	/**
	 * allow class to automatically add widget_text filter for you in shortcode
	 * @var string
	 */
	protected $shortcode_name;

	// hold widget View options
	private $widget_options;

	function __construct( $widget_label , $widget_id , $defaults = array(), $settings = array() ) {


		/**
		 * The shortcode name is set to the lowercase name of the widget class, unless overridden by the class specifying a different value for $shortcode_name
		 * @var string
		 */
		$this->shortcode_name = !isset( $this->shortcode_name ) ? strtolower( get_class($this) ) : $this->shortcode_name;

		$this->widget_label = $widget_label;
		$this->widget_id = $widget_id;
		$this->defaults = array_merge( array( 'header' => 0, 'footer' => 0 ), $defaults );
		$this->settings = $settings;

		// register widgets to be listed in the View Configuration
		add_filter( 'gravityview_register_directory_widgets', array( $this, 'register_widget') );

		// widget options
		add_filter( 'gravityview_template_widget_options', array( $this, 'assign_widget_options' ), 10, 3 );

		// frontend logic
		add_action( "gravityview_render_widget_{$widget_id}", array( $this, 'render_frontend' ), 10, 1 );

		// register shortcodes
		add_action( 'wp', array( $this, 'add_shortcode') );

		// Use shortcodes in text widgets.
		add_filter('widget_text', array( $this, 'maybe_do_shortcode' ) );
	}

	/**
	 * Do shortcode if the Widget's shortcode exists.
	 * @param  string $text   Widget text to check
	 * @param  null|WP_Widget Empty if not called by WP_Widget, or a WP_Widget instance
	 * @return string         Widget text
	 */
	function maybe_do_shortcode( $text, $widget = NULL ) {

		if( !empty( $this->shortcode_name ) && has_shortcode( $text, $this->shortcode_name ) ) {
			return do_shortcode( $text );
		}

		return $text;
	}

	function render_shortcode( $atts, $content = '', $context = '' ) {

		ob_start();

		$this->render_frontend( $atts, $content, $context );

		return ob_get_clean();
	}

	/**
	 * Add $this->shortcode_name shortcode to output self::render_frontend()
	 */
	function add_shortcode( $run_on_singular = true ) {
		global $gravityview_view, $post;

		if( is_admin() ) { return; }

		if( empty( $this->shortcode_name ) ) { return; }

		// If the widget shouldn't output on single entries, don't show it
		if( empty( $this->show_on_single ) && class_exists('GravityView_frontend') && GravityView_frontend::is_single_entry() ) {
			do_action('gravityview_log_debug', sprintf( '%s[add_shortcode]: Skipping; set to not run on single entry.', get_class($this)) );

			add_shortcode( $this->shortcode_name, '__return_null' );
			return;
		}


		if( !has_gravityview_shortcode( $post ) ) {

			do_action('gravityview_log_debug', sprintf( '%s[add_shortcode]: No shortcode present; not adding render_frontend shortcode.', get_class($this)) );

			add_shortcode( $this->shortcode_name, '__return_null' );
			return;
		}

		add_shortcode( $this->shortcode_name, array( $this, 'render_shortcode') );
	}

	/**
	 * Register widget to become available in admin
	 * @param  array $widgets
	 * @return array $widgets
	 */
	function register_widget( $widgets ) {
		$widgets[ $this->widget_id ] = array( 'label' => $this->widget_label );
		return $widgets;
	}

	/**
	 * Assign template specific field options
	 *
	 * @access protected
	 * @param array $options (default: array())
	 * @param string $template (default: '')
	 * @return void
	 */
	public function assign_widget_options( $options = array(), $template = '', $widget = '' ) {

		if( $this->widget_id === $widget ) {
			$options = array_merge( $options, $this->settings );
		}

		return $options;
	}


	/** Frontend logic */
	public function render_frontend( $widget_args, $content = '', $context = '') {
		// to be defined by child class
	}


} // GravityView_Widget

new GravityView_Widget_Pagination_Info;
new GravityView_Widget_Page_Links;
new GravityView_Widget_Search_Bar;
