<?php
	
	declare(strict_types=1);
	
	/**
	 * Plugin Name: SwissQR - wpShopGermany
	 * Text Domain: swissqr
	 * Domain Path: /lang
	 * Plugin URI: https://wpshopgermany.de/
	 * Description: Erstellung von SwissQR mit wpShopGermany für einfache Überweisungen
	 * Author: maennchen1.de
	 * Version: 1.0.1
	 * Author URI: http://maennchen1.de/
	 * License: GPLv2
	 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
	 */
	
	namespace wpsgSwissQr;
	
	\spl_autoload_register(function($class_name) {

	    $arPath = explode('\\', $class_name);

	    if ($arPath[0] === 'wpsgSwissQr') {

	        if (file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$arPath[1].'.php')) {

	            require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$arPath[1].'.php');

	        }

	    }

	} );
	
	\add_action('wpsg_order_done_afterAction', ['wpsgSwissQr\wpsgSwissQr', 'wpsg_order_done_afterAction']);
	\add_action('wpsg_sendMail', ['wpsgSwissQr\wpsgSwissQr', 'wpsg_sendMail']);
	\add_action('init', ['wpsgSwissQr\wpsgSwissQr', 'init']);
	\add_action('wpsg_loadModule', function($all) {
		
		require_once(__DIR__.'/mods/swissqr_mod_swissqr.class.php');

		$shop = &$GLOBALS['wpsg_sc'];

		if (!isset($shop)) return;

		if ($shop->isMultiBlog() && $shop->get_option('wpsg_multiblog_standalone', true) != '1') $global = true;
		else $global = false;

		if ($shop->get_option('wpsgswissqr_mod_swissqr', $global)) {

			$shop->arModule['wpsgswissqr_mod_swissqr'] = \wpsgswissqr_mod_swissqr::getInstance();
			
		} else {
			
			$shop->update_option('wpsgswissqr_mod_swissqr', time(), $global, false, 0);
			
			\swissqr_mod_swissqr::getInstance()->install();
			
			$shop->arModule['wpsgswissqr_mod_swissqr'] = \wpsgswissqr_mod_swissqr::getInstance();
			
		}
		
		if ($all) $shop->arAllModule['wpsgswissqr_mod_swissqr'] = \wpsgswissqr_mod_swissqr::getInstance();
		
	}, 10, 1);
	
	\add_filter('plugin_row_meta', ['wpsgSwissQr\wpsgSwissQr', 'plugin_row_meta'], 10, 4);