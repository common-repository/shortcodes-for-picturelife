<?php
/*
Plugin Name: Shortcodes for Picturelife
Plugin URI: http://www.tieronedesign.co.uk/
Description: Show your Picturelife albums direct on your web site without any HTML coding.
Version: 1.0.4
Author: Tier One Design Ltd
Author URI: http://www.tieronedesign.co.uk
*/

include_once('lib/3rdparty/cache.php');
include_once('lib/rewrite-rules.php');

add_action('admin_menu', 'pl_register_custom_menu_pages');

add_action('wp_enqueue_scripts', 'pl_add_main_stylesheet');

function pl_register_custom_menu_pages() {
    add_menu_page('Picturelife settings', 'Picturelife', 'manage_options', 'picturelife', 'pl_admin_settings', 'dashicons-format-gallery', 12);
}

function pl_admin_settings() {
	if(isset($_REQUEST['cmd'])) {
		if($_REQUEST['cmd']=='save_api_details') {
			update_option('pl_app_key', $_REQUEST['app_key']);
			update_option('pl_app_secret', $_REQUEST['app_secret']);
			update_option('pl_token', '');
			update_option('pl_user_id', '');
			update_option('pl_access_token', '');
			update_option('pl_refresh_token', '');
			update_option('pl_expires', '');
			update_option('pl_expires_in', '');
			update_option('pl_token_type', '');
		}
		elseif($_REQUEST['cmd']=='validate_code') {
			if(isset($_REQUEST['code'])) {
				$code = $_REQUEST['code'];
			} else {
				$code = $_REQUEST['?code'];
			}
			
			$results = json_decode(cache_url('https://api.picturelife.com/oauth/access_token?client_id='. get_option('pl_app_key', '') .'&client_secret='. get_option('pl_app_secret', '') .'&code='. $code .'&client_uuid='. md5($_SERVER['HTTP_HOST']) .'', true));
			
			if($results->status=='200') {
				update_option('pl_token', $results->token);
				update_option('pl_user_id', $results->user_id);
				update_option('pl_access_token', $results->access_token);
				update_option('pl_refresh_token', $results->refresh_token);
				update_option('pl_expires', $results->expires);
				update_option('pl_expires_in', $results->expires_in);
				update_option('pl_token_type', $results->token_type);
			}
		}
		elseif($_REQUEST['cmd']=='refresh_token') {
			pl_refresh_token($_REQUEST['refresh_token']);
		}
	}
	
	echo '<h2>Picturelife settings</h2>';
	
	if(get_option('pl_app_key', '')=='' or get_option('pl_app_secret', '')=='') {
		echo '<p>The first step in setting up Picturelife is specifying your API details, these can be setup at <a href="https://picturelife.com/developers" target="_blank">picturelife.com/developers</a>. Not got an account? <a href="https://picturelife.com/?love=stevebates" target="_blank">Register here</a>.</p>';
		echo '<form method="post" action="">';
		echo '<input type="hidden" name="cmd" value="save_api_details">';
		echo '<table cellspacing="4" cellpadding="4" border="0">';
		echo '<tr>';
		echo '<td><b>App Key</b></td>';
		echo '<td><input type="text" name="app_key" value="'. $_REQUEST['app_key'] .'"></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td><b>App Secret</b></td>';
		echo '<td><input type="text" name="app_secret" value="'. $_REQUEST['app_secret'] .'"></td>';
		echo '</tr>';
		echo '</table>';
		submit_button('Save details');
		echo '</form>';
	}
	elseif(get_option('pl_token', '')=='') {
		echo '<p>You must now authenticate with the Picture API before we can display any photographs.</p>';
		echo '<form method="post" action="https://api.picturelife.com/oauth/authorize?client_id='. get_option('pl_app_key', '') .'&client_secret='. get_option('pl_app_secret', '') .'&client_uuid='. md5($_SERVER['HTTP_HOST']) .'&response_type=code&redirect_uri='. urlencode(admin_url('admin.php?page=picturelife&cmd=validate_code&')) .'">';
		submit_button('Authenticate with Picturelife');
		echo '</form>';
		echo '<p>If your having problems, try <a href="'. admin_url('admin.php?page=picturelife&cmd=save_api_details&app_key=&app_secret=') .'">resetting your API details</a>.</p>';
	} else {
		if(get_option('pl_expires', '0') - time() < 3600) {
			pl_refresh_token(get_option('pl_refresh_token', ''));
		}
		
		echo '<p>Picturelife is now fully setup, you can begin to use the custom shortcodes to place your photos on your web site.</p>';
		echo '<table cellspacing="4" cellpadding="4" border="0">';
		echo '<tr>';
		echo '<td><b>App Key</b></td>';
		echo '<td>'. get_option('pl_app_key', '') .' <a href="'. admin_url('admin.php?page=picturelife&cmd=save_api_details&app_key=&app_secret=') .'">reset your API details</a></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td><b>Client UUID</b></td>';
		echo '<td>'. md5($_SERVER['HTTP_HOST']) .'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td><b>User ID</b></td>';
		echo '<td>'. get_option('pl_user_id', '') .'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td><b>Token Expires</b></td>';
		echo '<td>'. date('r', get_option('pl_expires', '0')) .' <a href="'. admin_url('admin.php?page=picturelife&cmd=refresh_token&refresh_token='. get_option('pl_refresh_token', '') .'') .'">refresh</a></td>';
		echo '</tr>';
		echo '</table>';
		
		echo '<br>';
		echo '<h3>Shortcode generator</h3>';
		echo '<p>Select an album from below to generate it\'s own unique shortcode. If the album is set to public in Picturelife then all photographs will be shown, if its a private album then only photos set to public individually will show.</p>';
			
		echo '<table cellspacing="4" cellpadding="4" border="0">';
			
		$album_data = json_decode(cache_url('https://api.picturelife.com/albums/index?access_token='. get_option('pl_access_token', '')));
		
		if($album_data->status=='20000') {
			echo '<tr>';
			echo '<td><b>Public albums</b></td>';
			echo '<td>';
				echo '<select name="album_id" onchange="if (this.value) window.location.href=this.value">';
				echo '<option value="'. admin_url('admin.php?page=picturelife') .'">Please select...</option>';
				foreach($album_data->albums as &$album) {
					if($album->privacy=='6') {
						if(isset($_REQUEST['album_id'])) {
							if($_REQUEST['album_id']==$album->id) {
								echo '<option value="'. admin_url('admin.php?page=picturelife&album_id='. $album->id) .'" selected>'. $album->caption .'</option>';
							} else {
								echo '<option value="'. admin_url('admin.php?page=picturelife&album_id='. $album->id) .'">'. $album->caption .'</option>';
							}
						} else {
							echo '<option value="'. admin_url('admin.php?page=picturelife&album_id='. $album->id) .'">'. $album->caption .'</option>';
						}
					}
				}
				echo '</select>';
			echo '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td><b>Private albums</b></td>';
			echo '<td>';
				echo '<select name="album_id" onchange="if (this.value) window.location.href=this.value">';
				echo '<option value="'. admin_url('admin.php?page=picturelife') .'">Please select...</option>';
				foreach($album_data->albums as &$album) {
					if($album->privacy=='6') {
					
					} else {
						if(isset($_REQUEST['album_id'])) {
							if($_REQUEST['album_id']==$album->id) {
								echo '<option value="'. admin_url('admin.php?page=picturelife&album_id='. $album->id) .'" selected>'. $album->caption .'</option>';
							}
							else {
								echo '<option value="'. admin_url('admin.php?page=picturelife&album_id='. $album->id) .'">'. $album->caption .'</option>';
							}
						} else {
							echo '<option value="'. admin_url('admin.php?page=picturelife&album_id='. $album->id) .'">'. $album->caption .'</option>';
						}
					}
				}
				echo '</select>';
			echo '</td>';
			echo '</tr>';
		}
		
		$tag_data = json_decode(cache_url('https://api.picturelife.com/tags/index?access_token='. get_option('pl_access_token', '')));
		
		if($tag_data->status=='20000') {
			$tag_array = array();
			foreach($tag_data->tags as &$tag) {
				if(in_array($tag->tag, $tag_array)==false)
				{
					$tag_array[] = $tag->tag;
				}
			}
			
			natcasesort($tag_array);
			
			echo '<tr>';
			echo '<td><b>Tags</b></td>';
			echo '<td>';
				echo '<select name="tag" onchange="if (this.value) window.location.href=this.value">';
				echo '<option value="'. admin_url('admin.php?page=picturelife') .'">Please select...</option>';
				foreach($tag_array as &$tag) {
					if(isset($_REQUEST['tag'])) {
						if($_REQUEST['tag']==$tag) {
								echo '<option value="'. admin_url('admin.php?page=picturelife&tag='. $tag) .'" selected>'. $tag .'</option>';
						} else {
							echo '<option value="'. admin_url('admin.php?page=picturelife&tag='. $tag) .'">'. $tag .'</option>';
						}
					} else {
						echo '<option value="'. admin_url('admin.php?page=picturelife&tag='. $tag) .'">'. $tag .'</option>';
					}
				}
				echo '</select>';
			echo '</td>';
			echo '</tr>';
		}
		
		echo '</table>';
		
		if(isset($_REQUEST['album_id'])) {
			$album_id = $_REQUEST['album_id'];
			
			echo '<p>List the contents of the album.</p>';
			echo '<p><b>[picturelife_album id="'. $album_id .'"]</b></p>';
			
			echo '<p>List the contents of the album but hide the title.</p>';
			echo '<p><b>[picturelife_album id="'. $album_id .'" title="0"]</b></p>';
			
			echo '<p>List the contents of the album but hide the title and captions.</p>';
			echo '<p><b>[picturelife_album id="'. $album_id .'" title="0" caption="0"]</b></p>';
		}
		
		if(isset($_REQUEST['tag'])) {
			$tag = $_REQUEST['tag'];
			
			echo '<p>List all photos tagged as \''. $tag .'\'.</p>';
			echo '<p><b>[picturelife_tag tag="'. $tag .'"]</b></p>';
			
			echo '<p>List all photos tagged as \''. $tag .'\' but hide the captions.</p>';
			echo '<p><b>[picturelife_tag tag="'. $tag .'" caption="0"]</b></p>';
		}
	}
}

