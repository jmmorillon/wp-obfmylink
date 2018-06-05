<?php
defined('ABSPATH') or die('No script kiddies please!');

$obfmlOptions = get_option('obfml_options', array());
$obfmlContext = array('cache' => 'no');

$obfmlCloacking = new gm_cloacking();

/**
 * Plugin Init
 */
function _obfml_init() {
    global $obfmlContext, $obfmlCloacking;

    _obfml_context_finder();

    define('OBFML_MARKER', 'obfml');

    add_action('admin_menu', '_obfml_admin_menu');

    if (!$obfmlCloacking->isBot()) {
        add_action('wp_enqueue_scripts', 'obfml_scripts');
    }

    switch ($obfmlContext['cache']) {
        case 'wp-super-cache':
            if (function_exists('add_cacheaction')) {
                add_filter('wpsupercache_buffer', '_obfml_wp_super_cache');
            }
            break;
        case 'no':
        default:
            add_action('wp_head', '_obfml_start');
            add_action('wp_footer', '_obfml_end');
    }
}

function _obfml_context_finder() {
    global $obfmlContext;

    if (function_exists('wpsc_init') && (defined('WP_CACHE') && $cache_enabled == true)) {
        $obfmlContext['cache'] = 'wp-super-cache';
    } else {
        $obfmlContext['cache'] = 'no';
    }
}

function _obfml_wp_super_cache($buffer) {
    return _obfml_obfuscation($buffer);
}

/**
 * Add admin plugin link
 */
function _obfml_admin_menu() {
    add_options_page(__('obfml', 'OBFMyLink Options'), 'OBFMyLink', 'administrator', plugin_basename(__FILE__), 'obfml_options_page');
}

function _obfml_settings_action_links($links, $file) {
    if ($file == 'obfmylink/obfmylink.php' && function_exists('admin_url')) {
        // lien vers la page de config de ce plugin
        array_unshift($links, '<a href="' . admin_url('options-general.php?page=' . plugin_basename(__FILE__)) . '">' . __('Settings') . '</a>');
    }
    return $links;
}

add_filter('plugin_action_links', '_obfml_settings_action_links', 10, 2);

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

function _obfml_end() {
    $content = ob_get_clean();

    echo _obfml_obfuscation($content);

    ob_flush();
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
    $elt->setAttribute('data-obfml', base64_encode($url));
    unset($elt->href);
    if ($elt->class != '')
        $elt->class .= ' ';
    $elt->class .= OBFML_MARKER;

    return $elt;
}

function obfml_encode($url) {
    
    $first_encode = base64_encode($url);
    $nbChar = strlen($first_encode);
    $cission = 0;
    if ($nbChar > 10) {
        $cission = rand(2, 9);
    }
    $second_encode = base64_encode($cission . substr($first_encode, $cission, $nbChar-$cission).substr($first_encode, 0, $cission));
    return $second_encode;
}

/**
 * Transform <a> with URL responding with regexp to <span> with URL 
 * encoded in base64 et setted to rel parameter
 */
function _obfml_obfuscation($content) {
    $options = get_option('obfml_options');

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
        } elseif (($options['nofollow'] == 'yes') && (preg_match('/nofollow/', $a->rel))) {
            $a = _obfml_tag_change($a, $a->href);
        } else {
            foreach ($options as $key => $value) {
                if (preg_match('/obfml\-regexp\-/', $key)) {
                    if (($value !== '') && (preg_match('/' . $value . '/', $a->href))) {
                        $a = _obfml_tag_change($a, $a->href);
                    }
                }
            }
        }
    }

    foreach ($options as $key => $value) {
        if (preg_match('/obfml\-css\-/', $key)) {
            if ($value !== '') {
                foreach ($html->find($value) as $a) {
                    $a = _obfml_tag_change($a, $a->href);
                }
            }
        }
    }

    $content = $html;
    $content = str_replace('href=""', '', $content);

    return $content;
}

/**
 * Define Admin Form for settings
 */
