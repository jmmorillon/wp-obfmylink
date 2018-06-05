<?php

/*
  Plugin Name: OBFMyLink
  Plugin URI: https://www.jmmorillon.fr/wp-obfmylink
  Description: Obfuscation de lien dans Wordpress. 
  Author: Jean-Michel Morillon
  Author URI: https://twitter.com/docteur_mi
  Version: 0.3.2
  License: GPL2
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define('OBFML_Version', '0.3.2');

load_plugin_textdomain('obfml', false, basename( dirname( __FILE__ ) ) . '/languages' );

require_once(plugin_dir_path(__FILE__) . 'inc/simple_html_dom.php');
require_once(plugin_dir_path(__FILE__) . 'inc/gm-cloacking.class.php');
require_once(plugin_dir_path(__FILE__) . 'inc/functions.php');

//_obfml_init();

add_action( 'init', '_obfml_init' );