<?php
defined('ABSPATH') or die('No script kiddies please!');

$obfmlOptions = get_option('obfml_options', array());

/**
 * Plugin Init
 */
function _obfml_init() {
    define('OBFML_MARKER', 'obfml');

    add_action('wp_enqueue_scripts', 'obfml_scripts');
    add_action('admin_menu', '_obfml_admin_menu');
    add_action('wp_head', '_obfml_start');
    add_action('wp_footer', '_obfml_obfuscation');
}

/**
 * Add admin plugin link
 */
function _obfml_admin_menu() {
    add_options_page(__('obfml', 'ObfMyLink Options'), 'ObfMyLink', 'administrator', __FILE__, 'obfml_options_page');
}

/**
 * Add JS to front
 */
function obfml_scripts() {
    wp_enqueue_script('obfml', plugins_url('../js/obfml.js', __FILE__), array('jquery'), '0.1.0');
}

/**
 * Start caching HTML code
 */
function _obfml_start() {
    ob_start();
}

/**
 * Change Tag in DOM to <span>
 * Define parameter rel with base64 coded URL and set class to OBFML_MARKER
 * 
 * @param object $elt DOM element
 * @param string $url URL to encode in base64
 * @return object DOM element 
 */
function _obfml_tag_change($elt, $url) {
    $elt->tag = 'span';
    $elt->rel = base64_encode($url);
    unset($elt->href);
    $elt->class .= ' ' . OBFML_MARKER;

    return $elt;
}

/**
 * Transform <a> with URL responding with regexp to <span> with URL 
 * encoded in base64 et setted to rel parameter
 */
function _obfml_obfuscation() {
    $options = get_option('obfml_options');

    $content = ob_get_clean();

    $html = str_get_html($content);
    foreach ($html->find('a') as $a) {
        if (preg_match('/^' . OBFML_MARKER . '(s?)\:\/\//', $a->href)) {
            $url = preg_replace('/^' . OBFML_MARKER . '/', 'http', $a->href);
            $a = _obfml_tag_change($a, $url);
        } elseif (preg_match('/#' . OBFML_MARKER . '$/', $a->href)) {
            $url = preg_replace('/#' . OBFML_MARKER . '(s?)$/', '', $a->href);
            $a = _obfml_tag_change($a, $url);
        } elseif (($options['amazon'] == 'yes') &&
                (preg_match('/^https?\:\/\/www\.amazon\.fr\//', $a->href) || preg_match('/^https?\:\/\/amzn\.to\//', $a->href))) {
            $a = _obfml_tag_change($a, $a->href);
        } elseif (($options['1tpe'] == 'yes') &&
                (preg_match('/^https?\:\/\/(go|pay)\.[a-z0-9\.]+\.1tpe\.net/', $a->href))) {
            $a = _obfml_tag_change($a, $a->href);
        } elseif (($options['clickbank'] == 'yes') && (preg_match('/^https?\:\/\/[a-z0-9\.]+\.clickbank\.net/', $a->href))) {
            $a = _obfml_tag_change($a, $a->href);
        } else {
            foreach ($options as $key => $value) {
                if (preg_match('/obfml\-/', $key)) {
                    if (($value !== '') && (preg_match('/' . $value . '/', $a->href))) {
                        $a = _obfml_tag_change($a, $a->href);
                    }
                }
            }
        }
    }

    $content = $html;
    $content = str_replace('href=""', '', $content);

    echo $content;

    ob_flush();
}

/**
 * Define Admin Form for settings
 */
function obfml_form_settings() {
    $options = get_option('obfml_options');

    register_setting('obfml_options', 'obfml_options');
    add_settings_section('obfml_main_section', 'Paramétrage', '', __FILE__);
    add_settings_field('amazon', 'Amazon : ', 'obfml_admin_amazon_radio', __FILE__, 'obfml_main_section');
    add_settings_field('1tpe', '1TPE : ', 'obfml_admin_1tpe_radio', __FILE__, 'obfml_main_section');
    add_settings_field('clickbank', 'ClickBank : ', 'obfml_admin_clickbank_radio', __FILE__, 'obfml_main_section');

    $nbRegexp = 1;
    foreach ($options as $key => $value) {
        if (preg_match('/obfml\-/', $key)) {
            if ($value !== '') {
                add_settings_field($key, 'RegExp ' . $nbRegexp . ' : ', 'obfml_admin_regexp_fields', __FILE__, 'obfml_main_section', array('key' => $key));
                $nbRegexp++;
            }
        }
    }
    $key = 'obfml-' . date('YmdHis');
    add_settings_field($key, 'RegExp ' . $nbRegexp . ' : ', 'obfml_admin_regexp_fields', __FILE__, 'obfml_main_section', array('key' => $key));
}

add_action('admin_init', 'obfml_form_settings');

