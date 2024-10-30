<?php
/*
Plugin Name: Media Folders
Plugin URI: http://ecs.mit.edu/
Description: The Media Folders plugin creates a "folder" custom post type, to which individual media items can be attached.  The Media Folder can then be tagged, categorized, and otherwise treated as a single entity.  All media items attached to the folder can be downloaded as a single zip file.
Version: 0.1.1
Author: Brett Mellor
Author URI: http://ecs.mit.edu/
*/

// stylesheet
add_action('wp_print_styles', 'mf_stylesheet');
function mf_stylesheet() {
	$stylesheet = dirname(__FILE__) . '/media-folders.css';
	if ( file_exists($stylesheet) ) {
		$stylesheet_url = plugins_url('media-folders.css', __FILE__); 
		wp_register_style('mf-stylesheet', "$stylesheet_url");
		wp_enqueue_style( 'mf-stylesheet');
		}	
	} // mf_stylesheet

// Create new "Media Folder" ('folder') post type and add it to the admin menu
add_action('init', 'mf_register');

function mf_register() {
	register_post_type('folder', array(
		'label' => __('Media Folders', 'media-folders'),
		'labels' => array(
			'singular_name' => __('Media Folder', 'media-folders'),
			'add_new_item' => __('Add New Media Folder', 'media-folders'),
			'edit_item' => __('Edit Media Folder', 'media-folders'),
			'new_item' => __('New Media Folder', 'media-folders'),
			'view_item' => __('View Media Folder', 'media-folders'),
			'search_items' => __('Search Media Folders', 'media-folders'),
			'not_found' => __('No media folders found', 'media-folders'),
			'not_found_in_trash' => __('No media folders found in Trash', 'media-folders')
		),
		'public' => true,
		'menu_position' => 10,
		'hierarchical' => false,
		'supports' => array('title', 'editor', 'author'),
		'taxonomies' => array('post_tag'),
	));
}

// Get the post->ID of the current post. If you do it in the footer where the license will be displayed, the post->ID has become the last item whatever loop has last run, whether in the content or in the sidebar
add_action('wp_head','mf_capture_post_id');
function mf_capture_post_id() {
	global $mf_pid;
	global $post;
	$mf_pid = $post->ID;
	}


// add a custom icon for the new "Media Folder" post type
add_action('admin_head', 'folder_icon');

// note: the register_post_type() has a 'menu_icon', but there is no documentation on how to get the alternate image during mouseover.  no doubt a change in background position, but how?
define('MF_ICON36X34', plugins_url('/images/05-large.png', __FILE__));	// larger 36x34 custom icon on the edit/new media folder interface, and media folder list
define('MF_ICON28X28', plugins_url('/images/05.png', __FILE__));  	// smaller 28x28 custom icon for the admin menu, contains both gray and hover image

function folder_icon() {

	echo "<style type='text/css'>\n
		#adminmenu #menu-posts-folder div.wp-menu-image {background: transparent url('" . MF_ICON28X28 . "') no-repeat right top; }\n
		#adminmenu #menu-posts-folder:hover div.wp-menu-image, #adminmenu #menu-posts-folder.wp-has-current-submenu div.wp-menu-image {background: transparent url('". MF_ICON28X28 ."') no-repeat left top;}\n
		";	

	global $post_type;
	if (($_GET['post_type'] == 'folder') || ($post_type == 'folder'))
		echo "#icon-edit { background:transparent url('". MF_ICON36X34 ."'); no-repeat; }\n";

	echo "</style>\n";

	} // folder_icon


// hide some of the upload tabs from the media interface when processing attachments to media folders
add_filter('media_upload_tabs', 'remove_media_library_tab');
function remove_media_library_tab($tabs) {
  if (isset($_REQUEST['post_id'])) {
    $post_type = get_post_type($_REQUEST['post_id']);
    if ('folder' == $post_type) {
      unset($tabs['library']);
      unset($tabs['type_url']);
    }
  }
  return $tabs;
}


