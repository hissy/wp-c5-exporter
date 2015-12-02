<?php
if ( ! defined( 'WPINC' ) ) exit;

class WP_C5_Exporter_Front {
	
	const QUERY_VAR = 'c5-export';
	const ACTION_EXPORT = 'run-export';
	
	private static $instance;
	private $error;
	
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
			self::$instance->run_init();
		}
		return self::$instance;
	}
	
	public function run_init() {
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'wp', array( $this, 'do_export' ) );
	}
	
	public function add_query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}
	
	public function do_export() {
		if ( get_query_var( self::QUERY_VAR ) == self::ACTION_EXPORT ) {
			if( array_key_exists( 'export_xml', $_POST ) && check_admin_referer( 'export_xml', 'export_xml' ) ) {
				$args = array(
					'page_type'		=> esc_html( $_POST['page_type'] ),
					'page_template'	=> esc_html( $_POST['page_template'] ),
					'topic_handle'	 => esc_html( $_POST['topic_handle'] ),
					'topic_name'	   => esc_html( $_POST['topic_name'] ),
					'thumbnail_handle' => esc_html( $_POST['thumbnail_handle'] ),
					'area_handle'	  => esc_html( $_POST['area_handle'] ),
					'post_type'		=> esc_html( $_POST['post_type'] ),
					'category_slug'	=> esc_html( $_POST['category_slug'] )
				);
				
				$exporter = new WP_C5_Exporter($args);
				
				// make export directory
				$r = $exporter->make_export_dir();
				if ( is_wp_error($r) ) {
					$this->error = $r;
				} else {
					$exporter->send_headers();
					echo $exporter->get_xml();
					die();
				}
			}
			
			if( array_key_exists( 'download_file', $_POST ) && check_admin_referer( 'download_file', 'download_file' ) ) {
				$exporter = new WP_C5_Exporter();
				$exporter->download_export_dir();
			}
		}
	}
}
