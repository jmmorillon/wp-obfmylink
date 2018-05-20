<?php
defined('ABSPATH') or die('No script kiddies please!');

$obfmlOptions = get_option('obfml_options', array());

function _obfml_init() {
    define('OBFML_MARKER', 'obfml');

    add_action('wp_enqueue_scripts', 'obfml_scripts');
    add_action('admin_menu', '_obfml_admin_menu');
    add_action('wp_head', '_obfml_start');
    add_action('wp_footer', '_obfml_obfuscation');
}

function _obfml_admin_menu() {
    add_options_page(__('obfml', 'ObfMyLink Options'), 'ObfMyLink', 'administrator', __FILE__, 'obfml_options_page');
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

function _obfml_start() {
    ob_start();
}

function _obfml_obfuscation() {
    $options = get_option('obfml_options');

    $content = ob_get_clean();

    $html = str_get_html($content);
    foreach ($html->find('a') as $a) {
        if (preg_match('/^' . OBFML_MARKER . '(s?)\:\/\//', $a->href)) {
            $a->tag = 'span';
            $a->href = preg_replace('/^' . OBFML_MARKER . '/', 'http', $a->href);
            $a->rel = base64_encode($a->href);
            unset($a->href);
            $a->class = OBFML_MARKER;
        } elseif (preg_match('/#' . OBFML_MARKER . '$/', $a->href)) {
            $a->tag = 'span';
            $a->href = preg_replace('/#' . OBFML_MARKER . '(s?)$/', '', $a->href);
            $a->rel = base64_encode($a->href);
            unset($a->href);
            $a->class = OBFML_MARKER;
        } elseif (($options['amazon'] === 'yes') && 
                ((preg_match('/^https?\:\/\/www\.amazon\.fr\//', $a->href))) || (preg_match('/^https?\:\/\/amzn\.to\//', $a->href))) {
            $a->tag = 'span';
            $a->rel = base64_encode($a->href);
            unset($a->href);
            $a->class = OBFML_MARKER;
        }
    };



    $content = $html;
    $content = str_replace('href=""', '', $content);

    echo $content;

    ob_flush();
}

function obfml_form_settings() {
    $options = get_option('obfml_options');

    register_setting('obfml_options', 'obfml_options');
    add_settings_section('obfml_main_section', 'Paramétrage', '', __FILE__);
    add_settings_field('amazon', 'Amazon : ', 'obfml_admin_amazon_radio', __FILE__, 'obfml_main_section');
    add_settings_field('1tpe', '1TPE : ', 'obfml_admin_1tpe_radio', __FILE__, 'obfml_main_section');

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

function obfml_admin_amazon_radio() {
    $options = get_option('obfml_options');
    echo '<input type="radio" name="obfml_options[amazon]" id="obfml-amazon" value="yes"';
    if ($options['amazon'] == 'yes')
        echo ' checked';
    echo '> Oui ';
    echo '<input type="radio" name="obfml_options[amazon]" id="obfml-amazon" value="no"';
    if ($options['amazon'] != 'yes')
        echo ' checked';
    echo '> Non ';
}

function obfml_admin_1tpe_radio() {
    $options = get_option('obfml_options');
    echo '<input type="radio" name="obfml_options[1tpe]" id="obfml-1tpe" value="yes"';
    if ($options['1tpe'] == 'yes')
        echo ' checked';
    echo '> Oui ';
    echo '<input type="radio" name="obfml_options[1tpe]" id="obfml-1tpe" value="no"';
    if ($options['1tpe'] != 'yes')
        echo ' checked';
    echo '> Non ';
}

function obfml_admin_regexp_fields($datas) {
    $options = get_option('obfml_options');

    echo '<div>';
    echo '<input type="text" name="obfml_options[' . $datas['key'] . ']" id="' . $datas['key'] . '" value="' . esc_attr($options[$datas['key']]) . '">';
    echo '</div>';
}

function obfml_options_page() {
    ?>
    <div class="wrap">
        <h1>OBFMyLink version <?php echo OBFML_Version; ?></h1>
        <p>Obfuscation de lien par DocteurMi</p>

        <form method="post" action="options.php">

            <?php
            settings_fields('obfml_options');
            do_settings_sections(__FILE__);
            ?>

            <p>
                <input type="submit" value="<?php _e('Save Changes'); ?>" />
            </p>
        </form>

        <h2>Mode d'emploi</h2>
        <p>D'une complexité effarante ... ajoutez <strong>#<?php echo OBFML_MARKER; ?></strong> à la fin de vos liens.</p>
        <p>Le plugin interceptera le code HTML et substituera la balise &lt;a&gt; par une balise &lt;span&gt;, 
            puis transformera le lien du paramètre href en une version encodée base64 et transférée dans un paramètre rel.</p>
        <p>Le script JS se chargera après chargement du DOM par le navigateur, et au mouvement de la souris ou au scroll, de faire la transformation 
            inverse.</p>
        <p>L'utilisateur voit le lien, les moteurs ne le voit pas ! That's it !</p>
        <h2>Problèmes d'affichage ?</h2>
        <p>La transformation d'une balise &lt;a&gt; par une balise &lt;span&gt; entraine également l'application des CSS correspondantes, et donc provoque des effets visuels non souhaités. Pour résoudre le problème, il suffira de dupliquer les styles appliqués aux &lt;a&gt; pour les appliquer aux balises &lt;span class="obf"&gt;.</p>
        <h2>Remerciement</h2>
        <p>Merci à SEOAffil pour les idées et les encouragements dans la réalisation de ce plugin.</p>
    </div>
    <?php
}

;
