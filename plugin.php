<?php
/**
 * Plugin Name:          Pressenter
 * Plugin URI:           https://10100.to/wp-plugins/pressenter
 * Description:          Web Presentation program
 * Version:              1.0.1
 * Author:               10100 S.R.L.
 * Author URI:           https://10100.to
 * Text Domain:          pressenter
 * License:              GPLv2 or later
 * Domain Path:          /languages
 * Requires at least:    5.3
 * Tested up to:         5.5.1
 *
 * @author  10100 Team
 * @package \10100\Plugin
 *
 * Copyright 2017-2020 10100 S.R.L. - All right reserved.
 */
namespace Pressenter;

require_once('includes/autoload.php');

$mainPressenter = new Main();

{ /** INSTALLATION HOOKS */
	register_activation_hook(__FILE__, [$mainPressenter, 'activate']);
	register_deactivation_hook(__FILE__, [$mainPressenter, 'deactivate']);
}

{ /** GENERAL HOOKS */
	add_action('init', [$mainPressenter, 'init']);
	add_action('wp_enqueue_scripts', [$mainPressenter, 'addJS'], 1);
	add_action('wp_enqueue_scripts', [$mainPressenter, 'addStyle'], 1);
	add_filter('single_template', [$mainPressenter, 'getSlideTemplate']);
	add_action('wp_footer', [$mainPressenter, 'sliderWrapper']);
	add_action('save_post_'.\Pressenter\SLIDE_SLUG, [$mainPressenter, 'bindSlide'], 10, 3);

	add_action('create_pressentation', [$mainPressenter,'addPresentationMeta']);
	add_action('edited_term', [$mainPressenter,'updatePresentationMeta'], 10, 3);
}

{ /** ADMIN HOOKS */
	add_action('admin_enqueue_scripts', [$mainPressenter, 'addAdminJS']);
	add_action('admin_menu', [$mainPressenter, 'editAdminMenu']);
	add_action('admin_enqueue_scripts', [$mainPressenter, 'addAdminStyle'], 1);
	add_action('load-edit.php', [$mainPressenter, 'goToPresentationPage']);
}

{ /** AJAX HOOKS */
	add_action('wp_ajax_getSlider', [$mainPressenter,'getSlider']);
	add_action('wp_ajax_nopriv_getSlider', [$mainPressenter,'getSlider']);
	add_action('wp_ajax_savePresentation', [$mainPressenter,'savePresentation']);
}

{ /** SHORTCODE HOOKS */
	add_shortcode('pressentation', [$mainPressenter, 'doShortCodes']);
}