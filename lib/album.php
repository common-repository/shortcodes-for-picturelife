<?php
add_shortcode('picturelife_album', 'pl_album_shortcode');
add_shortcode('picturelife_albums', 'pl_albums_shortcode');

function pl_album_shortcode($atts) {
	$content = '';
	
	if(get_option('pl_expires', '0') - time() < 3600) {
		pl_refresh_token(get_option('pl_refresh_token', ''));
	}
	
	if(isset($atts['title'])) {
		if($atts['title']=='0') {
			$show_title = '0';
		} else {
			$show_title = '1';
		}
	} else {
		$show_title = '1';
	}
	
	if(isset($atts['caption'])) {
		if($atts['caption']=='0') {
			$show_caption = '0';
		} else {
			$show_caption = '1';
		}
	} else {
		$show_caption = '1';
	}
	
	if(isset($atts['id'])) {
		$album_data = json_decode(cache_url('https://api.picturelife.com/albums/get?access_token='. get_option('pl_access_token', '') .'&album_ids='. $atts['id'] .'&include_photos=true&limit=999'));
			
		if($album_data->status=='20000') {
			foreach($album_data->albums as &$album) {
				if($show_title=='1') {
					$content .= '<h4>'. $album->caption .'</h4>';
				}
					
				$content .= '<ul id="picturelife">';
					
				foreach($album->photos as &$photo) {
					$show_photo = false;
						
					if($album->privacy=='6') {
						$show_photo = true;
					}
					elseif($photo->privacy=='6') {
						$show_photo = true;
					}
						
					if($photo->visible=='0' && $show_photo==true) {
						$show_photo = false;
					}
						
					if($show_photo==true) {
						$caption_full = (string)$photo->caption;
						if($caption_full=='') {
							$caption_full = (string)$photo->original_file_name;
						}
						$caption_short = pl_truncate($caption_full, 40);
							
						$content .= '<li>';
							$content .= '<a href="https://api.picturelife.com/v/1000/'. $photo->id .'?album_id='. $album->id .'" title="'. $caption_full .'" rel="lightbox">';
							$content .= '<img src="'. plugins_url('3rdparty/timthumb.php', __FILE__) .'?w=320&h=210&zc=1&src=https://api.picturelife.com/v/400/'. $photo->id .'?album_id='. $album->id .'" border="0" alt="'. $caption_full .'" />';
								
							if($show_caption=='1') {
								$content .= '<br/><b>'. $caption_short .'</b>';
							}
								
							$content .= '</a>';
						$content .= '</li>';
					}
				}
					
				$content .= '</ul>';
				$content .= '<div class="clear"></div>';
			}
		}		
	}
	
	return $content;
}

function pl_albums_shortcode($atts) {
	$content = '';
	
	if(get_option('pl_expires', '0') - time() < 3600) {
		pl_refresh_token(get_option('pl_refresh_token', ''));
	}
	
	if(isset($atts['title'])) {
		if($atts['title']=='0') {
			$show_title = '0';
		} else {
			$show_title = '1';
		}
	} else {
		$show_title = '1';
	}
	
	if(isset($atts['caption'])) {
		if($atts['caption']=='0') {
			$show_caption = '0';
		} else {
			$show_caption = '1';
		}
	} else {
		$show_caption = '1';
	}
	
	if(get_query_var('album_id') != '') {
		$album_data = json_decode(cache_url('https://api.picturelife.com/albums/get?access_token='. get_option('pl_access_token', '') .'&album_ids='. get_query_var('album_id') .'&include_photos=true&limit=999'));
			
		if($album_data->status=='20000') {
			foreach($album_data->albums as &$album) {
				$content .= '<ul id="picturelife">';
					
				foreach($album->photos as &$photo) {
					$show_photo = false;
						
					if($album->privacy=='6') {
						$show_photo = true;
					}
					elseif($photo->privacy=='6') {
						$show_photo = true;
					}
						
					if($photo->visible=='0' && $show_photo==true) {
						$show_photo = false;
					}
					
					if($show_photo==true) {
						$caption_full = (string)$photo->caption;
						if($caption_full=='') {
							$caption_full = (string)$photo->original_file_name;
						}
						$caption_short = pl_truncate($caption_full, 40);
							
						$content .= '<li>';
							$content .= '<a href="https://api.picturelife.com/v/1000/'. $photo->id .'?album_id='. $album->id .'" title="'. $caption_full .'" rel="lightbox">';
							$content .= '<img src="'. plugins_url('3rdparty/timthumb.php', __FILE__) .'?w=320&h=210&zc=1&src=https://api.picturelife.com/v/400/'. $photo->id .'?album_id='. $album->id .'" border="0" alt="'. $caption_full .'" />';
								
							if($show_caption=='1') {
								$content .= '<br/><b>'. $caption_short .'</b>';
							}
								
							$content .= '</a>';
						$content .= '</li>';
					}
				}
					
				$content .= '</ul>';
				$content .= '<div class="clear"></div>';
			}
		}	
	} else {
		$album_data = json_decode(cache_url('https://api.picturelife.com/albums/index?access_token='. get_option('pl_access_token', '') .'&include_key_media=true&key_media_limit=1'));
			
		if($album_data->status=='20000') {
			$content .= '<ul id="picturelife">';
			
			foreach($album_data->albums as &$album) {
				$show_album = false;
						
				if($album->privacy=='6') {
					$show_album = true;
				}
						
				if($show_album==true) {
					$caption_full = (string)$album->caption;
					$caption_short = pl_truncate($caption_full, 40);
							
					$content .= '<li>';
						$content .= '<a href="/pictures/'. $album->id .'" title="'. $caption_full .'">';
						$content .= '<img src="'. plugins_url('3rdparty/timthumb.php', __FILE__) .'?w=320&h=210&zc=1&src=https://api.picturelife.com/v/400/'. $album->key_media_ids['0'] .'?album_id='. $album->id .'" border="0" alt="'. $caption_full .'" />';
								
						if($show_caption=='1') {
							$content .= '<br/><b>'. $caption_short .'</b>';
						}
								
						$content .= '</a>';
					$content .= '</li>';
				}
			}
			
			$content .= '</ul>';
			$content .= '<div class="clear"></div>';
		}		
	}
	
	return $content;
}