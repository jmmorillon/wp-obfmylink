<?php

/*
  Plugin Name: ObfMyLink
  Plugin URI: https://lesiteduplugin.com
  Description: Obfuscation de lien dans Wordpress
  Author: DocteurMi sur une idée d'AffilSEO
  Author URI: http://www.gm-wci.com
  License: GPL2
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define('OBFML_Version', '0.2.0');

load_plugin_textdomain('obfml', false, basename( dirname( __FILE__ ) ) . '/languages' );

require_once(plugin_dir_path(__FILE__) . 'inc/simple_html_dom.php');
require_once(plugin_dir_path(__FILE__) . 'inc/functions.php');

_obfml_init();