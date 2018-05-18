<?php

defined('ABSPATH') or die('No script kiddies please!');

define('OBFML_Version', '0.1.0');

function _obfml_admin_menu() {
    add_options_page('ObfMyLink Options', 'ObfMyLink', 'manage_options', 'obfml-options', 'obfml_options');
}

function obfml_options() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    include(plugin_dir_path(__FILE__) . 'obfml-admin-page.php');
}

function obfml_scripts() {
    wp_enqueue_script('obfml', plugins_url('../js/obfml.js', __FILE__), array('jquery'), '0.1.0');
}

add_action('wp_enqueue_scripts', 'obfml_scripts');

function _obfml_start() {
    ob_start();
}

function _obfml_obfuscation() {

    $content = ob_get_clean();

    $html = str_get_html($content);
    foreach ($html->find('a') as $a) {
        if (preg_match('/^clkg(s?)\:\/\//', $a->href)) {
            $a->tag = 'span';
            $a->href = preg_replace('/^clkg/', 'http', $a->href);
            $a->rel = base64_encode($a->href);
            unset($a->href);
            $a->class = 'obf';
        } elseif (preg_match('/#clkg(s?)$/', $a->href)) {
            $a->tag = 'span';
            $a->href = preg_replace('/#clkg(s?)$/', '', $a->href);
            $a->rel = base64_encode($a->href);
            unset($a->href);
            $a->class = 'obf';
        }
    };

    $content = $html;
    $content = str_replace('href=""', '', $content);

    echo $content;

    ob_flush();
}

add_action('admin_menu', '_obfml_admin_menu');
add_action('wp_head', '_obfml_start');
add_action('wp_footer', '_obfml_obfuscation');
