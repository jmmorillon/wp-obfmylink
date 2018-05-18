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

require_once(plugin_dir_path(__FILE__) . 'inc/simple_html_dom.php');
require_once(plugin_dir_path(__FILE__) . 'inc/functions.php');

