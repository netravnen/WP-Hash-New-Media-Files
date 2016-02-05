<?php
/**
 * Plugin Name: Hash Upload Filename
 * Plugin URI: https://gist.github.com/Jeni4/41630a174c95d9303a63
 * Description: Rename uploaded files to something completely random based on filename, current time in seconds since 1-1-1970, 5 different $_SERVER[] values and different ways of using the sha1() + md5() functions.
 * Version: 1.1.0.1
 * Author: Jeni4
 * Author URI: https://github.com/Jeni4/
 */

function my_hashing($hash) {
	$to_be_hashed = md5(time())+$hash+sha1($_SERVER['HTTP_HOST'])+md5($_SERVER['HTTP_USER_AGENT']);
	$salt = sha1(md5($hash)+sha1(time()))+sha1($_SERVER['REMOTE_ADDR']+$_SERVER['REMOTE_PORT']+$_SERVER['SERVER_PORT']);
	
	$session_values = $_SERVER['SERVER_SOFTWARE']+
		$_SERVER['SERVER_ADDR']+
		$_SERVER['SERVER_PORT']+
		$_SERVER['REMOTE_ADDR']+
		$_SERVER['REMOTE_PORT']+
		$_SERVER['HTTP_USER_AGENT']+
		$_SERVER['HTTP_HOST']+
		$_SERVER['HTTP_REFERER']+
		$_SERVER['REQUEST_TIME']+
		$_SERVER['REQUEST_TIME_FLOAT']+
		$_SERVER['REQUEST_METHOD'];
	
	// http://php.net/manual/en/function.crypt.php
	// http://php.net/manual/en/function.sha1.php
	// http://php.net/manual/en/function.md5.php
	// http://php.net/manual/en/function.hash.php
	$hashed_value = hash('tiger192,4', hash('sha256', md5(sha1($session_values) + sha1(crypt( $to_be_hashed, $salt ))) ));
	
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