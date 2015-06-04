<?php defined('ABSPATH') || die(-1);

/**
 * @package Coop Highlights
 * @copyright BC Libraries Coop 2013
 *
 **/
/**
 * Plugin Name: Coop Highlights 
 * Description: Custom content type to present in highlight boxes on home page. Install as MUST USE.
 * Author: Erik Stainsby, Roaring Sky Software
 * Author URI: http://roaringsky.ca/plugins/coop-highlights/
 * Version: 0.0.1
 **/
 
 
if ( ! class_exists( 'CoopHighlights' )) :

class CoopHighlights {

	var $slug = 'coop_highlights';
	
	public function __construct() {
		add_action( 'init', array( &$this, '_init' ));
	}

	public function _init() {
		
		self::register_custom_post_type();
				
		if( is_admin() ) {		
							
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles_scripts' ));
		
		//  custom post type already provides menu item 	
		//	add_action( 'admin_menu', array( &$this,'add_highlights_menu' ));	
				
			add_action( 'add_meta_boxes', array( &$this, 'add_highlight_link_meta_box' )) ;
			add_action( 'add_meta_boxes', array( &$this, 'add_highlight_position_meta_box' )) ;
			
			add_action( 'save_post', array( &$this, 'save_post_highlight_linkage' ));
			add_action( 'save_post', array( &$this, 'save_post_highlight_position' ));

		}
		// else {
		// 	add_action( 'wp_enqueue_scripts', array( &$this, 'frontside_enqueue_styles_scripts' ));
		// }	
	}
	
/*
	public function frontside_enqueue_styles_scripts() {
	
		wp_register_style( 'coop-highlights', plugins_url( '/css/coop-highlights.css', __FILE__ ), false );
		wp_enqueue_style( 'coop-highlights' );
		
		wp_register_script( 'coop-highlights-js', plugins_url( '/js/coop-highlights.js',__FILE__), array('jquery'));
		wp_enqueue_script( 'coop-highlights-js' );
		
	}
*/
	
	public function admin_enqueue_styles_scripts($hook) {
	
		if( 'site-manager_page_highlight' == $hook || 'site-manager_page_highlight-admin' == $hook 
			|| 'edit.php' == $hook || 'post.php' == $hook || 'post-new.php' == $hook ) {
					
			wp_register_style( 'coop-highlights-admin', plugins_url( '/css/coop-highlights-admin.css', __FILE__ ), false );
			wp_enqueue_style( 'coop-highlights-admin' );
			
			
			wp_register_script( 'coop-highlights-admin-js', plugins_url( '/js/coop-highlights-admin.js',__FILE__), array('jquery'));
			wp_enqueue_script( 'coop-highlights-admin-js' );
			
		}
			
		return;
	}
	
	
	public function add_highlights_menu() {
	
		global $coophighlights_admin;
		$plugin_page = add_submenu_page( 'site-manager', 'Front Highlights', 'Front Highlights', 'manage_local_site', 'highlight', array(&$coophighlights_admin,'coop_highlights_admin_page'));
	
	}
	
	
	public function add_highlight_link_meta_box() {
		add_meta_box( $this->slug.'_linkage','Link Highlight to Page/Post', array(&$this, 'coop_highlight_inner_box'));
	}
		
	public function coop_highlight_inner_box( $post ) {
		
		global $coophighlights_utils;
		$out = array();
		
		$current = get_post_meta($post->ID, '_'.$this->slug.'_linked_post', true);
		
		$out[] = '<p>If you wish the highlight to be linked to a post or page, select that post/page from the list below.</p>';
		$out[] = $coophighlights_utils->highlight_linked_post_selector( $this->slug .'_linked_post', $current );
		$out[] = '<p>Items in green are posts. Items in blue are pages.</p>';
		
		echo implode("\n",$out);

	}
		
	public function add_highlight_position_meta_box() {
		add_meta_box( $this->slug.'_placement','Show Highlight in Column #', array(&$this, 'coop_highlight_position_inner_box'));
	}
	
	
	public function coop_highlight_position_inner_box( $post ) {
		
		$out = array();
		$tag = $this->slug.'_position';
		$current = get_post_meta($post->ID, '_'.$tag, true);
		
		error_log( 'current '.$tag.': '. $current );
		
		$out[] = '<p>You must choose which column this Highlight will be displayed in.</p>';
		
		for( $i=1;$i<=3;$i++) {
		
			$out[] = sprintf('<p><input type="radio" id="highlight_column_%d" value="%d" name="%s"%s>',$i,$i,$tag,(($current==$i)?' checked="checked"':''));
			$out[] = sprintf('<label for="highlight_column_%d">Column %d</label></p>', $i, $i );
		}	
		
		echo implode("\n",$out);

	}
	
	
	public function save_post_highlight_linkage( $post_id ) {

		error_log( __FUNCTION__ );
				
		if( ! wp_is_post_revision($post_id)) {
		
			if( array_key_exists($this->slug .'_linked_post', $_POST)) {
				$link_id = $_POST[$this->slug .'_linked_post'];
				update_post_meta($post_id, '_'.$this->slug.'_linked_post', $link_id );
			}
		}
	}
	
	
	public function save_post_highlight_position( $post_id ) {
		
		error_log( __FUNCTION__ );
		
		if( ! wp_is_post_revision($post_id)) {
		
			$tag = $this->slug .'_position';
			
			if( array_key_exists($tag, $_POST)) {
				$index = $_POST[$tag];
				
				error_log( 'setting '.$tag.': '. $index );
				
				update_post_meta($post_id, '_'.$tag, $index );
			}
		}
	}
	

	public function register_custom_post_type() {
	
		$labels = array(
			'name' => 'Highlights',
			'singular_name' => 'Highlight',
			'add_new' => 'Add New',
			'add_new_item' => 'Add New Highlight',
			'edit_item' => 'Edit Highlight',
			'new_item' => 'New Highlight',
			'all_items' => 'All Highlights',
			'view_item' => 'View Highlight',
			'search_items' => 'Search Highlights',
			'not_found' =>  'No highlights found',
			'not_found_in_trash' => 'No highlights found in Trash', 
			'parent_item_colon' => '',
			'menu_name' => 'Highlights'
		);
		
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => true, 
			'query_var' => true,
			'rewrite' => array( 'slug' => 'highlight' ),
			'capability_type' => 'post',
			'has_archive' => false, 
			'hierarchical' => false,
			'menu_position' => 17,
			'supports' => array( 'title', 'editor' ),
			'taxonomies' => array( 'category', 'post_tag')
		); 
		register_post_type( 'highlight', $args );
	}
	
}

if ( ! isset($coophighlights) ) {

	require_once( 'inc/coop-highlights-utils.php' );
	
	global $coophighlights;
	$coophighlights = new CoopHighlights();
	
}
	
endif; /* ! class_exists */