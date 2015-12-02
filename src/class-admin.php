<?php
if ( ! defined( 'WPINC' ) ) exit;

class WP_C5_Exporter_Admin {
	
	const PAGE_SLUG = 'c5-exporter';
	const OPTION    = 'c5-exporter';
	
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
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}
	
	public function admin_menu() {
		$hook = add_management_page(
			esc_html__( 'C5 Export', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			esc_html__( 'C5 Export', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			'import', // capability
			self::PAGE_SLUG,
			array( $this, 'admin_view' )
		);
		add_action( "load-$hook", array( $this, 'admin_help' ) );
	}
	
	public function admin_view() {
		if ( is_wp_error($this->error) ) {
			echo '<div class="error"><p>' . $this->error->get_error_message() . '</p></div>';
		}
		
		$action_url = site_url( '?' . WP_C5_Exporter_Front::QUERY_VAR . '=' . WP_C5_Exporter_Front::ACTION_EXPORT );
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Export concrete5 Content Import Format file', WP_C5_EXPORTER_PLUGIN_DOMAIN ); ?></h2>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'Please create database backup copies of both of your site (WordPress and concrete5).', WP_C5_EXPORTER_PLUGIN_DOMAIN ); ?></p>
			</div>
			<form action="<?php echo esc_url($action_url); ?>" method="post">
				<?php wp_nonce_field( 'export_xml', 'export_xml' ); ?>
				<?php do_settings_sections( self::PAGE_SLUG ); ?>
				<?php submit_button( esc_html__( 'Download Export XML' ) ); ?>
			</form>
			<hr />
			<form action="<?php echo esc_url($action_url); ?>" method="post">
				<?php echo '<p>' . esc_html__('Download files uploaded to Media Library.') . '</p>'; ?>
				<?php wp_nonce_field( 'download_file', 'download_file' ); ?>
				<?php submit_button( esc_html__( 'Download Files' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}
	
	public function admin_help() {
		get_current_screen()->add_help_tab( array(
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' => '<p>' . esc_html__( 'You can export a file of your blog&#8217;s content in order to import it into your concrete5 site. The export file will be an XML file format called concrete5 CIF (content import format).', WP_C5_EXPORTER_PLUGIN_DOMAIN ) . '</p>'
				. '<p>' . esc_html__( 'You can also download files of your blog in order to import these into your concrete5 site.', WP_C5_EXPORTER_PLUGIN_DOMAIN ) . '</p>'
		) );
		get_current_screen()->add_help_tab( array(
			'id'      => 'howtouse',
			'title'   => esc_html__( 'How to Use', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			'content' => '<ol>'
				. '<li>' . esc_html__( 'First, you should export a XML file.', WP_C5_EXPORTER_PLUGIN_DOMAIN ) . '</li>'
				. '<li>' . sprintf( esc_html__( 'Then, you can download files.', WP_C5_EXPORTER_PLUGIN_DOMAIN ) ) . '</li>'
				. '<li>' . esc_html__( 'Install "Migration Tool" add-on to your concrete5 site', WP_C5_EXPORTER_PLUGIN_DOMAIN ) . '</li>'
				. '<li>' . esc_html__( 'Add a import batch of Migration Tool and upload XML file to the batch.', WP_C5_EXPORTER_PLUGIN_DOMAIN ) . '</li>'
				. '<li>' . esc_html__( 'Upload files to the batch.', WP_C5_EXPORTER_PLUGIN_DOMAIN ) . '</li>'
				. '<li>' . esc_html__( 'Finally, you can import batch to your concrete5 site.', WP_C5_EXPORTER_PLUGIN_DOMAIN ) . '</li>'
				. '</ol>'
		) );
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __('For more information:') . '</strong></p>' .
			'<p>' . __('<a href="http://codex.wordpress.org/Tools_Export_Screen" target="_blank">Documentation on Export</a>') . '</p>' .
			'<p>' . __('<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>' .
			'<p>' . __('<a href="https://github.com/concrete5/addon_migration_tool" target="_blank">concrete5 Migration Tool Add-on</a>') . '</p>'
		);
	}
	
	public function admin_init() {
		
		add_settings_section(
			self::PAGE_SLUG . '_wp',
			esc_html__( 'From WordPress', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			array( $this, 'settings_section_options_wp' ),
			self::PAGE_SLUG
		);
		
		add_settings_field(
			'wp_c5_exporter_post_type',
			esc_html__( 'Export Post Type', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			array( $this, 'settings_field_post_type' ),
			self::PAGE_SLUG,
			self::PAGE_SLUG . '_wp'
		);
		
		add_settings_field(
			'wp_c5_exporter_category_slug',
			esc_html__( 'Export Category', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			array( $this, 'settings_field_category_slug' ),
			self::PAGE_SLUG,
			self::PAGE_SLUG . '_wp'
		);
		
		add_settings_section(
			self::PAGE_SLUG . '_c5',
			esc_html__( 'To concrete5', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			array( $this, 'settings_section_options_c5' ),
			self::PAGE_SLUG
		);
		
		add_settings_field(
			'wp_c5_exporter_page_type',
			esc_html__( 'Page Type handle for blog entries', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			array( $this, 'settings_field_page_type' ),
			self::PAGE_SLUG,
			self::PAGE_SLUG . '_c5'
		);
		
		add_settings_field(
			'wp_c5_exporter_page_template',
			esc_html__( 'Page Template handle for blog entries', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			array( $this, 'settings_field_page_template' ),
			self::PAGE_SLUG,
			self::PAGE_SLUG . '_c5'
		);
		
		add_settings_field(
			'wp_c5_exporter_topic_handle',
			esc_html__( 'Attribute handle for topic tree', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			array( $this, 'settings_field_topic_handle' ),
			self::PAGE_SLUG,
			self::PAGE_SLUG . '_c5'
		);
		
		add_settings_field(
			'wp_c5_exporter_topic_name',
			esc_html__( 'Name of topic tree for blog entries', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			array( $this, 'settings_field_topic_name' ),
			self::PAGE_SLUG,
			self::PAGE_SLUG . '_c5'
		);
		
		add_settings_field(
			'wp_c5_exporter_thumbnail_handle',
			esc_html__( 'Attribute handle for thumbnail image', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			array( $this, 'settings_field_thumbnail_handle' ),
			self::PAGE_SLUG,
			self::PAGE_SLUG . '_c5'
		);
		
		add_settings_field(
			'wp_c5_exporter_area_handle',
			esc_html__( 'Area handle for blog contents', WP_C5_EXPORTER_PLUGIN_DOMAIN ),
			array( $this, 'settings_field_area_handle' ),
			self::PAGE_SLUG,
			self::PAGE_SLUG . '_c5'
		);
		
	}
	
	public function settings_section_options_wp() {
		echo '<p>' . esc_html__('Options for your WordPress blog.') . '</p>';
	}
	
	public function settings_field_post_type() {
		echo '<select name="post_type" id="post_type">';
		foreach ( get_post_types( array( 'can_export' => true ), 'objects' ) as $post_type ) : ?>
		<option value="<?php echo esc_attr( $post_type->name ); ?>" <?php selected( WP_C5_Exporter::DEFAULT_POST_TYPE, $post_type->name ); ?>><?php echo esc_html( $post_type->label ); ?></option>
		<?php endforeach;
		echo '</select>';
	}
	
	public function settings_field_category_slug() {
		echo '<select name="category_slug" id="category_slug">';
		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) : ?>
		<option name="category_slug" type="radio" value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php selected( WP_C5_Exporter::DEFAULT_CAT_SLUG, $taxonomy->name ); ?>><?php echo esc_html( $taxonomy->label ); ?></option>
		<?php endforeach;
		echo '</select>';
	}
	
	public function settings_section_options_c5() {
		echo '<p>' . esc_html__('Options for your concrete5 site.') . '</p>';
	}
	
	public function settings_field_page_type() {
		echo '<input name="page_type" type="text" id="page_type" value="' . WP_C5_Exporter::DEFAULT_PAGE_TYPE . '" />';
	}
	
	public function settings_field_page_template() {
		echo '<input name="page_template" type="text" id="page_template" value="' . WP_C5_Exporter::DEFAULT_PAGE_TEMPLATE . '" />';
	}
	
	public function settings_field_topic_handle() {
		echo '<input name="topic_handle" type="text" id="topic_handle" value="' . WP_C5_Exporter::DEFAULT_TOPIC_ATTR_HANDLE . '" />';
	}
	
	public function settings_field_topic_name() {
		echo '<input name="topic_name" type="text" id="topic_name" value="' . WP_C5_Exporter::DEFAULT_TOPIC_NAME . '" />';
	}
	
	public function settings_field_thumbnail_handle() {
		echo '<input name="thumbnail_handle" type="text" id="thumbnail_handle" value="' . WP_C5_Exporter::DEFAULT_THUM_HANDLE . '" />';
	}
	
	public function settings_field_area_handle() {
		echo '<input name="area_handle" type="text" id="area_handle" value="' . WP_C5_Exporter::DEFAULT_AREA_HANDLE . '" />';
	}
	
}
