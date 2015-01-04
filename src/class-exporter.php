<?php

use Sunra\PhpSimple\HtmlDomParser;

if ( ! defined( 'WPINC' ) ) exit;

class WP_C5_Exporter {
	
	const DEFAULT_BLOG_PATH         = '/blog';
	const DEFAULT_PAGE_TYPE         = 'blog_entry';
	const DEFAULT_PAGE_TEMPLATE     = 'right_sidebar';
	const DEFAULT_TOPIC_ATTR_HANDLE = 'blog_entry_topics';
	const DEFAULT_TOPIC_NAME        = 'Blog Entries';
	const DEFAULT_THUM_HANDLE       = 'thumbnail';
	const DEFAULT_AREA_HANDLE       = 'Main';
	const DEFAULT_POST_TYPE         = 'post';
	const DEFAULT_CAT_SLUG          = 'category';
	
	public function __construct( $args = null ) {
		$defaults = array(
			// concrete5
			'parent_path'       => self::DEFAULT_BLOG_PATH,
			'page_type'         => self::DEFAULT_PAGE_TYPE,
			'page_template'     => self::DEFAULT_PAGE_TEMPLATE,
			'topic_handle'      => self::DEFAULT_TOPIC_ATTR_HANDLE,
			'topic_name'        => self::DEFAULT_TOPIC_NAME,
			'thumbnail_handle'  => self::DEFAULT_THUM_HANDLE,
			'area_handle'       => self::DEFAULT_AREA_HANDLE,
			// WordPress
			'export_files_path' => WP_CONTENT_DIR . '/uploads/export',
			'post_type'         => self::DEFAULT_POST_TYPE,
			'category_slug'     => self::DEFAULT_CAT_SLUG
		);
		
		$arrs = wp_parse_args( $args, $defaults );
		
		foreach($arrs as $key => $prop) {
			$this->{$key} = $prop;
		}
	}
	
	/**
	 * Get the all posts of the post type
	 */
	public function get_posts() {
		$args = array(
			'post_type'        => $this->post_type,
			'post_status'      => 'publish',
			'nopaging'         => 'nopaging',
			'cache_results'    => false,
			'fields'           => 'ids',
			'suppress_filters' => true
		);
		$query = new WP_Query( $args );
		return $query->get_posts();
	}
	
	/**
	 * Get the all categories of the post type
	 */
	public function get_categories( $parent = 0 ) {
		$categories = array();
		$args = array(
			'post_type'    => $this->post_type,
			'parent'       => $parent,
			'hide_empty'   => 0,
			'hierarchical' => 1
		);
		$terms = get_terms( $this->category_slug, $args );
		
		if (is_array($terms)) {
			foreach ( $terms as $term ) {
				$args = array(
					'post_type'    => $this->post_type,
					'parent'       => $term->term_id,
					'hide_empty'   => 0,
					'hierarchical' => 1
				);
				$childrens = get_terms( $this->category_slug, $args );
				if (is_array($childrens) && count($childrens) > 0) {
					$category = new stdClass;
					$category->type = 'category';
				} else {
					$category = new stdClass;
					$category->type = 'node';
				}
				$category->name = $term->name;
				$category->term_id = $term->term_id;
				$category->term = $term;
				$categories[] = $category;
			}
		}
		
		return $categories;
	}
	