// do some stuff to the edit screens
add_action('admin_footer', 'mf_modify_edit_screens',10000);
function mf_modify_edit_screens() {

	$screen = get_current_screen();

	// Are we on an edit screen?
	if( $screen->base == 'post' ) {


		// Is it the Media Folder edit screen?
		if ( $screen->post_type == 'folder' ) {

			// Shrink the content textarea on the media folder edit page.  It will only be used to add a short description
			// Remove the upload/insert media button.  this is not how Media Folders will be used
			echo "<script type='text/javascript'>
				jQuery('#content').css({'height': '120px'});
				jQuery('#wp-content-media-buttons').empty();
				jQuery('#wp-content-media-buttons').html(\"<span class='mf_label'>Enter a short description below for this Media Folder:</span>\");
				jQuery('#media-upload-header').css('border','1px dashed red !important');
				// are we in a thickbox iframe?
				if (top === self) {
					// the media folder edit page is not in an iframe
					}
				else {
					// the media folder edit page IS in an iframe.  remove some clutter
					jQuery('.admin-bar').css('margin-top','-28px');
					jQuery('#wpadminbar').css('display','none');
					jQuery('#wpadminbar').css('border','1px solid white');
					jQuery('#adminmenuback').css('display','none');
					jQuery('#adminmenuwrap').css('display','none');
					jQuery('#footer').css('display','none');
					jQuery('.update-nag').css('display','none');
					jQuery('#wpcontent').css('padding-top','8px !important');
					jQuery('#wpcontent').css('margin-left','12px');
					}
				</script>";
			} // if media folder edit screen
		
		// it's some other edit screen
		else {
			echo "<script type='text/javascript'>
				var add_related = function () {
					// all the tags, comma delimited
					var tags = jQuery('#tax-input-post_tag').val();
					if (!tags.length) {
						alert('You may want to add some tags to this post before creating a related file group.');
						return false;
						}
					jQuery('#add-new-related-mf').attr('href','post-new.php?post_type=folder&tags='+tags+'TB_iframe=true&height=800&width=1000');
					return true;
				};
				jQuery('#wp-content-media-buttons').empty();
				var new_folder = jQuery(\"<a style='padding:3px 0px;' href='' id='add-new-related-mf' class='thickbox' title='Add New Related Media Folder' style='line-height:24px;'>Add New Related Media Folder <img height='16' width='16' src='" . MF_ICON36X34 . "'></a>\").click(add_related);
				jQuery('#wp-content-media-buttons').append(new_folder);
				</script>";
			} // other
		} // post	
	} // mf_modify_edit_screens

// Insert initial tags when coming from "create new related media folder" link
add_action('dbx_post_advanced','mf_insert_initial_tags');
function mf_insert_initial_tags() {
	global $post;
	if ( $post->post_status == 'auto-draft' && isset( $_GET['tags'] ) )
		wp_set_post_tags( $post->ID, $_GET['tags'] );
	}


// related Media Folders widget
add_action( 'widgets_init', 'register_related_media_folders' );
function register_related_media_folders() {
	register_widget( 'related_media_folders' );
	}

class related_media_folders extends WP_Widget {

	function related_media_folders() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'related-media-folders', 'description' => __('Show related Media Folders based on tags', 'related-media-folders') );

		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'related-media-folders' );

		/* Create the widget. */
		$this->WP_Widget( 'related-media-folders', __('Related Media Folders', 'related-media-folders'), $widget_ops, $control_ops );
		}

	function widget( $args ) {
		extract( $args );
		$title = __('Related Media Folders', 'related-media-folders');

		global $post;

		// Media folders are not shown as being related to other media folders
		if ($post->post_type == 'folder')
			return;

		$current_post_id = $post->ID;
		$the_tags = get_the_tags( 0 );

		if ($the_tags && count($the_tags)) {
			$tags = join('+',array_map(create_function('$x','return $x->slug;'),$the_tags));
			$args = array('post_type' => 'folder',
			              'tag' => $tags,
			              'post_status' => 'publish',
			              'posts_per_page' => -1,
			              'caller_get_posts'=> 1);

			$my_query = null;
			$my_query = new WP_Query($args);

			echo $before_widget;
			echo $before_title . $title . $after_title;

			if( $my_query->have_posts() ) {
			/*	echo "<p>Media folders with the following tag(s):";
				the_tags('');
				echo "</p>";

			*/
				echo "<ul>";
				echo "<!-- related-mf-begin -->";
				while ($my_query->have_posts()) {
					$my_query->the_post();
					// do not show a post being related to itself if it is the post being viewed on screen
					$this_post_id = $post->ID;
					if($current_post_id == $this_post_id)
						continue;
					?>
					<li><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></li>
					<?php
					} // while
				echo "<!-- related-mf-end -->";
				}

			else {
				// there were no media folders with these tags
				echo "<em>none.</em>";
				}

			if(is_user_logged_in()) {
				global $mf_pid;
				$csv_tags = wp_get_post_tags($mf_pid);
				function mf_return_tag_name($tag) { return $tag->name; }
				$csv_tags = join(',', array_map('mf_return_tag_name', $csv_tags));
				$url = admin_url( "post-new.php?post_type=folder&tags=" . $csv_tags );
				echo "<hr class='mf-related-separator'>&nbsp;<br><a target='_blank' style='font-weight:bold;' href='$url'>Create</a> new related media folder";
				}

			echo "</ul>";
			echo $after_widget;


			wp_reset_query();
			} // if the_tags
			
		else {
			// there were no tags
			}
	} // widget

} //class


