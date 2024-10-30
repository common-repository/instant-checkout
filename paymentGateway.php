<?php
add_action('plugins_loaded', 'instant_checkout_gateway_init');

function instant_checkout_gateway_init()
{
	class WC_Instant_Checkout_Gateway extends WC_Payment_Gateway
	{
		public function __construct()
		{
			$this->id                 = 'instant_checkout_gateway';
			$this->icon               = apply_filters('woocommerce_instant_icon', '');
			$this->has_fields         = false;
			$this->method_title       = __('Instant Checkout', 'wc-instant-checkout-gateway');
			$this->method_description = __('1 click checkout for WooCommerce', 'wc-instant-checkout-gateway');

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title        = $this->get_option('title');
			$this->description  = $this->get_option('description');
			$this->instructions = $this->get_option('instructions', $this->description);

			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		}

		public function init_form_fields()
		{

			$this->form_fields = apply_filters('wc_instant_form_fields', array(

				'enabled' => array(
					'title'   => __('Enable/Disable', 'wc-instant-checkout-gateway'),
					'type'    => 'checkbox',
					'label'   => __('Enable Instant Checkout', 'wc-instant-checkout-gateway'),
					'default' => 'yes'
				),

				'title' => array(
					'title'       => __('Title', 'wc-instant-checkout-gateway'),
					'type'        => 'text',
					'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-instant-checkout-gateway'),
					'default'     => __('Instant Checkout', 'wc-instant-checkout-gateway'),
					'desc_tip'    => true,
				),

				'description' => array(
					'title'       => __('Description', 'wc-instant-checkout-gateway'),
					'type'        => 'textarea',
					'description' => __('Payment method description that the customer will see on your checkout.', 'wc-instant-checkout-gateway'),
					'default'     => __('1 click checkout for WooCommerce', 'wc-instant-checkout-gateway'),
					'desc_tip'    => true,
				),

				'instructions' => array(
					'title'       => __('Instructions', 'wc-instant-checkout-gateway'),
					'type'        => 'textarea',
					'description' => __('Added instructions.', 'wc-instant-checkout-gateway'),
					'default'     => '',
					'desc_tip'    => true,
				),
			));
		}
	}
}

function add_instant_checkout_gateway($methods)
{
	$methods[] = 'WC_Instant_Checkout_Gateway';
	return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_instant_checkout_gateway');

//  Disable this gateway when not using Instant Checkout button flow
function payment_gateway_disable_instant_regular($available_gateways)
{
	if (is_null(WC()->cart)) {
		wc_load_cart();
	}

	if (isset($available_gateways['instant_checkout_gateway']) && (WC()->session->get("instant_checkout_session") !== "true")) {
		unset($available_gateways['instant_checkout_gateway']);
	}
	return $available_gateways;
}
add_filter('woocommerce_available_payment_gateways', 'payment_gateway_disable_instant_regular');
