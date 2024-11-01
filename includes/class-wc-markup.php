<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://tallpro.lt
 * @since      1.0.0
 *
 * @package    Wc_Markup
 * @subpackage Wc_Markup/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.1.0
 * @package    Wc_Markup
 * @subpackage Wc_Markup/includes
 * @author     Deividas Ambrazevicius <info@tallpro.com>
 */
class Wc_Markup
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wc_Markup_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('WC_MARKUP_VERSION')) {
			$this->version = WC_MARKUP_VERSION;
		} else {
			$this->version = '1.7.7';
		}
		$this->plugin_name = 'wc-markup';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wc_Markup_Loader. Orchestrates the hooks of the plugin.
	 * - Wc_Markup_i18n. Defines internationalization functionality.
	 * - Wc_Markup_Admin. Defines all hooks for the admin area.
	 * - Wc_Markup_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-markup-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-markup-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-markup-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wc-markup-public.php';

		$this->loader = new Wc_Markup_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wc_Markup_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{
		$plugin_i18n = new Wc_Markup_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new Wc_Markup_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('woocommerce_process_product_meta', $plugin_admin, 'wc_markup_save_fields');
		$this->loader->add_action('admin_head', $plugin_admin, 'wc_markup_icon_change');
		$this->loader->add_action(
			'woocommerce_product_data_panels',
			$plugin_admin,
			'wc_markup_product_data_panels'
		);

		$this->loader->add_action(
			'woocommerce_variation_options_pricing',
			$plugin_admin,
			'wc_markup_variation_options_pricing',
			13,
			3
		);


		$this->loader->add_filter('woocommerce_product_data_tabs', $plugin_admin, 'wc_markup_product_data_tabs');
		$this->loader->add_filter('woocommerce_product_get_price', $plugin_admin, 'wc_markup_get_price', 10, 2);
		$this->loader->add_filter(
			'woocommerce_product_get_regular_price',
			$plugin_admin,
			'wc_markup_get_regular_price',
			10,
			2
		);

		$this->loader->add_filter(
			'woocommerce_get_sections_products',
			$plugin_admin,
			'wc_markup_product_settings_section',
			10,
			1
		);
		$this->loader->add_filter(
			'woocommerce_get_settings_products',
			$plugin_admin,
			'wc_markup_section_settings',
			10,
			2
		);

		$this->loader->add_action('admin_menu', $plugin_admin, 'wc_markup_admin_menu_page');
		$this->loader->add_action('admin_init', $plugin_admin, 'ignore_notice_wcmarkup');
		$this->loader->add_action('admin_notices', $plugin_admin, 'wc_markup_admin_notice');
		$this->loader->add_action('admin_notices', $plugin_admin, 'wc_markup_admin_notice_error');
		$this->loader->add_filter('plugin_action_links_wc-markup/wc-markup.php', $plugin_admin, 'add_action_link');

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$plugin_public = new Wc_Markup_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Wc_Markup_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version()
	{
		return $this->version;
	}

}
