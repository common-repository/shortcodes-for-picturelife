<?php
add_shortcode('picturelife_tag', 'pl_tag_shortcode');

function pl_tag_shortcode($atts) {
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
	
	if(isset($atts['tag'])) {
		$tag_data = json_decode(cache_url('https://api.picturelife.com/tags/get_media?access_token='. get_option('pl_access_token', '') .'&tag='. urlencode($atts['tag'])));
		
		if($tag_data->status=='20000') {
			$picture_found = false;
			
			$content .= '<ul id="picturelife">';
					
			foreach($tag_data->media as &$photo) {
				$show_photo = false;
						
				if($photo->privacy=='6') {
					$show_photo = true;
				}
						
				if($photo->visible=='0' && $show_photo==true) {
					$show_photo = false;
				}
						
				if($show_photo==true) {
					$picture_found = true;
					
					$caption_full = (string)$photo->caption;
					if($caption_full=='') {
						$caption_full = (string)$photo->original_file_name;
					}
					$caption_short = pl_truncate($caption_full, 40);
							
					$content .= '<li>';
						$content .= '<a href="https://api.picturelife.com/v/1000/'. $photo->id .'" title="'. $caption_full .'" rel="lightbox">';
						$content .= '<img src="'. plugins_url('3rdparty/timthumb.php', __FILE__) .'?w=320&h=210&zc=1&src=https://api.picturelife.com/v/400/'. $photo->id .'" border="0" alt="'. $caption_full .'" />';
								
						if($show_caption=='1') {
							$content .= '<br/><b>'. $caption_short .'</b>';
						}
								
						$content .= '</a>';
					$content .= '</li>';
				}
			}
					
			$content .= '</ul>';
			
			if(!$picture_found)
			{
				$content .= '<p>No public photographs could be found.</p>';
			}
		}		
	}
	
	return $content;
}