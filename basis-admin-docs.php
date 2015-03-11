<?php
/*
Plugin Name: Basis Admin Docs
Plugin URI: http://codecarpenter.com
Description: An easy to use documentation system for the Wordpress Admin. Allows Admin level users to add and edit documentation pages. All other levels can browse and view documentation on the backend.
Version: 0.1
Author: Josiah Spence
Author Email: spencejosiah@gmail.com
License:

  Copyright 2011 Josiah Spence (spencejosiah@gmail.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

///////////////////////////////////////////////////
// Register Post Type
///////////////////////////////////////////////////

add_action( 'init', 'register_cpt_admin_doc_page' );

function register_cpt_admin_doc_page() {

	$labels = array(
		'name' => _x( 'Admin Docs', 'admin_doc_page' ),
		'singular_name' => _x( 'Admin Doc Page', 'admin_doc_page' ),
		'add_new' => _x( 'Add New', 'admin_doc_page' ),
		'add_new_item' => _x( 'Add New Documentation Page', 'admin_doc_page' ),
		'edit_item' => _x( 'Edit Documentation Page', 'admin_doc_page' ),
		'new_item' => _x( 'New Documentation Page', 'admin_doc_page' ),
		'view_item' => _x( 'View Documentation Page', 'admin_doc_page' ),
		'search_items' => _x( 'Search Admin Docs', 'admin_doc_page' ),
		'not_found' => _x( 'No admin docs found', 'admin_doc_page' ),
		'not_found_in_trash' => _x( 'No admin docs found in Trash', 'admin_doc_page' ),
		'parent_item_colon' => _x( 'Parent Documentation Page:', 'admin_doc_page' ),
		'menu_name' => _x( 'Admin Docs', 'admin_doc_page' ),
	);

	$args = array(
		'labels' => $labels,
		'hierarchical' => true,
		'description' => 'Documentation for this site.',
		'supports' => array( 'title', 'editor', 'page-attributes' ),

		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_icon' => 'dashicons-clipboard',
		'show_in_nav_menus' => false,
		'publicly_queryable' => false,
		'exclude_from_search' => true,
		'has_archive' => false,
		'query_var' => true,
		'can_export' => true,
		'rewrite' => true,
		'capability_type' => 'post'
	);

	register_post_type( 'admin_doc_page', $args );
}

//////////////////////////////////////////////////
// Hide Content for Users Below Admin Level
//////////////////////////////////////////////////
//Remove Editor and Title Fields
function bad_hide_editor() {
	// Hide the editor on the post type
	if ( !current_user_can('activate_plugins') ) {
		remove_post_type_support('admin_doc_page', 'editor');
		remove_post_type_support('admin_doc_page', 'title');
	};
}
add_action( 'admin_head', 'bad_hide_editor' );

//Remove Publish and Slug Boxes
if (is_admin()) :
	function bad_remove_meta_boxes() {
		if ( !current_user_can('activate_plugins') ) {
			remove_meta_box('slugdiv', 'admin_doc_page', 'normal');
			remove_meta_box('submitdiv', 'admin_doc_page', 'normal');
			remove_meta_box('pageparentdiv', 'admin_doc_page', 'normal');
		}
	}
	add_action( 'admin_menu', 'bad_remove_meta_boxes' );
endif;

//Remove Submenu
function hide_add_new_bad() {
	if ( !current_user_can('activate_plugins') ) {
		global $submenu;
		// replace my_type with the name of your post type
		unset($submenu['edit.php?post_type=admin_doc_page'][10]);
	}
}
add_action('admin_menu', 'hide_add_new_bad');

//Remove Edit Row
add_filter( 'page_row_actions', 'remove_row_actions', 10, 2 );

function remove_row_actions( $actions, $post ) {
	if( $post->post_type === 'admin_doc_page' ) {
		if (!current_user_can('activate_plugins')) {
			unset( $actions['edit'] );
			unset( $actions['trash'] );
			unset( $actions['inline hide-if-no-js'] );
		}
		unset( $actions['view'] );
	}
	return $actions;
}

//////////////////////////////////////////
// Add Meta Box With Content
//////////////////////////////////////////
//Add Meta Boxes
function add_bad_doc_metaboxes() {
	if ( !current_user_can('activate_plugins') ) {
		global $post;
		$bad_title = get_the_title( $post->ID );
		add_meta_box('basis-doc', $bad_title, 'basis_doc_content', 'admin_doc_page');
		add_meta_box('basis-related', 'Related Docs', 'basis_doc_related', 'admin_doc_page', 'side');
	}
}
add_action( 'add_meta_boxes', 'add_bad_doc_metaboxes');

//Content for Content Meta Box
function basis_doc_content(){
	global $post;
	$post = &get_post($post->ID);
	setup_postdata( $post );
	the_content();
	wp_reset_postdata( $post );
	}

//Content for Related Docs Meta Box
function basis_doc_related(){
	global $post;
	if($post->post_parent) {
		//Child Page
		$children = get_pages("title_li=&child_of=".$post->post_parent."&echo=0&post_type=admin_doc_page");
		$bad_top_link = 'http://127.0.0.1:4001/wordpress/wp-admin/post.php?post='.$post->post_parent.'&action=edit';
	} else {
		//Not a Child
		$children = get_pages("title_li=&child_of=".$post->ID."&echo=0&post_type=admin_doc_page");
		$bad_top_link = 'http://127.0.0.1:4001/wordpress/wp-admin/post.php?post='.$post->ID.'&action=edit';
	}

	if ($children) { ?>
		<ul class="sectionlist">
			<?php $parent_title = get_the_title($post->post_parent);?>
			<li class="page_item <?php
				if ( $post->post_parent ) {
				} else {
					echo 'current_page_item';// This is not a subpage
				}
			?>"><a href="<?php echo $bad_top_link ?>"><?php echo $parent_title;?></a></li>
			<ul>
				<?php
				foreach ($children as $child) {
					echo '<li class="page_item';
					if ( $post->ID == $child->ID ) {
						echo ' current_page_item';
					}
					echo '">';
					echo '<a href="http://127.0.0.1:4001/wordpress/wp-admin/post.php?post='.$child->ID.'&action=edit">'.$child->post_title.'</a>';
					echo '</li>';
				} ?>
			</ul>
		</ul>
	<?php } else {
		echo 'No Related Documents';
	}
}

////////////////////////////////////////////
// Stylesheet for Docs
///////////////////////////////////////////

function bad_admin_styles() {
	wp_register_style('bad_admin_css', plugins_url( 'bad-docs.css', __FILE__ ));
	global $post;
	if( (get_post_type( $post->ID ) == 'admin_doc_page') && (!current_user_can('activate_plugins')) ) {
		wp_enqueue_style('bad_admin_css');
	}
}
add_action('admin_head', 'bad_admin_styles');
?>