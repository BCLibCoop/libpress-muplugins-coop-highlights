<?php 
/**
 * @package Coop Highlights - Utils
 * @copyright BC Libraries Coop 2013
 *
 **/
/**
 * Plugin Name: Coop Highlights Utils
 * Description: Admin page context for Highlights. Install as MUST USE.
 * Author: Erik Stainsby, Roaring Sky Software
 * Author URI: http://roaringsky.ca/plugins/coop-highlights/
 * Version: 0.0.1
 **/
 
 
if ( ! class_exists( 'CoopHighlightsUtils' )) :

class CoopHighlightsUtils {

	var $slug = 'coop_highlights';
	
	public function __construct() {
		add_action( 'init', array( &$this, '_init' ));
	}

	public function _init() {
		
		if( is_admin() ) {
							
			
		}
			
	}

	public function highlight_linked_post_selector( $id, $prev=null ) {
		// listing of the posts/pages available to be linked to a lede box
		// - hand-rolled hierarchy listing is probably fastest - see Fixtures
		$out = array();
	
		$out[] = '<select class="'.$id.'" name="'.$id.'" style="width:90%">';
		$out[] = '<option value="0"></option>';
		$nodes = self::walk_menu_nodes();
	//	error_log( count($nodes) );				// reflects only top-level parent-nodes
		foreach( $nodes as $n ) {
			$out[] = self::expand_options($n,$prev);
		}
		$out[] = '</select>';
		
		return implode("\n",$out);
	}
	
	
	public static function expand_options( $node, $prev=null ) {
		
		$out = array();
		$indent = (($node['depth']>0) ? "\t":'');
	
		$out[] = sprintf( '<option class="level-%d %s" value="%d"%s>%s</option>',$node['depth'],$node['post_type'],$node['ID'],(($node['ID']==$prev)?' selected="selected"':''),$node['post_title']);
		
		if(count($node['children'])>0) {
			foreach( $node['children'] as $n ) {
				$out[] = self::expand_options( $n, $prev );
			}
		}
		
		return implode("\n",$out);
	}
	
	
	/**
	*	walk the menu hierarchy from the fixtures at the base 
	*	to the last pages under each branch.
	*
	**/

	public static function walk_menu_nodes( $node_id=0, $depth=0 ) {
			
		global $wpdb;
		$sql = "SELECT ID, post_type, post_title, post_parent FROM $wpdb->posts WHERE post_parent=$node_id AND post_type IN('page','post') AND post_status='publish' ORDER BY post_parent,menu_order";

		$res = $wpdb->get_results($sql);
		
		$ret = array();
		if( $wpdb->num_rows === 0 ) {
			return $ret;
		}
		
		foreach( $res as $r ) {
			$node = array( 'ID'=>$r->ID, 'post_type'=>$r->post_type, 'post_title'=>$r->post_title, 'depth' => $depth );
			$node['children'] = self::walk_menu_nodes($r->ID, $depth+1);
			$ret[] = $node;
			unset($node);
		}
		return $ret;	
	}


}

if( ! isset($coophighlights_utils)) {
	global $coophighlights_utils;
	$coophighlights_utils = new CoopHighlightsUtils();
}

endif;
