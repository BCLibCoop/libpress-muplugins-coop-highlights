<?php defined('ABSPATH') || die(-1);

/**
 * @package Coop Highlights - Admin
 * @copyright BC Libraries Coop 2013
 *
 **/
/**
 * Plugin Name: Coop Highlights Admin
 * Description: Admin page context for Highlights. Install as MUST USE.
 * Author: Erik Stainsby, Roaring Sky Software
 * Author URI: http://roaringsky.ca/plugins/coop-highlights/
 * Version: 0.0.1
 **/
 
 
if ( ! class_exists( 'CoopHighlightsAdmin' )) :

class CoopHighlightsAdmin {

	var $slug = 'coop_highlights';
	
	public function __construct() {
		add_action( 'init', array( &$this, '_init' ));
	}

	public function _init() {
		
		if( is_admin() ) {
							
			
		}
			
	}

	public function coop_highlights_admin_page() {
		
		
	}


}

if( ! isset($coophighlights_admin)) {
	global $coophighlights_admin;
	$coophighlights_admin = new CoopHighlightsAdmin();
}

endif;

