<?php
    defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
?>
<div class="wrap">
  <h1>OBFMyLink version <?php echo OBFML_Version;?></h1>
  <p>Obfuscation de lien par DocteurMi sur une idée de plugin WP de SEOAffil</p>
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