<?php 
/**
 * Init Styles & scripts
 *
 * @return void
 */
function instant_admin_styles_scripts() {

  wp_enqueue_style('instant-tagify-style', INSTANT_PLUGIN_URL . 'admin/css/tagify.css', '', rand());
  wp_enqueue_style('instant-admin-style', INSTANT_PLUGIN_URL . 'admin/css/admin.css', '', rand());

  wp_enqueue_script('instant-tagify-script', INSTANT_PLUGIN_URL . 'admin/js/tagify.min.js', '', rand(), true);
  wp_enqueue_script('instant-tagify-pollyfill-script', INSTANT_PLUGIN_URL . 'admin/js/tagify.pollyfill.min.js', '', rand(), true);
  wp_enqueue_script('instant-admin-script', INSTANT_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), rand(), true);
}

add_action( 'admin_enqueue_scripts', 'instant_admin_styles_scripts' );

/**
 * init instant_checkout_button_style
 */
$current_creds = get_option("instant_checkout_button_style");
if (!$current_creds) {
  update_option("instant_checkout_button_style", array(
    "checkout" => INSTANT_BUTTON_CLASSES,
    "product" => INSTANT_BUTTON_CLASSES,
    "cart" => INSTANT_BUTTON_CLASSES,
  ));
}