function obfml_form_settings() {
    $options = get_option('obfml_options');

    register_setting('obfml_options', 'obfml_options');
    add_settings_section('obfml_affiliate_section', 'Obfuscation sur les liens d\'affiliation', '', __FILE__);
    add_settings_field('amazon', 'Amazon : ', 'obfml_admin_amazon_radio', __FILE__, 'obfml_affiliate_section');
    add_settings_field('1tpe', '<a href="http://www.1tpe.com/index-pro.php?p=dwd1tpe" target="_blank">1TPE</a> : ', 'obfml_admin_1tpe_radio', __FILE__, 'obfml_affiliate_section');
    add_settings_field('clickbank', 'ClickBank : ', 'obfml_admin_clickbank_radio', __FILE__, 'obfml_affiliate_section');

    add_settings_section('obfml_regexp_section', 'Obfusquez vos propres cibles d\'URL', '', __FILE__);
    $nbRegexp = 1;
    foreach ($options as $key => $value) {
        if (preg_match('/obfml\-regexp\-/', $key)) {
            if ($value !== '') {
                add_settings_field($key, 'RegExp ' . $nbRegexp . ' : ', 'obfml_admin_regexp_fields', __FILE__, 'obfml_regexp_section', array('key' => $key));
                $nbRegexp++;
            }
        }
    }
    $key = 'obfml-regexp-' . date('YmdHis');
    add_settings_field($key, 'RegExp ' . $nbRegexp . ' : ', 'obfml_admin_regexp_fields', __FILE__, 'obfml_regexp_section', array('key' => $key));

    add_settings_section('obfml_cssselect_section', 'Obfusquez des liens par chemin de sélection CSS', '', __FILE__);
    add_settings_field('nofollow', 'Liens nofollow : ', 'obfml_admin_nofollow_radio', __FILE__, 'obfml_cssselect_section');

    $nbCSSSelector = 1;
    foreach ($options as $key => $value) {
        if (preg_match('/obfml\-css\-/', $key)) {
            if ($value !== '') {
                add_settings_field($key, 'CSS Path' . $nbCSSSelector . ' : ', 'obfml_admin_cssselector_fields', __FILE__, 'obfml_cssselect_section', array('key' => $key));
                $nbRegexp++;
            }
        }
    }
    $key = 'obfml-css-' . date('YmdHis');
    add_settings_field($key, 'CSS Path ' . $nbCSSSelector . ' : ', 'obfml_admin_cssselector_fields', __FILE__, 'obfml_cssselect_section', array('key' => $key));
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
 * Set Radio selector to activate Amazon Afiliate link
 */
function obfml_admin_nofollow_radio() {
    obfml_admin_add_radio('nofollow');
}

/**
 * Set Radio selector to activate Amazon Afiliate link
 */
function obfml_admin_comments_radio() {
    obfml_admin_add_radio('comments');
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
 * Set input field to define a regexp for link obfuscation
 */
function obfml_admin_cssselector_fields($datas) {
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
        <h1><?php echo __('OBFMyLink version', 'obfml') . ' ' . OBFML_Version; ?></h1>
        <p><?php echo __('Obfuscation de lien par', 'obfml'); ?> <a href="https://twitter.com/docteur_mi" target="_blank">DocteurMi</a></p>

        <form method="post" action="options.php">

            <?php
            settings_fields('obfml_options');
            do_settings_sections(__FILE__);
            ?>

            <p>
                <input type="submit" value="<?php _e('Save Changes'); ?>" />
            </p>
        </form>

        <h2><?php echo __('Mode d\'emploi', 'obfml'); ?></h2>
        <p><?php echo __('Ce plugin peut être utilisé de 5 manières :', 'obfml'); ?></p>
        <p>
        <ol>
            <li><?php echo __('Activez l\'obfuscation sur les liens d\'afilliation prédéfinis (Amazon, Clickbank, 1TPE)', 'obfml'); ?></li>
            <li><?php echo __('Ajoutez à la fin de vos liens', 'obfml') . ' : #' . OBFML_MARKER; ?></li>
            <li><?php echo __('Substituez le protocole http de vos liens par', 'obfml') . ' ' . OBFML_MARKER; ?><br><?php echo __('ex: http://monlien.fr devient ', 'obfml') . '' . OBFML_MARKER . '://' . __('monlien.fr', 'obfml'); ?></li>
            <li><?php echo __('Utilisez les champs d\'expression régulière REGEXP pour cibler vos propres liens à obfusquer', 'obfml'); ?></li>
            <li><?php echo __('Utilisez des sélecteurs CSS pour cibler vos propres liens à obfusquer', 'obfml'); ?></li>
        </ol>
    </p>
    <p><?php echo __('Le plugin interceptera le code HTML et substituera la balise &lt;a&gt; par une balise &lt;span&gt;, puis transformera le lien du paramètre href en une version encodée base64 et transférée dans un paramètre data-obfml.', 'obfml'); ?></p>
    <p><?php echo __('Le script JS se chargera après chargement du DOM par le navigateur, et au mouvement de la souris ou au scroll, de faire la transformation inverse.', 'obfml'); ?></p>
    <p><?php echo __('L\'utilisateur voit le lien, les moteurs ne le voit pas !', 'obfml'); ?></p>

    <h2><?php echo __('Support'); ?></h2>
    <p><?php echo __('Merci d\'utiliser le service GitHub pour toute demande concernant un bug ou une évolution : ', 'obfml'); ?><a href="https://github.com/jmmorillon/wp-obfmylink/issues" target="_blank">https://github.com/jmmorillon/wp-obfmylink/issues</a></p>

    <h2><?php echo __('Problèmes d\'affichage ?', 'obfml'); ?></h2>
    <p><?php echo __('La transformation d\'une balise &lt;a&gt; par une balise &lt;span&gt; entraine également l\'application des CSS correspondantes, et donc provoque des effets visuels non souhaités. Pour résoudre le problème, il suffira de dupliquer les styles appliqués aux &lt;a&gt; pour les appliquer aux balises &lt;span class="obf"&gt;.', 'obfml'); ?></p>

    <h2><?php echo __('Remerciement', 'obfml'); ?></h2>
    <p><?php echo __('Merci à SEOAffil pour les idées et les encouragements dans la réalisation de ce plugin.', 'obfml'); ?></p>
    </div>
    <?php
}

;