	/**
	 * Get a XML
	 */
	public function get_xml() {
		$x = new SimpleXMLElement( '<concrete5-cif></concrete5-cif>' );
		$x->addAttribute( 'version', '1.0' );
		
		// Export trees
		$trees = $x->addChild( 'trees' );
		$tree = $trees->addChild( 'tree' );
		$tree->addAttribute( 'type', 'topic' );
		$tree->addAttribute( 'name', $this->topic_name );
		$this->export_topics_from_parent( $tree );
		
		// Export pages
		$c5_pages = $x->addChild( 'pages' );
		$wp_posts = $this->get_posts();
		foreach ( $wp_posts as $post_id ) {
			$post = get_post( $post_id );
			// setup_postdata( $post );
			$p = $c5_pages->addChild( 'page') ;
			
			$name         = esc_attr( apply_filters( 'the_title', $post->post_title ) );
			$pagetype     = esc_attr( $this->page_type );
			$template     = esc_attr( $this->page_template );
			$path         = esc_attr( rawurldecode( $this->parent_path . '/' . $post->post_name ) );
			$description  = esc_attr( $post->post_excerpt );
			$content      = apply_filters( 'the_content', $post->post_content );
			$content      = str_replace(']]>', ']]&gt;', $content);
			$post_date    = $post->post_date;
			$categories   = apply_filters( 'get_the_categories', get_the_terms( $post, $this->category_slug ) );
			$thumbnail_id = get_post_thumbnail_id( $post_id );
			
			$p->addAttribute( 'name',        $name );
			$p->addAttribute( 'pagetype',    $pagetype );
			$p->addAttribute( 'template',    $template );
			$p->addAttribute( 'path',        $path );
			$p->addAttribute( 'description', $description );
			$p->addAttribute( 'public-date', $post_date );
			
			// Export the content to a content block
			$area = $p->addChild( 'area' );
			$area->addAttribute( 'name', $this->area_handle );
			$this->export_content( $area, $content );
			
			// Collection attributes
			$attributes = $p->addChild( 'attributes' );
			
			// Export the categories to topic attributes
			$ak = $attributes->addChild( 'attributekey' );
			$ak->addAttribute( 'handle', $this->topic_handle );
			if ( is_array( $categories ) ) {
				$topics = $ak->addChild( 'topics' );
				foreach ( $categories as $category ) {
					$topic_path = self::get_topic_path_from_term( $category, $this->category_slug );
					$topics->addChild( 'topic', $topic_path );
				}
			}
			
			// Save the post thumbnail to a image attribute
			$ak = $attributes->addChild( 'attributekey' );
			$ak->addAttribute( 'handle', $this->thumbnail_handle );
			if ( $thumbnail_id ) {
				$thumbnail_file = get_attached_file( $thumbnail_id );
				if ( $thumbnail_file ) {
					$file = $this->move_file_to_export_dir( $thumbnail_file );
					$this->export_file_attribute( $ak, $file );
				}
			}
		}
		
		return $x->asXML();
	}
	
	/**
	 * Get the concrete5 topic path string from the WordPress term
	 */
	public static function get_topic_path_from_term( $term, $taxonomy ) {
		$nodes = array();
		$ancestors = get_ancestors( $term->term_id, $taxonomy );
		foreach ( $ancestors as $term_id ) {
			$ancestor = get_term_by( 'id', $term_id, $taxonomy );
			if ( is_object( $ancestor ) ) {
				$nodes[] = $ancestor->name;
			}
		}
		$nodes = array_reverse( $nodes );
		
		$path = '/';
		for ( $i = 0; $i < count($nodes); $i++ ) {
			$n = $nodes[$i];
			$path .= $n . '/';
		}
		$path .= $term->name;
		
		return $path;
	}
	
	/**
	 * Add topic nodes from the parent term id
	 */
	private function export_topics_from_parent( SimpleXMLElement $node, $parent = 0 ) {
		$wp_categories = $this->get_categories( $parent );
		foreach ( $wp_categories as $cat ) {
			if ($cat->type == 'category') {
				$topic_category = $node->addChild( 'topic_category' );
				$topic_category->addAttribute( 'name', $cat->name );
				$this->export_topics_from_parent( $topic_category, $cat->term_id );
			} else {
				$topic = $node->addChild( 'topic' );
				$topic->addAttribute( 'name', $cat->name );
			}
		}
	}

	/**
	 * Add a CDATA node of the passed value to XML
	 */
	private function export_value( SimpleXMLElement $element, $val = '' ) {
		$cnode = $element->addChild( 'value' );
		$node = dom_import_simplexml( $cnode );
		$no = $node->ownerDocument;
		$node->appendChild( $no->createCDataSection( $val ) );
		return $cnode;
	}
	