function pl_refresh_token($refresh_token) {
	$results = json_decode(cache_url('https://api.picturelife.com/oauth/access_token?client_id='. get_option('pl_app_key', '') .'&client_secret='. get_option('pl_app_secret', '') .'&grant_type=refresh_token&refresh_token='. $refresh_token .'&client_uuid='. md5($_SERVER['HTTP_HOST']) .'', true));
	
	if($results->status=='200') {
		update_option('pl_token', $results->token);
		update_option('pl_user_id', $results->user_id);
		update_option('pl_access_token', $results->access_token);
		update_option('pl_refresh_token', $results->refresh_token);
		update_option('pl_expires', $results->expires);
		update_option('pl_expires_in', $results->expires_in);
		update_option('pl_token_type', $results->token_type);
	}		
}

function pl_add_main_stylesheet() {
    wp_register_style('prefix-style', plugins_url('css/style.css', __FILE__));
    wp_enqueue_style('prefix-style');
}

function pl_truncate($text, $chars = 25) {
	if(strlen($text) >= $chars) {
    	$text = $text ." ";
		$text = substr($text,0,$chars);
		$text = substr($text,0,strrpos($text,' '));
		$text = $text ."...";
	} else {
		$text = $text;
	}
	
    return $text;
}

include_once('lib/album.php');
include_once('lib/tag.php');
?>