<?php

$wppath = preg_replace( '!wp-content.*$!', '', __FILE__ );

require( $wppath . 'wp-load.php' );

// little bit of security
$nonce=$_REQUEST['_wpnonce'];
if ( !wp_verify_nonce($nonce, 'mf-download') ) wp_die( __('Media Folder not found (err1)', 'media-folders') );

if ( !isset($_GET['mfid']) || $_GET['mfid']=='' ) wp_die( __('Media Folder not found (err2)', 'media-folders') );
$mfid = intval($_GET['mfid']);

// is $mfid a valid folder post id?
$folders = get_posts( array( post_type=>'folder', 'numberposts' => -1 ) );
$folder_ids = array();
foreach ($folders as $folder) {
	$folder_ids[] = $folder->ID;
	}	
if( !in_array($mfid, $folder_ids, true) ) wp_die( __('Media Folder not found (err3)', 'media-folders') );

$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $mfid ); 
$attachments = get_posts($args);

// are there any attachments?
if(count($attachments)>0) {
	$file_list = array();
	$uploads_path = WP_CONTENT_URL . "/uploads/";
	foreach($attachments as $att) {
		$file_url = wp_get_attachment_url( $att->ID );
		$file_list[] = str_replace($uploads_path, '', $file_url);  
		} // foreach

	$post = get_post($mfid);
	$title = $post->post_name;
	// Certain characters are not allowed in file names.
	// This is actually different by platform, but let's just make life easier...
	$title = preg_replace("![:/\\\\]!", '-', $title) . '.zip';

	chdir(WP_CONTENT_DIR . '/uploads');
	create_zip($file_list, "/tmp/files.zip");

	// ship it
	header("Content-type: application/zip");
	header("Content-Disposition: attachment; filename={$title}");
	header("Pragma: no-cache");
	header("Expires: 0");
	readfile('/tmp/files.zip');
	exit;

	} // if count

else {
	wp_die( __("There are no files in this media folder", 'media-folders') );
	} 



function create_zip($files = array(), $destination = '', $overwrite = true) {

  // if the zip file already exists and overwrite is false, return false
  if (file_exists($destination) && !$overwrite) { return false; }

  $valid_files = array();
  // if files were passed in...
  if (is_array($files)) {
    // cycle through each file
    foreach ($files as $file) {
      // make sure the file exists
      if (file_exists($file))
        $valid_files[] = $file;
    }
  }

  // if we have good files...
  if (count($valid_files)) {
    // create the archive

    $zip = new ZipArchive();

    if ($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
      return false;
    }
    //add the files
    foreach ($valid_files as $file) {
      $zip->addFile($file,$file);
    }
    
    // close the zip -- done!
    $zip->close();
    
    // check to make sure the file exists
    return file_exists($destination);
  } else {
    return false;
  }
}