	/**
	 * Add a content block instance of the passed HTML to XML
	 */
	private function export_content( SimpleXMLElement $area, $val = '' ) {
		$dom = new HtmlDomParser();
		$r = $dom->str_get_html( $val );
		if ( $r ) {
			foreach( $r->find('img') as $img ) {
				$src = $img->src;
				$alt = $img->alt;
				$style = $img->style;
				
				// If the image file is a attachment, move the file to the export directory
				$attachment_id = self::get_attachment_id_from_url( $src );
				if ($attachment_id) {
					$src = $this->move_file_to_export_dir( $src );
					$img->outertext = '<concrete-picture file="' . $src . '" alt="' . $alt . '" style="' . $style . '" />';
				}
			}
			
			foreach( $r->find('a') as $anchor ) {
				$href = $anchor->href;
				
				// If the linked file is a attachment, move the original file to the export directory
				$attachment_id = self::get_attachment_id_from_url( $href );
				if ($attachment_id) {
					$file = get_attached_file( $attachment_id );
					if ( $file ) {
						$anchor->href = $this->move_file_to_export_dir( $file );
					}
				}
			}

			$val = (string) $r;
		}
		
		$blocks = $area->addChild( 'blocks' );
		$block = $blocks->addChild( 'block' );
		$block->addAttribute( 'type', 'content' );
		$data = $block->addChild( 'data' );
		$data->addAttribute( 'table', 'btContentLocal' );
		$record = $data->addChild( 'record' );
		$cnode = $record->addChild( 'content' );
		$node = dom_import_simplexml( $cnode );
		$no = $node->ownerDocument;
		$cdata = $no->createCDataSection( $val );
		$node->appendChild( $cdata );
	}
	
	/**
	 * Add a file attribute of the passed value to XML
	 */
	private function export_file_attribute( SimpleXMLElement $attributekey, $val = '' ) {
		$av = $attributekey->addChild( 'value' );
		if ( $val ) {
			$file = '{ccm:export:file:' . $val . '}';
			$av->addChild( 'fID', $file );
		} else {
			$av->addChild( 'fID', 0 );
		}
	}
	
	/**
	 * Get the attachment id from the media url
	 */
	public static function get_attachment_id_from_url( $url ) {
		$filename = wp_basename( $url );
		
		if ( $filename ) {
			$args = array(
				'post_type' => 'attachment',
				'post_status' => 'any',
				'meta_query' => array(
					array(
						'key'              => '_wp_attachment_metadata',
						'value'            => $filename,
						'compare'          => 'LIKE',
						'cache_results'    => false,
						'suppress_filters' => true
					)
				)
			);
			
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				$attachment = $query->next_post();
				return $attachment->ID;
			}
		}
	}
	
	/**
	 * Move the file to the export directory
	 */
	private function move_file_to_export_dir( $file ) {
		global $wp_filesystem;
		if ( !is_object( $wp_filesystem ) ) {
			WP_Filesystem();
		}
		
		$upload_url  = WP_CONTENT_URL . '/uploads';
		$upload_path = WP_CONTENT_DIR . '/uploads';
		
		// Overwrite the file path from file url
		if ( substr( $file, 0, strlen($upload_url)) == $upload_url ) {
			$file = str_replace($upload_url, $upload_path, $file);
		}
		
		// Make the unique file name
		if ( substr( $file, 0, strlen($upload_path)) == $upload_path ) {
			$filename = substr( $file, strlen( $upload_path ) );
			$filename = str_replace( '/', '_', $filename );
			$filename = 'wpupload' . $filename;
		} else {
			$filename = wp_basename( $file );
		}
		
		// Move the file
		$destination = $this->export_files_path . '/' . $filename;
		$r = $wp_filesystem->copy( $file, $destination, true );
		if ( $r ) {
			return $filename;
		}
	}
	
	/**
	 * Make the export directory
	 */
	public function make_export_dir() {
		global $wp_filesystem;
		if (!is_object($wp_filesystem)) {
			WP_Filesystem();
		}
		
		if ( !$wp_filesystem->is_dir($this->export_files_path) ) {
			$r = $wp_filesystem->mkdir($this->export_files_path);
			if (!$r) {
				return new WP_Error( 'fails_mkdir', sprintf( __('Failed to make directory %s.', WP_C5_EXPORTER_PLUGIN_DOMAIN), $this->export_files_path ) );
			}
		}
		if ( !$wp_filesystem->is_writable($this->export_files_path) ) {
			$r = $wp_filesystem->chmod( $this->export_files_path, FS_CHMOD_DIR );
			if (!$r) {
				return new WP_Error( 'fails_chmod', sprintf( __('Directory %s is not writable.', WP_C5_EXPORTER_PLUGIN_DOMAIN), $this->export_files_path ) );
			}
		}
		
		return true;
	}
	
	public function send_headers() {
		$filename = 'wordpress_' . date( 'Y-m-d' ) . '.xml';
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
	}
	
}
