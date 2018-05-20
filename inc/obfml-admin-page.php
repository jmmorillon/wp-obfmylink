<?php

function obfml_form_settings() {
    register_setting('obfml_options', 'obfml_options');
    add_settings_section('obfml_main_section', 'Paramétrage', '', __FILE__);
    add_settings_field('amazon', 'Amazon : ', 'obfml_admin_amazon_radio', __FILE__, 'obfml_main_section');
}
add_action('admin_init', 'obfml_form_settings');

function obfml_admin_amazon_radio() {
    $options = get_option('obfml_options');
    echo '<input type="radio" name="obfml_options[amazon]" id="obfml-amazon" value="yes"';
    if ($options['amazon'] == 'yes')
        echo ' checked';
    echo '> Oui';
    echo '<input type="radio" name="obfml_options[amazon]" id="obfml-amazon" value="no"';
    if ($options['amazon'] != 'yes')
        echo ' checked';
    echo '> Non';
}

function obfml_options_page() {
?>
<div class="wrap">
    <h1>OBFMyLink version <?php echo OBFML_Version; ?></h1>
    <p>Obfuscation de lien par DocteurMi sur une idée de plugin WP de SEOAffil</p>

    <h2>Paramétrage</h2>

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
    <p>D'une complexité effarante ... ajoutez #clkg à la fin de vos liens.</p>
    <p>Le plugin interceptera le code HTML et substituera la balise &lt;a&gt; par une balise &lt;span&gt;, 
        puis transformera le lien du paramètre href en une version encodée base64 et transférée dans un paramètre rel.</p>
    <p>Le script JS se chargera après chargement du DOM par le navigateur, et au mouvement de la souris ou au scroll, de faire la transformation 
        inverse.</p>
    <p>L'utilisateur voit le lien, les moteurs ne le voit pas ! That's it !</p>
    <h2>Problèmes d'affichage ?</h2>
    <p>La transformation d'une balise &lt;a&gt; par une balise &lt;span&gt; entraine également l'application des CSS correspondantes, et donc provoque des effets visuels non souhaités. Pour résoudre le problème, il suffira de dupliquer les styles appliqués aux &lt;a&gt; pour les appliquer aux balises &lt;span class="obf"&gt;.</p>
</div>
<?php
};