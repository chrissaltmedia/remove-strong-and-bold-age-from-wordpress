<?php
/**
 * Plugin Name: Strip Bold Inside Headings (WP only)
 * Description: Unwraps bold tags (<strong> and <b>) inside H1–H6 for classic/Gutenberg content only. Skips all Elementor content/templates.
 * Version:     1.0.0
 * Author:      Salt Media LTD - Christopher Sheppard (chris@saltmedia.co.uk)
 * License:     GPL-2.0+
 */


if ( ! defined('ABSPATH') ) exit;

/**
 * Unwrap <strong>/<b> tags ONLY inside <h1>-<h6> blocks.
 * - Leaves all other markup untouched.
 * - Keeps heading attributes intact.
 */
function sm_strip_bold_inside_headings_fragment( string $html ): string {
	if ( trim($html) === '' ) return $html;

	return preg_replace_callback(
		'/<h([1-6])\b([^>]*)>(.*?)<\/h\1>/is',
		function ($m) {
			$level = $m[1];
			$attrs = $m[2] ?? '';
			$inner = $m[3] ?? '';

			// Unwrap any <strong>/<b> inside the heading content
			$inner = preg_replace('/<\/?(?:strong|b)\b[^>]*>/i', '', $inner);

			return "<h{$level}{$attrs}>{$inner}</h{$level}>";
		},
		$html
	);
}

/**
 * Apply to WordPress post/page/CPT content only.
 * Explicitly skip Elementor-built posts and Elementor templates.
 */
add_filter('the_content', function ($content) {
	// No admin/REST manipulation
	if ( is_admin() ) return $content;

	$post_id = get_the_ID();
	if ( ! $post_id ) return $content;

	// Skip Elementor library items entirely
	$post_type = get_post_type($post_id);
	if ( $post_type === 'elementor_library' ) return $content;

	// If Elementor is present and the post is built with Elementor, skip
	if ( defined('ELEMENTOR_VERSION') ) {
		// Fast, safe check: Elementor sets this meta when a post is edited with it
		$edit_mode = get_post_meta($post_id, '_elementor_edit_mode', true);
		if ( ! empty($edit_mode) ) return $content;
	}

	// Process classic/Gutenberg content only
	return sm_strip_bold_inside_headings_fragment($content);
}, 20);
