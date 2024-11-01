<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wc_Markup
 * @subpackage Wc_Markup/admin
 * @author     Deividas Ambrazevicius <info@tallpro.com>
 */
class Wc_Markup_Admin {

	const MARKUP_PRICE_FIXED = 'fixed';
	const MARKUP_PRICE_PERCENTAGE = 'percentage';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.1
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.1
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/wc-markup-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.1
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/wc-markup-admin.min.js',
			array( 'jquery' ),
			$this->version,
			true
		);
		wp_localize_script(
			$this->plugin_name, 'markupforwc_ajax_object',
			array(
				'site_url' => get_site_url()
			)
		);
	}

	/**
	 * @param $price
	 * @param $product
	 *
	 * @return float|int|mixed|void
	 */
	public function wc_markup_get_price( $price, $product ) {
		$markup_price_woocommerce = get_option( 'wc_markup_sale_price' );
		wc_delete_product_transients( $product->get_id() );
		if ( $product->is_on_sale() && $markup_price_woocommerce === 'yes' ) {
			return $this->get_woocommerce_product_price( $product, $price );
		}

		if ( $product->is_on_sale() && $markup_price_woocommerce === 'no' ) {
			return $price;
		}

		return $this->get_woocommerce_product_price( $product, $price );
	}

	public function wc_markup_get_regular_price( $price, $product ) {
		return $this->get_woocommerce_product_price( $product, $price );
	}

	public function wc_markup_get_sale_price( $price, $product ) {
		return $price;
	}


	/**
	 * @param $product
	 * @param $productPrice
	 *
	 * @return float|int|mixed
	 */
	public function get_woocommerce_product_price( $product, $productPrice ) {
		$markup_product_id = $product->get_id();
		$markup_price      = get_post_meta( $markup_product_id, '_price_for_markup', true );
		$markup_price_type = get_post_meta( $markup_product_id, '_markup_pricing_type', true );

		return $this->extractedPrice(
			$markup_price,
			$markup_price_type,
			$productPrice
		);
	}


	public function getVariationsMinMaxPrices( $variations, $single = false, $price = null ) {
		if ( $single ) {
			$variationPrice              = $price;
			$markup_price_type_variation = $variations->get_meta( 'markup_price_type' );
			$markup_price                = $variations->get_meta( 'markup_price' );

			return $this->extractedPrice(
				$markup_price,
				$markup_price_type_variation,
				$variationPrice
			);
		}

		$prices = [];
		foreach ( $variations as $variation ) {
			$variationPrice              = $variation['display_price'];
			$markup_price_type_variation = $variation['markup_price_type'];
			$markup_price                = $variation['markup_price'];

			$prices[] = $this->extractedPrice(
				$markup_price,
				$markup_price_type_variation,
				$variationPrice
			);
		}

		return $prices;
	}

	/**
	 * @param $product_price_markup
	 * @param $product_markup_price_type
	 * @param $productPrice
	 * @param $finalProductPrice
	 *
	 * @return float|int|mixed
	 */
	public function extractedPrice( $product_price_markup, $product_markup_price_type, $productPrice ) {
		$productPrice         = floatval( $productPrice );
		$finalProductPrice    = $productPrice;
		$product_price_markup = floatval( $product_price_markup );

		if ( $product_price_markup > 0 && $product_markup_price_type === self::MARKUP_PRICE_FIXED ) {
			$finalProductPrice = $productPrice + $product_price_markup;
		}

		if ( $product_price_markup > 0 && $product_markup_price_type === self::MARKUP_PRICE_PERCENTAGE ) {
			$priceToIncrease   = $productPrice * ( $product_price_markup / 100 );
			$finalProductPrice = $productPrice + $priceToIncrease;
		}

		return $finalProductPrice;
	}


	public function wc_markup_before_calculate_totals( $cart ) {
		foreach ( $cart->get_cart() as $cartItemKey => $cartItem ) {
			$product                    = $cartItem['data'];
			$productPrice               = $product->get_price();
			$final_product_markup_price = $this->get_woocommerce_product_price( $product, $productPrice );

			$cartItem['data']->set_price( $final_product_markup_price );
		}
	}

	/**
	 * @param $postId
	 *
	 * @return WP_Error|boolean
	 */
	public function wc_markup_save_fields($postId) {
		$product = wc_get_product($postId);

		$markup_price_type = filter_var($_POST['_markup_pricing_type'], FILTER_SANITIZE_STRING);
		$markup_price      = filter_var($_POST['_price_for_markup'], FILTER_SANITIZE_STRING);

		$error = false;

		$currency = get_woocommerce_currency();
		$decimal_separator = wc_get_price_decimal_separator();

		if (!$markup_price_type) {
			$error      = true;
			$error_message = __("Markup Price Type should be a string", 'wpiron-wc-markup');
		}

		if ($markup_price === '') {
			$markup_price = '';
		} else {
			if (!preg_match('/^-?\d*[' . preg_quote($decimal_separator, '/') . ']?\d+$/', $markup_price)) {
				$error      = true;
				$error_message = sprintf(__("Markup Price should be a valid number with correct decimal separator '%s'", 'wpiron-wc-markup'), $decimal_separator);
			}
		}

		if ($error) {
			set_transient('wc_markup_admin_error_' . get_current_user_id(), $error_message, 45);
		} else {
			$markup_price_type = sanitize_text_field($markup_price_type);
			$priceForMarkup    = $markup_price !== '' ? sanitize_text_field($markup_price) : '';

			$product->update_meta_data('_markup_pricing_type', $markup_price_type);
			$product->update_meta_data('_price_for_markup', $priceForMarkup);
			$product->save();
		}
	}


	public function wc_markup_icon_change() {
		echo '<style>
                    #woocommerce-product-data ul.wc-tabs li.wc_markup_options a::before {
                        content: "\f108";
                    } 
                </style>';
	}

	public function wc_markup_product_data_panels() {
		?>
        <div id="wc_markup" class="panel woocommerce_options_panel hidden"><?php

		$options = [
			self::MARKUP_PRICE_PERCENTAGE => __( 'Percentage', 'wpiron-wc-markup' ),
			self::MARKUP_PRICE_FIXED      => __( 'Fixed', 'wpiron-wc-markup' ),
		];
		woocommerce_wp_select( [
			'id'            => '_markup_pricing_type',
			'label'         => __( 'Markup pricing type', 'wpiron-wc-markup' ),
			'wrapper_class' => 'show_if_simple',
			'options'       => $options,
//            'desc_tip' => 'true',
//            'description' => __('Enter the custom number here.', 'woocommerce'),
		] );

		woocommerce_wp_text_input( [
			'id'                => '_price_for_markup',
			'value'             => get_post_meta( get_the_ID(), '_price_for_markup', true ),
			'label'             => __( 'Price for markup', 'wpiron-wc-markup' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => '0.1',
				'min'  => '0'
			),
		] );

		?></div><?php
	}

	public function wc_markup_variation_options_pricing( $loop, $variationData, $variation ) {
		$options = [
			self::MARKUP_PRICE_PERCENTAGE => __( 'Percentage', 'wpiron-wc-markup' ),
			self::MARKUP_PRICE_FIXED      => __( 'Fixed', 'wpiron-wc-markup' ),
		];
		echo '<div class="options_group form_group"><br/>';
		echo '<a href="https://wpiron.com/products/markup-for-woocommerce/" target="_blank" style="color:green; font-weight: bold;">Upgrade To PREMIUM to use this feature!</a>';
		echo '<br/>';
		woocommerce_wp_select( [
			'id'            => 'markup_price_type_' . $loop,
			'name'          => 'markup_price_type[' . $loop . ']',
			'label'         => __( 'Markup pricing type', 'wpiron-wc-markup' ),
			'wrapper_class' => 'show_if_simple form-row form-row-first markup_type_variations',
			'options'       => $options,
			'value'         => get_post_meta( $variation->ID, 'markup_price_type', true ),
			'desc_tip'      => 'true',
			'custom_attributes' => array(
				'disabled' => 'disabled',
			),
		] );

		woocommerce_wp_text_input( [
			'id'                => 'markup_price_' . $loop,
			'name'              => 'markup_price[' . $loop . ']',
			'wrapper_class'     => 'form-row form-row-last markup_type_variations',
			'value'             => get_post_meta( $variation->ID, 'markup_price', true ),
			'label'             => __( 'Price for markup', 'wpiron-wc-markup' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			'custom_attributes' => array(
				'step' => 'any',
				'min'  => '0',
				'disabled' => 'disabled',
			),

		] );


		?>
        </div>
		<?php
	}

	public function wc_markup_product_data_tabs( $tabs ) {
		$tabs['wc_markup'] = [
			'label'    => __( 'Markup', 'wpiron-wc-markup' ),
			'target'   => 'wc_markup',
			'class'    => [ 'hide_if_variable', 'hide_if_external', 'hide_if_grouped' ],
			'priority' => 80
		];

		return $tabs;
	}

	public function wc_markup_product_settings_section( $sections ) {
		$sections['markups'] = __( 'Markups', 'wpiron-wc-markup' );

		return $sections;
	}

	public function wc_markup_section_settings( $settings, $current_section ) {
		if ( $current_section === 'markups' ) {
			$wc_markup_settings = array();
			// Add Title to the Settings
			$wc_markup_settings[] = array(
				'name' => __( 'Woocommerce Markups Settings', 'wpiron-wc-markup' ),
				'type' => 'title',
				'desc' => __(
					'The following options are used to configure WC Markup Plugin (<b>Settings disabled for FREE version</b> <strong><a style="color:green;" href="https://wpiron.com/products/markup-for-woocommerce/#pricing" target="_blank">Upgrade to premium now!</a></strong>)',
					'wpiron-wc-markup'
				),
				'id'   => 'markups_title'
			);

			$options = [
				self::MARKUP_PRICE_PERCENTAGE => __( 'Percentage', 'wpiron-wc-markup' ),
				self::MARKUP_PRICE_FIXED      => __( 'Fixed', 'wpiron-wc-markup' ),
			];

			$wc_markup_settings[] = array(
				'name' => __( 'Global Markup', 'wpiron-wc-markup' ),
				'type' => 'title',
				'desc' => __(
					'The following global markup settings will be applied to all products in WooCommerce.',
					'wpiron-wc-markup'
				),
				'id'   => 'global_markup_title'
			);


			$wc_markup_settings[] = array(
				'name'              => __( 'Markup Pricing Type', 'wpiron-wc-markup' ),
				'desc_tip'          => __(
					'Select the type of pricing for the markup.',
					'wpiron-wc-markup'
				),
				'id'                => '',
				'type'              => 'select',
				'options'           => $options,
				'disabled'          => true,
				'class'             => '',
				'css'               => 'min-width:300px;',
				'wrapper_class'     => 'show_if_simple',
				'custom_attributes' => array(
					'disabled' => 'disabled'
				),
			);

			// woocommerce_wp_text_input for Price for Markup
			$wc_markup_settings[] = array(
				'name'              => __( 'Price for Markup', 'wpiron-wc-markup' ),
				'desc_tip'          => __(
					'Enter the price which will be used for the markup calculation.',
					'wpiron-wc-markup'
				),
				'id'                => 'lalaila',
				'type'              => 'text',
				'custom_attributes' => array(
					'disabled' => 'disabled'
				),
			);

			$wc_markup_settings[] = array(
				'name'     => __( 'Sale Price Markup', 'wpiron-wc-markup' ),
				'desc_tip' => __(
					'Should WC Markup calculate markups on sale price? <br/>
                    For example: 10% markup on a 30$ regular price yields 3$(33$) markup
                    and if you set 20$ sale price and if this setting is ON then it will yields +2$(22$) for sale price.<br/>
                    <i>This setting affects products individually and takes effect when you update sale price for the product</i>',
					'wpiron-wc-markup'
				),
				'type'     => 'checkbox',
				'disabled' => true,
				'css'      => 'min-width:300px;',
				'desc'     => __( 'Enable', 'wpiron-wc-markup' ),
			);
			$wc_markup_settings[] = array(
				'name'     => __( 'Round Markup', 'wpiron-wc-markup' ),
				'desc_tip' => __(
					'Should WC Markup round final price? <br/>
                    Some stores want prices with specific numbers below the decimal place (such as xx.00 or xx.95).<br/>
                    Rounding markups will keep the value below the decimal intact<br/>
                    <i>This setting affects products individually and takes effect when you recalculate price for the product</i>',
					'wpiron-wc-markup'
				),
				'type'     => 'checkbox',
				'disabled' => true,
				'css'      => 'min-width:300px;',
				'desc'     => __( 'Enable', 'wpiron-wc-markup' ),
			);

			$wc_markup_settings[] = array( 'type' => 'sectionend', 'id' => 'wcmarkups' );

			return $wc_markup_settings;
			/**
			 * If not, return the standard settings
			 **/
		}

		return $settings;
	}

	public function wc_markup_admin_menu_page() {
		add_menu_page(
			$this->plugin_name,
			'WC Markup',
			'administrator',
			$this->plugin_name,
			array( $this, 'displayPluginAdminDashboard' ),
			'dashicons-money-alt',
			26
		);
	}

	public function displayPluginAdminDashboard() {
		require_once 'partials/' . $this->plugin_name . '-admin-display.php';
	}

	public function registerAndBuildFields__premium() {
		register_setting( 'mcfwc_plugin_options', 'mcfwc_plugin_options', 'mcfwc_plugin_options_validate' );
		add_settings_section( 'api_settings', 'API Settings', 'mcfwc_plugin_section_text', 'mcfwc_plugin' );

		add_settings_field(
			'mcfwc_plugin_setting_consumer_key',
			'WooCommerce Consumer Key',
			'mcfwc_plugin_setting_consumer_key',
			'mcfwc_plugin',
			'api_settings'
		);
		add_settings_field(
			'mcfwc_plugin_setting_secret_key',
			'WooCommerce Secret Key',
			'mcfwc_plugin_setting_secret_key',
			'mcfwc_plugin',
			'api_settings'
		);
	}

	public function mcfwc_plugin_section_text() {
		echo '<p>All the settings to make better user experience with Markup For WooCommerce plugin</p>';
	}

	public function mcfwc_plugin_setting_consumer_key() {
		$options = get_option( 'mcfwc_plugin_options' );
		echo "<input id='mcfwc_plugin_setting_consumer_key' name='mcfwc_plugin_options[consumer_key]' type='text' value='" . esc_attr(
				$options['consumer_key']
			) . "' />";
	}

	public function mcfwc_plugin_setting_secret_key() {
		$options = get_option( 'mcfwc_plugin_options' );
		echo "<input id='mcfwc_plugin_setting_secret_key' name='mcfwc_plugin_options[secret_key]' type='text' value='" . esc_attr(
				$options['secret_key']
			) . "' />";
	}


	public function displayPluginAdminSettings() {
		$tab = sanitize_text_field( esc_attr( $_GET['tab'] ) );
		// set this var to be used in the settings-display view
		$active_tab = isset( $tab ) ? $tab : 'general';
		if ( isset( $_GET['error_message'] ) ) {
			add_action( 'admin_notices', array( $this, 'pluginNameSettingsMessages' ) );
			do_action( 'admin_notices', $_GET['error_message'] );
		}
		require_once 'partials/' . $this->plugin_name . '-admin-settings-display.php';
	}

	public function add_action_link( $links ) {
		$url           = "https://wpiron.com/products/markup-for-woocommerce/";
		$url2          = "admin.php?page=wc-markup";
		$settings_link = '<a href="' . esc_url( $url2 ) . '"><b>' . esc_html( 'Settings' ) . '</b></a> | ';
		$settings_link .= '<a href="' . esc_url( $url ) . '"><b>' . esc_html( 'Get Premium' ) . ' 🚀</b></a>';
		$links[]       = $settings_link;

		return $links;
	}

	function wc_markup_admin_notice_error() {
		$user_id = get_current_user_id();
		$error_message = get_transient('wc_markup_admin_error_' . $user_id);
		if ($error_message) {
			echo '<div class="notice notice-error"><p>' . $error_message . '</p></div>';
			delete_transient('wc_markup_admin_error_' . $user_id);
		}
	}

	function wc_markup_admin_notice() {
		global $current_user;

		$siteUrl      = site_url();
		$uniqueUserId = md5( $siteUrl );

		$api_url = 'https://uwozfs6rgi.execute-api.us-east-1.amazonaws.com/prod/notifications';
		$body    = wp_json_encode( [
			'pluginName' => 'wpiron-wc-markup-free',
			'status'     => true,
			'user_id'    => $uniqueUserId
		], JSON_THROW_ON_ERROR );

		$args = [
			'body'        => $body,
			'headers'     => [
				'Content-Type' => 'application/json',
			],
			'method'      => 'POST',
			'data_format' => 'body',
		];

		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			return;
		}

		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true, 512 );
		$status_code = $data['statusCode'];

		if ( ! empty( $data ) && $status_code === 200 && $data['body'] !== '[]' ) {
			$dataEncoded = json_decode( $data['body'], true )[0];
			if ( $dataEncoded['content'] && $dataEncoded['dismissed'] === false ) {
				$content    = $dataEncoded['content'];
				$message_id = $dataEncoded['message_id']; // Get the message ID

				?>
                <div class="notice notice-success is-dismissible">
					<?php
					echo $content; ?>
                    <hr>
                    <a style="margin-bottom: 10px; position: relative; display: block;"
                       href="?quantity-discounts_-notice&message_id=<?php
					   echo urlencode( $message_id ); ?>"><b>Dismiss this notice</b></a>
                </div>
				<?php
			}
		}
	}

	public function ignore_notice_wcmarkup() {
		global $current_user;

		$siteUrl      = site_url();
		$uniqueUserId = md5( $siteUrl );

		if ( isset( $_GET['quantity-discounts_-notice'] ) ) {
			$message_id     = $_GET['message_id'];
			$apiRequestBody = wp_json_encode( array(
				'user_id'     => $uniqueUserId,
				'plugin_name' => 'wpiron-wc-markup-free',
				'message_id'  => $message_id,
			) );

			$apiResponse = wp_remote_post(
				'https://uwozfs6rgi.execute-api.us-east-1.amazonaws.com/prod/notifications',
				array(
					'body'    => $apiRequestBody,
					'headers' => array(
						'Content-Type' => 'application/json',
					),
				)
			);

			if ( is_wp_error( $apiResponse ) ) {
				$error_message = $apiResponse->get_error_message();

				return;
			}
		}
	}

	function wc_markup_custom_error_handler( $level, $message, $file, $line ) {
		$to      = 'support@wpiron.com';
		$subject = 'WP Plugin Error Detected';
		$body    = "An error occurred in the plugin: \n\n";
		$body    .= "Error Level: {$level} \n";
		$body    .= "Message: {$message} \n";
		$body    .= "File: {$file} \n";
		$body    .= "Line: {$line}";

		@mail( $to, $subject, $body );

		return true;
	}


}
