<?php
	///////////////////////////////////////////////////////////////////////////
	/////////////////////////// JW AJAX API plugin ////////////////////////////
	///////////////////////////////////////////////////////////////////////////
	
	
	class WPAJAXAPI {
		function __construct() {
			add_action('init', array($this, 'run'));
		}
		function run() {
			if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'wpajaxapi') {
				header('content-Type: application/json; charset=utf-8');
				$input = json_decode(file_get_contents('php://input'), true);
				
				if(isset($_SERVER['HTTP_X_WPAJAXCOMMAND'])) {
					if($_SERVER['HTTP_X_WPAJAXCOMMAND'] == 'queryNext') {
						die(json_encode($this->next($input[0])));
					}
					elseif($_SERVER['HTTP_X_WPAJAXCOMMAND'] == 'queryPrevious') {
						die(json_encode($this->previous($input[0])));
					}
					else {
						die(json_encode($this->query($input)));
					}
				}
				else {
					die(json_encode($this->query($input)));
				}
			}
		}
		function query($args) {
			global $post;
			$saved_post = $post;
			
			$posts = array();
			$query = new WP_Query($args);
			
			while($query->have_posts()) {
				$query->the_post();
				
				$post->permalink = get_permalink($post->ID);
				
				if(!isset($post->terms)) {
					$post->terms = array();
				}
				
				foreach(get_object_taxonomies($post->post_type, 'names') as $tax) {
					$post->terms = array_merge($post->terms, array($tax => wp_get_post_terms($post->ID, $tax)));
				}
				
				$post->post_meta = get_post_meta($post->ID, '', true);
				
				foreach($post->post_meta as $key => $value) {
					if(is_array($value) && count($value) == 1) {
						$post->post_meta[$key] = $value[0];
					}
				}
				
				$post->post_date_formatted = date('d.m.Y', strtotime($post->post_date));
				$post->post_title_filtered = apply_filters('the_title', $post->post_title);
				$post->post_content_filtered = apply_filters('the_content', $post->post_content);
				
				if(get_previous_post()) {
					$post->has_next_post = 'yes';
				}
				else {
					$post->has_next_post = 'no';
				}
				
				if(isset($args['thumbnail_size'])) {
					if(has_post_thumbnail()) {
						$thumb_id  = get_post_thumbnail_id();
						$thumb_url = wp_get_attachment_image_src($thumb_id, $args['thumbnail_size']);
						
						$post->thumbnail_image = $thumb_url;
					}
				}
				
				array_push($posts, $post);
			}
			
			wp_reset_postdata();
			
			if($saved_post) {
				$post = $saved_post;
			}
			
			return $posts;
		}
		function next($args) {
			global $post;
			$post = get_post($args['id']);
			$post = get_next_post();
			$post->post_title_filtered = apply_filters('the_title', $post->post_title);
			$post->post_content_filtered = apply_filters('the_content', $post->post_content);
			
			if(has_post_thumbnail()) {
				$thumb_id  = get_post_thumbnail_id();
				$thumbnail = wp_get_attachment_image_src($thumb_id, $args['thumbnail_size']);
				$post->thumbnail_image = $thumbnail;
			}
			else {
				$post->thumbnail_image = 0;
			}
			
			if($prev_post = get_previous_post()) {
				$post->previous_post = $prev_post->ID;
			}
			else {
				$post->previous_post = 0;
			}
			
			if($next_post = get_next_post()) {
				$post->next_post = $next_post->ID;
			}
			else {
				$post->next_post = 0;
			}
			
			return (is_object($post)) ? $post : (object)array();
		}
		function previous($args) {
			global $post;
			$post = get_post($args['id']);
			$post = get_previous_post();
			$post->post_title_filtered = apply_filters('the_title', $post->post_title);
			$post->post_content_filtered = apply_filters('the_content', $post->post_content);
			
			if(has_post_thumbnail()) {
				$thumb_id  = get_post_thumbnail_id();
				$thumbnail = wp_get_attachment_image_src($thumb_id, $args['thumbnail_size']);
				$post->thumbnail_image = $thumbnail;
			}
			else {
				$post->thumbnail_image = 0;
			}
			
			if(isset($args['thumbnail_size'])) {
				if(has_post_thumbnail()) {
					$thumb_id  = get_post_thumbnail_id();
					$thumb_url = wp_get_attachment_image_src($thumb_id, $args['thumbnail_size']);
					
					$post->thumbnail_image = $thumb_url;
				}
			}
			else {
				if(has_post_thumbnail()) {
					$thumb_id  = get_post_thumbnail_id();
					$thumb_url = wp_get_attachment_image_src($thumb_id);
					
					$post->thumbnail_image = $thumb_url;
				}
			}
			
			if($prev_post = get_previous_post()) {
				$post->previous_post = $prev_post->ID;
			}
			else {
				$post->previous_post = 0;
			}
			
			if($next_post = get_next_post()) {
				$post->next_post = $next_post->ID;
			}
			else {
				$post->next_post = 0;
			}
			
			return (is_object($post)) ? $post : (object)array();
		}
	}
	
	new WPAJAXAPI();
?>