// Media Folders List Table
// Add custom columns to the listing of file groups
add_filter('manage_posts_columns', 'mf_columns');
add_action('manage_posts_custom_column', 'mf_column_pop', 10, 2);

// Add custom columns to the media folders screen
function mf_columns($columns) {
	global $current_screen;
	if ($current_screen->post_type == 'folder') {
		$columns['folder_contents'] = __('Folder contents', 'media-folders');
		$columns['folder_description'] = __('Description', 'media-folders');
		}
	return $columns;
	}
// Populate the custom columns added to the media folders screen
function mf_column_pop($column_name) {
	global $post;
	if ( $column_name == 'folder_contents' ) {

		$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $post->ID ); 
		$attachments = get_posts($args);
		if(count($attachments)==0) {
			echo __('<em>empty</em>', 'media-folders');
			}
		else {
			foreach($attachments as $att) {
				$att_url = wp_get_attachment_url( $att->ID );
				$upload_dir = wp_upload_dir();
				$baseurl = $upload_dir['baseurl'];
				$att_name = preg_replace("|$baseurl/|",'',$att_url);
				$contents .= "<a title='$textlink' href='$att_url'>$att_name</a>, ";
				} // foreach
			$contents = rtrim($contents,', ');
			echo $contents;
			} // else

		} // $column_name

	else if ( $column_name == 'folder_description' ) {
		$folder = get_post($att['id']);
		$description = $folder->post_content;
		$description = apply_filters('the_content', $description);
		$description = str_replace(']]>', ']]>', $description);
		$description = strip_tags($description);
		echo $description;
		}
	else
		return; 
	} // 

// add a download link to the post actions in the title column
add_filter('post_row_actions', 'mf_download_link', 10, 2);

function mf_download_link($actions, $post) {
	if ($post->post_type == 'folder'){

		$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $post->ID ); 
		$attachments = get_posts($args);
		$num = count($attachments);

		if($num > 1) {
			$link = plugins_url('download.php?mfid='.$post->ID, __FILE__);
			$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link,'mf-download') : $link;
			$actions['download'] = "<a href='$link'>Download Zip</a>";
			} // if > 1

		} // if folder
	return $actions;
	} // mf_download_link


// Override Wordpress's automatic thickbox frame sizing
add_action('admin_head','mf_thickbox_resize');
function  mf_thickbox_resize() {
?>
<script type='text/javascript'>
jQuery(function($) {
        tb_position = function() {
                var tbWindow = $('#TB_window');
                var width = $(window).width();
                var H = $(window).height();
		    // var H = 400;
                var W = ( 1024 < width ) ? 1024 : width;

                if ( tbWindow.size() ) {
                        tbWindow.width( W - 50 ).height( H - 45 );
                        $('#TB_iframeContent').width( W - 50 ).height( H - 75 );
                        tbWindow.css({marginLeft: '-' + parseInt((( W - 50 ) / 2),10) + 'px'});
                };

                return $('a.thickbox').each( function() {
                        var href = $(this).attr('href');
                        if ( ! href ) return;
                        href = href.replace(/&width=[0-9]+/g, '');
                        href = href.replace(/&height=[0-9]+/g, '');
                        $(this).attr( 'href', href + '&width=' + ( W - 80 ) + '&height=' + ( H - 85 ) );
                });
        };

        $(window).resize( function() { tb_position() } );
        $(document).ready( function() { tb_position() } );
});
</script>
<style type='text/css'>#TB_window { top: 28px !important; }</style>
<style type='text/css'>.mf_label { font-weight:bold; font-size:14px; color:#777; }</style>
<?
	} // mf_thickbox_resize
?>