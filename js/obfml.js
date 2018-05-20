/**
 * ObfMyLink
 * version 0.1.0
 * Author: DocteurMi <docteurmi@hotmail.fr>
 * Date: 2018-05-17
 */

var obfuscation = false;

/**
 * Replacement d'un tag HTML
 */
jQuery.extend({
    replaceTag: function (currentElem, newTagObj, keepProps) {
        var $currentElem = jQuery(currentElem);
        var i, $newTag = jQuery(newTagObj).clone();
        if (keepProps) {
            newTag = $newTag[0];
            newTag.className = currentElem.className;
            jQuery.extend(newTag.classList, currentElem.classList);
            jQuery.extend(newTag.attributes, currentElem.attributes);
        }
        $currentElem.wrapAll($newTag);
        $currentElem.contents().unwrap();
        return this;
    }
});

jQuery.fn.extend({
    replaceTag: function (newTagObj, keepProps) {
        return this.each(function () {
            jQuery.replaceTag(this, newTagObj, keepProps);
        });
    }
});

/**
 * obfml_upd
 * Transforme le tag obfusqué en balise de lien
 */
function obfml_upd() {
    if (obfuscation == false) {
        jQuery(".obfml").each(function () {
            var url = atob(jQuery(this).attr("rel"));
            jQuery.replaceTag(this, jQuery('<a>').attr('href', url), true);
        });
        obfuscation = true;
        jQuery('a.obfml').removeClass('obfml');
    }
    
    jQuery('body').off('mousemove', 'body', obfml_upd());
    jQuery('body').off('scroll', 'body', obfml_upd());
    
    return '';
}

/**
 * Déclenche la transformation au déplacement de la souris ou au scroll de la page
 */
jQuery(document).ready(function () {
    jQuery('body').on('mousemove', 'body', obfml_upd());
    jQuery('body').on('scroll', 'body', obfml_upd());
});
