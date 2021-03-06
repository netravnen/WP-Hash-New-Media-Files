<?php
/**
 * Plugin Name: WP Hash New Media Files
 * Plugin URI: https://github.com/netravnen/WP-Hash-New-Media-Files
 * Description: Rename uploaded files to something completely random based on filename, current time in seconds since 1-1-1970, 5 different $_SERVER[] values and different ways of using the sha1() + md5() functions.
 * Version: 1.1.1
 * Author: netravnen
 * Author URI: https://github.com/netravnen
 */

function my_hashing($hash) {
	$to_be_hashed = md5(time());
	$to_be_hashed .= $hash;
	$to_be_hashed .= sha1($_SERVER['HTTP_HOST']);
	$to_be_hashed .= md5($_SERVER['HTTP_USER_AGENT']);
	
	$salt = sha1(md5($hash) . sha1(time()));
	$salt .= sha1($_SERVER['REMOTE_ADDR'] . $_SERVER['REMOTE_PORT'] . $_SERVER['SERVER_PORT']);
	
	$session_values = $_SERVER['SERVER_SOFTWARE'];
	$session_values .= $_SERVER['SERVER_ADDR'];
	$session_values .= $_SERVER['SERVER_PORT'];
	$session_values .= $_SERVER['REMOTE_ADDR'];
	$session_values .= $_SERVER['REMOTE_PORT'];
	$session_values .= $_SERVER['HTTP_USER_AGENT'];
	$session_values .= $_SERVER['HTTP_HOST'];
	$session_values .= $_SERVER['HTTP_REFERER'];
	$session_values .= $_SERVER['REQUEST_TIME'];
	$session_values .= $_SERVER['REQUEST_TIME_FLOAT'];
	$session_values .= $_SERVER['REQUEST_METHOD'];
	
	// http://php.net/manual/en/function.crypt.php
	// http://php.net/manual/en/function.sha1.php
	// http://php.net/manual/en/function.md5.php
	// http://php.net/manual/en/function.hash.php
	$hashed_value = hash('tiger192,4', hash('sha256', $_SERVER['REQUEST_TIME_FLOAT'] . md5(sha1($session_values) . sha1(crypt( $to_be_hashed, $salt ))) ));
	
	return $hashed_value;
}

/**
 * Filter {@see sanitize_file_name()} and return an MD5 hash.
 *
 * @param string $filename
 * @return string
 */
add_filter('sanitize_file_name', 'make_filename_hash', 10);
function make_filename_hash($filename) {
    $info = pathinfo($filename);
    $ext  = empty($info['extension']) ? '' : '.' . $info['extension'];
    $name = basename($filename, $ext);
    return my_hashing($name) . $ext;
}

add_action( 'add_attachment', 'fmt_update_media_title' );
function fmt_update_media_title( $id ) {

	$uploaded_post_id = get_post( $id );
	$title            = $uploaded_post_id->post_title;

	// Sets title to md5 value of the ORIGINAL FILENAME
	$title = my_hashing($title);

	// add formatted title to the ALTERNATIVE TEXT META field
	//update_post_meta( $id, '_wp_attachment_image_alt', $title );

	// update the post
	$uploaded_post               = array();
	$uploaded_post['ID']         = $id;
	$uploaded_post['post_title'] = $title;
	$uploaded_post['post_name']  = $title;

	// add formatted title to the DESCRIPTION META field
	//$uploaded_post['post_content'] = $title;

	// add formatted title to the CAPTION META field
	//$uploaded_post['post_excerpt'] = $title;

	wp_update_post( $uploaded_post );
}

?>