/**
 * show radio input to activate or not, predefined affiliate program link
 * @param string $target
 */
function obfml_admin_add_radio($target) {
    $options = get_option('obfml_options');
    echo '<input type="radio" name="obfml_options[' . $target . ']" id="obfml-' . $target . '" value="yes"';
    if ($options[$target] == 'yes')
        echo ' checked';
    echo '> Oui ';
    echo '<input type="radio" name="obfml_options[' . $target . ']" id="obfml-' . $target . '" value="no"';
    if ($options[$target] != 'yes')
        echo ' checked';
    echo '> Non ';
}

/**
 * Set Radio selector to activate Amazon Afiliate link
 */
function obfml_admin_amazon_radio() {
    obfml_admin_add_radio('amazon');
}

/**
 * Set Radio selector to activate 1TPE Afiliate link
 */
function obfml_admin_1tpe_radio() {
    obfml_admin_add_radio('1tpe');
}

/**
 * Set Radio selector to activate ClickBank Afiliate link
 */
function obfml_admin_clickbank_radio() {
    obfml_admin_add_radio('clickbank');
}

/**
 * Set input field to define a regexp for link obfuscation
 */
function obfml_admin_regexp_fields($datas) {
    $options = get_option('obfml_options');

    echo '<div>';
    echo '<input type="text" name="obfml_options[' . $datas['key'] . ']" id="' . $datas['key'] . '" value="' . esc_attr($options[$datas['key']]) . '">';
    echo '</div>';
}

/**
 * Options page for obfml plugin
 */
function obfml_options_page() {
    ?>
    <div class="wrap">
        <h1><?php echo __('OBFMyLink version', 'obfml').' '.OBFML_Version; ?></h1>
        <p><?php echo __('Obfuscation de lien par', 'obfml');?> <a href="https://twitter.com/docteur_mi" target="_blank">DocteurMi</a></p>

        <form method="post" action="options.php">

            <?php
            settings_fields('obfml_options');
            do_settings_sections(__FILE__);
            ?>

            <p>
                <input type="submit" value="<?php _e('Save Changes'); ?>" />
            </p>
        </form>

        <h2><?php echo __('Mode d\'emploi', 'obfml');?></h2>
        <p><?php echo __('Ce plugin peut être utilisé de 4 manières :', 'obfml');?></p>
        <p>
        <ol>
            <li><?php echo __('Activez l\'obfuscation sur les liens d\'afilliation prédéfinis (Amazon, Clickbank, 1TPE)', 'obfml');?></li>
            <li><?php echo __('Ajoutez à la fin de vos liens', 'obfml').' : #'.OBFML_MARKER;?></li>
            <li><?php echo __('Substituez le protocole http de vos liens par', 'obfml').' '.OBFML_MARKER;?><br><?php echo __('ex: http://monlien.fr devient ', 'obfml').''.OBFML_MARKER.'://'.__('monlien.fr', 'obfml');?></li>
            <li><?php echo __('Utilisez les champs d\'expression régulière REGEXP pour cibler vos propres cibles de liens à obfusquer', 'obfml');?></li>
            </ol>
        </p>
        <p><?php echo __('Le plugin interceptera le code HTML et substituera la balise &lt;a&gt; par une balise &lt;span&gt;, puis transformera le lien du paramètre href en une version encodée base64 et transférée dans un paramètre rel.', 'obfml');?></p>
        <p><?php echo __('Le script JS se chargera après chargement du DOM par le navigateur, et au mouvement de la souris ou au scroll, de faire la transformation inverse.', 'obfml');?></p>
        <p><?php echo __('L\'utilisateur voit le lien, les moteurs ne le voit pas !', 'obfml');?></p>
        
        <h2><?php echo __('Support');?></h2>
        <p><?php echo __('Merci d\'utiliser le service GitHub pour toute demande concernant un bug ou une évolution : ', 'obfml');?><a href="https://github.com/jmmorillon/wp-obfmylink/issues" target="_blank">https://github.com/jmmorillon/wp-obfmylink/issues</a></p>
        
        <h2><?php echo __('Problèmes d\'affichage ?', 'obfml');?></h2>
        <p><?php echo __('La transformation d\'une balise &lt;a&gt; par une balise &lt;span&gt; entraine également l\'application des CSS correspondantes, et donc provoque des effets visuels non souhaités. Pour résoudre le problème, il suffira de dupliquer les styles appliqués aux &lt;a&gt; pour les appliquer aux balises &lt;span class="obf"&gt;.', 'obfml');?></p>
        
        <h2><?php echo __('Remerciement', 'obfml');?></h2>
        <p><?php echo __('Merci à SEOAffil pour les idées et les encouragements dans la réalisation de ce plugin.', 'obfml');?></p>
    </div>
    <?php
};
