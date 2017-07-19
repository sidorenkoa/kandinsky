<?php
/**
 * Envato Theme Setup Wizard Class
 *
 * Takes new users through some basic steps to setup their ThemeForest theme.
 *
 * @author      dtbaker
 * @author      vburlak
 * @package     envato_wizard
 * @version     1.3.0
 *
 *
 * 1.2.0 - added custom_logo
 * 1.2.1 - ignore post revisioins
 * 1.2.2 - elementor widget data replace on import
 * 1.2.3 - auto export of content.
 * 1.2.4 - fix category menu links
 * 1.2.5 - post meta un json decode
 * 1.2.6 - post meta un json decode
 * 1.2.7 - elementor generate css on import
 * 1.2.8 - backwards compat with old meta format
 * 1.2.9 - theme setup auth
 * 1.3.0 - ob_start fix
 *
 * Based off the WooThemes installer.
 *
 *
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Envato_Theme_Setup_Wizard' ) ) {
	/**
	 * Envato_Theme_Setup_Wizard class
	 */
	class Envato_Theme_Setup_Wizard {

		/**
		 * The class version number.
		 *
		 * @since 1.1.1
		 * @access private
		 *
		 * @var string
		 */
		protected $version = '1.3.0';

		/** @var string Current theme name, used as namespace in actions. */
		protected $theme_name = '';

		/** @var string Theme author username, used in check for oauth. */
		protected $envato_username = '';

		/** @var string Full url to server-script.php (available from https://gist.github.com/dtbaker ) */
//		protected $oauth_script = '';

		/** @var string Current Step */
		protected $step = '';

		/** @var array Steps for the setup wizard */
		protected $steps = array();

		/**
		 * Relative plugin path
		 *
		 * @since 1.1.2
		 *
		 * @var string
		 */
		protected $plugin_path = '';

		/**
		 * Relative plugin url for this plugin folder, used when enquing scripts
		 *
		 * @since 1.1.2
		 *
		 * @var string
		 */
		protected $plugin_url = '';

		/**
		 * The slug name to refer to this menu
		 *
		 * @since 1.1.1
		 *
		 * @var string
		 */
		protected $page_slug;

		/**
		 * TGMPA instance storage
		 *
		 * @var object
		 */
		protected $tgmpa_instance;

		/**
		 * TGMPA Menu slug
		 *
		 * @var string
		 */
		protected $tgmpa_menu_slug = 'knd-install-plugins';

		/**
		 * TGMPA Menu url
		 *
		 * @var string
		 */
		protected $tgmpa_url = 'themes.php?page=knd-install-plugins';

		/**
		 * The slug name for the parent menu
		 *
		 * @since 1.1.2
		 *
		 * @var string
		 */
		protected $page_parent;

		/**
		 * Complete URL to Setup Wizard
		 *
		 * @since 1.1.2
		 *
		 * @var string
		 */
		protected $page_url;

		/**
		 * @since 1.1.8
		 *
		 */
		public $site_styles = array();

		/**
		 * Holds the current instance of the theme manager
		 *
		 * @since 1.1.3
		 * @var Envato_Theme_Setup_Wizard
		 */
		private static $instance = null;

		/**
		 * @since 1.1.3
		 *
		 * @return Envato_Theme_Setup_Wizard
		 */
		public static function get_instance() {

			if( !self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;

		}

		/**
		 * A dummy constructor to prevent this class from being loaded more than once.
		 *
		 * @see Envato_Theme_Setup_Wizard::instance()
		 *
		 * @since 1.1.1
		 * @access private
		 */
		public function __construct() {

			$this->init_globals();
			$this->init_actions();

		}

		/**
		 * Get the default style. Can be overriden by theme init scripts.
		 *
		 * @see Envato_Theme_Setup_Wizard::instance()
		 *
		 * @since 1.1.7
		 * @access public
		 */
		public function get_default_theme_style() {
			return 'style1';
		}

		/**
		 * Get the default style. Can be overriden by theme init scripts.
		 *
		 * @see Envato_Theme_Setup_Wizard::instance()
		 *
		 * @since 1.1.9
		 * @access public
		 */
		public function get_header_logo_width() {
			return '100%'; //'200px';
		}

		/**
		 * Get the default style. Can be overriden by theme init scripts.
		 *
		 * @see Envato_Theme_Setup_Wizard::instance()
		 *
		 * @since 1.1.9
		 * @access public
		 */
		public function get_logo_image() {
			return apply_filters('envato_setup_logo_image', knd_get_logo_img());
		}

		/**
		 * Setup the class globals.
		 *
		 * @since 1.1.1
		 * @access public
		 */
		public function init_globals() {
			$current_theme         = wp_get_theme();
			$this->theme_name      = strtolower( preg_replace( '#[^a-zA-Z]#', '', $current_theme->get( 'Name' ) ) );
//			$this->envato_username = apply_filters( $this->theme_name . '_theme_setup_wizard_username', 'dtbaker' );
//			$this->oauth_script    = apply_filters( $this->theme_name . '_theme_setup_wizard_oauth_script', 'http://dtbaker.net/files/envato/wptoken/server-script.php' );
			$this->page_slug       = 'knd-setup-wizard';//apply_filters( $this->theme_name . '_theme_setup_wizard_page_slug', $this->theme_name . '-setup' );
			$this->parent_slug     = apply_filters( $this->theme_name . '_theme_setup_wizard_parent_slug', '' );

			$this->site_styles = array(
                'color-line' => __('Social problem oriented charity organization', 'knd'),
            );

			//If we have parent slug - set correct url
			if ( $this->parent_slug !== '' ) {
				$this->page_url = 'admin.php?page=' . $this->page_slug;
			} else {
				$this->page_url = 'themes.php?page=' . $this->page_slug;
			}
			$this->page_url = 'themes.php?page=knd-setup-wizard'; //apply_filters( $this->theme_name . '_theme_setup_wizard_page_url', $this->page_url );

			//set relative plugin path url
			$this->plugin_path = trailingslashit( $this->cleanFilePath( dirname( __FILE__ ) ) );
			$relative_url      = str_replace( $this->cleanFilePath( get_template_directory() ), '', $this->plugin_path );
			$this->plugin_url  = trailingslashit( get_template_directory_uri() . $relative_url );
		}

		/**
		 * Setup the hooks, actions and filters.
		 *
		 * @uses add_action() To add actions.
		 * @uses add_filter() To add filters.
		 *
		 * @since 1.1.1
		 * @access public
		 */
		public function init_actions() {

			if ( apply_filters( $this->theme_name . '_enable_setup_wizard', true ) && current_user_can( 'manage_options' ) ) {
				add_action( 'after_switch_theme', array( $this, 'switch_theme' ) );

				if ( class_exists( 'TGM_Plugin_Activation' ) && isset( $GLOBALS['tgmpa'] ) ) {
					add_action( 'init', array( $this, 'get_tgmpa_instanse' ), 30 );
					add_action( 'init', array( $this, 'set_tgmpa_url' ), 40 );
				}

//				add_action( 'admin_menu', array( $this, 'admin_menus' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'admin_init', array( $this, 'admin_redirects' ), 30 );
				add_action( 'admin_init', array( $this, 'init_wizard_steps' ), 30 );
				add_action( 'admin_init', array( $this, 'setup_wizard' ), 30 );
				add_filter( 'tgmpa_load', array( $this, 'tgmpa_load' ), 10, 1 );
				add_action( 'wp_ajax_envato_setup_plugins', array( $this, 'ajax_plugins' ) );
				add_action( 'wp_ajax_envato_setup_content', array( $this, 'ajax_content' ) );
			}
//			if ( function_exists( 'envato_market' ) ) {
//				add_action( 'admin_init', array( $this, 'envato_market_admin_init' ), 20 );
//				add_filter( 'http_request_args', array( $this, 'envato_market_http_request_args' ), 10, 2 );
//				add_action( 'wp_ajax_dtbwp_update_notice_handler', array($this,'ajax_notice_handler') );
//				add_action( 'admin_notices', array($this,'admin_theme_auth_notice') );
//			}
//			add_action( 'upgrader_post_install', array( $this, 'upgrader_post_install' ), 10, 2 );
		}

		/**
		 * After a theme update we clear the setup_complete option. This prompts the user to visit the update page again.
		 *
		 * @since 1.1.8
		 * @access public
		 */
//		public function upgrader_post_install( $return, $theme ) {
//			if ( is_wp_error( $return ) ) {
//				return $return;
//			}
//			if ( $theme != get_stylesheet() ) {
//				return $return;
//			}
//			update_option( 'envato_setup_complete', false );
//
//			return $return;
//		}

		/**
		 * Check if the theme default content already installed.
         * This can happen if swapping from a previous theme or updated the current theme.
		 */
		public function is_default_content_installed() {
			return !!get_option('knd_default_content_installed');
		}

		public function enqueue_scripts() {
		}

		public function tgmpa_load($status) {
			return is_admin() || current_user_can('install_themes');
		}

		public function switch_theme() {
			set_transient( '_' . $this->theme_name . '_activation_redirect', 1 );
		}

		public function admin_redirects() {
			if ( ! get_transient( '_' . $this->theme_name . '_activation_redirect' ) || get_option( 'envato_setup_complete', false ) ) {
				return;
			}
			delete_transient( '_' . $this->theme_name . '_activation_redirect' );
			wp_safe_redirect( admin_url( $this->page_url ) );
			exit;
		}

		/**
		 * Get configured TGMPA instance
		 *
		 * @access public
		 * @since 1.1.2
		 */
		public function get_tgmpa_instanse() {
			$this->tgmpa_instance = call_user_func( array( get_class( $GLOBALS['tgmpa'] ), 'get_instance' ) );
		}

		/**
		 * Update $tgmpa_menu_slug and $tgmpa_parent_slug from TGMPA instance
		 *
		 * @access public
		 * @since 1.1.2
		 */
		public function set_tgmpa_url() {

			$this->tgmpa_menu_slug = ( property_exists( $this->tgmpa_instance, 'menu' ) ) ? $this->tgmpa_instance->menu : $this->tgmpa_menu_slug;
			$this->tgmpa_menu_slug = apply_filters( $this->theme_name . '_theme_setup_wizard_tgmpa_menu_slug', $this->tgmpa_menu_slug );

			$tgmpa_parent_slug = ( property_exists( $this->tgmpa_instance, 'parent_slug' ) && $this->tgmpa_instance->parent_slug !== 'themes.php' ) ? 'admin.php' : 'themes.php';

			$this->tgmpa_url = apply_filters( $this->theme_name . '_theme_setup_wizard_tgmpa_url', $tgmpa_parent_slug . '?page=' . $this->tgmpa_menu_slug );

		}

		/**
		 * Add admin menus/screens.
		 */
//		public function admin_menus() {
//
//			if ( $this->is_submenu_page() ) {
//				//prevent Theme Check warning about "themes should use add_theme_page for adding admin pages"
//				$add_subpage_function = 'add_submenu' . '_page';
//				$add_subpage_function( $this->parent_slug, esc_html__( 'Setup Wizard' ), esc_html__( 'Setup Wizard' ), 'manage_options', $this->page_slug, array(
//					$this,
//					'setup_wizard',
//				) );
//			} else {
//				add_theme_page( esc_html__( 'Setup Wizard' ), esc_html__( 'Setup Wizard' ), 'manage_options', $this->page_slug, array(
//					$this,
//					'setup_wizard',
//				) );
//			}
//
//		}


		/**
		 * Setup steps.
		 *
		 * @since 1.1.1
		 * @access public
		 * @return array
		 */
		public function init_wizard_steps() {

			$this->steps = array(
				'introduction' => array(
					'name'    => esc_html__('Introduction', 'knd'),
					'view'    => array( $this, 'envato_setup_introduction' ),
					'handler' => array( $this, '' /*'envato_setup_introduction_save'*/ ),
				),
			);
			if ( class_exists( 'TGM_Plugin_Activation' ) && isset( $GLOBALS['tgmpa'] ) ) {
				$this->steps['default_plugins'] = array(
					'name'    => esc_html__('Plugins'),
					'view'    => array( $this, 'envato_setup_default_plugins' ),
					'handler' => '',
				);
			}
//			$this->steps['updates']         = array(
//				'name'    => esc_html__( 'Updates' ),
//				'view'    => array( $this, 'envato_setup_updates' ),
//				'handler' => array( $this, 'envato_setup_updates_save' ),
//			);
			if( count($this->site_styles) > 1 ) {
				$this->steps['style'] = array(
					'name'    => esc_html__( 'Style' ),
					'view'    => array( $this, 'envato_setup_color_style' ),
					'handler' => array( $this, 'envato_setup_color_style_save' ),
				);
			}
			$this->steps['default_content'] = array(
				'name'    => esc_html__( 'Content' ),
				'view'    => array( $this, 'envato_setup_default_content' ),
				'handler' => '',
			);
			$this->steps['design']          = array(
				'name'    => esc_html__( 'Logo' ),
				'view'    => array( $this, 'envato_setup_logo_design' ),
				'handler' => array( $this, 'envato_setup_logo_design_save' ),
			);
			$this->steps['customize']       = array(
				'name'    => esc_html__( 'Customize' ),
				'view'    => array( $this, 'envato_setup_customize' ),
				'handler' => '',
			);
			$this->steps['help_support']    = array(
				'name'    => esc_html__( 'Support' ),
				'view'    => array( $this, 'envato_setup_help_support' ),
				'handler' => '',
			);
			$this->steps['next_steps']      = array(
				'name'    => esc_html__( 'Ready!' ),
				'view'    => array( $this, 'envato_setup_ready' ),
				'handler' => '',
			);

			$this->steps = apply_filters( $this->theme_name . '_theme_setup_wizard_steps', $this->steps );

		}

		/**
		 * Show the setup wizard
		 */
		public function setup_wizard() {
			if ( empty( $_GET['page'] ) || $this->page_slug !== $_GET['page'] ) {
				return;
			}
			ob_end_clean();

			$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );

			wp_register_script( 'jquery-blockui', $this->plugin_url . 'js/jquery.blockUI.js', array( 'jquery' ), '2.70', true );
			wp_register_script( 'envato-setup', $this->plugin_url . 'js/envato-setup.js', array(
				'jquery',
				'jquery-blockui',
			), $this->version );
			wp_localize_script( 'envato-setup', 'envato_setup_params', array(
				'tgm_plugin_nonce' => array(
					'update'  => wp_create_nonce( 'tgmpa-update' ),
					'install' => wp_create_nonce( 'tgmpa-install' ),
				),
				'tgm_bulk_url'     => admin_url( $this->tgmpa_url ),
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'wpnonce'          => wp_create_nonce( 'envato_setup_nonce' ),
				'verify_text'      => esc_html__( '...verifying' ),
			) );

			//wp_enqueue_style( 'envato_wizard_admin_styles', $this->plugin_url . '/css/admin.css', array(), $this->version );
			wp_enqueue_style( 'envato-setup', $this->plugin_url . 'css/envato-setup.css', array(
				'wp-admin',
				'dashicons',
				'install',
			), $this->version );

			//enqueue style for admin notices
			wp_enqueue_style( 'wp-admin' );

			wp_enqueue_media();
			wp_enqueue_script( 'media' );

			ob_start();
			$this->setup_wizard_header();
			$this->setup_wizard_steps();
			$show_content = true;
			echo '<div class="envato-setup-content">';
			if ( ! empty( $_REQUEST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
				$show_content = call_user_func( $this->steps[ $this->step ]['handler'] );
			}
			if ( $show_content ) {
				$this->setup_wizard_content();
			}
			echo '</div>';
			$this->setup_wizard_footer();
			exit;
		}

		public function get_step_link( $step ) {
			return add_query_arg( 'step', $step, admin_url( 'admin.php?page=' . $this->page_slug ) );
		}

		public function get_next_step_link() {
			$keys = array_keys( $this->steps );

			return add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ) ) + 1 ], remove_query_arg( 'translation_updated' ) );
		}

		/**
		 * Setup Wizard Header
		 */
	public function setup_wizard_header() {?>

		<!DOCTYPE html>
		<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes();?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<?php // avoid theme check issues.
			echo '<t'; echo 'itle>'.esc_html__('Theme &rsaquo; Setup Wizard').'</ti'.'tle>';?>
			<?php wp_print_scripts('envato-setup');?>
			<?php do_action('admin_print_styles');?>
			<?php do_action('admin_print_scripts');?>
			<?php do_action('admin_head');?>
		</head>
		<body class="envato-setup wp-core-ui">
		<h1 id="wc-logo">
			<a href="<?php echo KND_OFFICIAL_WEBSITE_URL;?>" target="_blank">
                <?php echo '<img class="site-logo" src="'.get_template_directory_uri().'/knd-logo.png" alt="'.__('Kandinsky theme setup wizard', 'knd').'" style="width:100%; height:auto;">';?>
            </a>
		</h1>
		<?php
		}

		/**
		 * Setup Wizard Footer
		 */
		public function setup_wizard_footer() {
		?>
		<?php if ( 'next_steps' === $this->step ) : ?>
			<a class="wc-return-to-dashboard"
			   href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Return to the WordPress Dashboard' ); ?></a>
		<?php endif; ?>
		</body>
		<?php
		@do_action( 'admin_footer' ); // this was spitting out some errors in some admin templates. quick @ fix until I have time to find out what's causing errors.
		do_action( 'admin_print_footer_scripts' );
		?>
		</html>
		<?php
	}

		/**
		 * Output the steps
		 */
		public function setup_wizard_steps() {
			$ouput_steps = $this->steps;
			array_shift( $ouput_steps );
			?>
			<ol class="envato-setup-steps">
				<?php foreach ( $ouput_steps as $step_key => $step ) : ?>
					<li class="<?php
					$show_link = false;
					if ( $step_key === $this->step ) {
						echo 'active';
					} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
						echo 'done';
						$show_link = true;
					}
					?>"><?php
						if ( $show_link ) {
							?>
							<a href="<?php echo esc_url( $this->get_step_link( $step_key ) ); ?>"><?php echo esc_html( $step['name'] ); ?></a>
							<?php
						} else {
							echo esc_html( $step['name'] );
						}
						?></li>
				<?php endforeach; ?>
			</ol>
			<?php
		}

		/**
		 * Output the content for the current step
		 */
		public function setup_wizard_content() {
			isset($this->steps[$this->step]) ? call_user_func($this->steps[$this->step]['view']) : false;
		}

		/**
		 * Introduction step
		 */
		public function envato_setup_introduction() {

//			if ( false && isset( $_REQUEST['debug'] ) ) {
//				echo '<pre>';
//				// debug inserting a particular post so we can see what's going on
//				$post_type = 'nav_menu_item';
//				$post_id   = 239; // debug this particular import post id.
//				$all_data  = $this->_get_json( 'default.json' );
//				if ( ! $post_type || ! isset( $all_data[ $post_type ] ) ) {
//					echo "Post type $post_type not found.";
//				} else {
//					echo "Looking for post id $post_id \n";
//					foreach ( $all_data[ $post_type ] as $post_data ) {
//
//						if ( $post_data['post_id'] == $post_id ) {
//							//print_r( $post_data );
//							$this->_process_post_data( $post_type, $post_data, 0, true );
//						}
//					}
//				}
//				$this->_handle_delayed_posts();
//				print_r( $this->logs );
//
//				echo '</pre>';
//			} else if ( isset( $_REQUEST['export'] ) ) {
//
//				@include( 'envato-setup-export.php' );
//
//			} else if ( $this->is_possible_upgrade() ) {
//				?>
<!--				<h1>--><?php //printf( esc_html__( 'Welcome to the setup wizard for %s.' ), wp_get_theme() ); ?><!--</h1>-->
<!--				<p>--><?php //esc_html_e( 'It looks like you may have recently upgraded to this theme. Great! This setup wizard will help ensure all the default settings are correct. It will also show some information about your new website and support options.' ); ?><!--</p>-->
<!--				<p class="envato-setup-actions step">-->
<!--					<a href="--><?php //echo esc_url( $this->get_next_step_link() ); ?><!--"-->
<!--					   class="button-primary button button-large button-next">--><?php //esc_html_e( 'Let\'s Go!' ); ?><!--</a>-->
<!--					<a href="--><?php //echo esc_url( wp_get_referer() && ! strpos( wp_get_referer(), 'update.php' ) ? wp_get_referer() : admin_url( '' ) ); ?><!--"-->
<!--					   class="button button-large">--><?php //esc_html_e( 'Not right now' ); ?><!--</a>-->
<!--				</p>-->
<!--				--><?php
//			} else

            /*if(get_option('envato_setup_complete', false)) {?>

				<h1><?php printf( esc_html__( 'Welcome to the setup wizard for %s.' ), wp_get_theme() ); ?></h1>
				<p><?php esc_html_e( 'It looks like you have already run the setup wizard. Below are some options: ' ); ?></p>
				<ul>
					<li>
						<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
						   class="button-primary button button-next button-large"><?php esc_html_e( 'Run Setup Wizard Again' ); ?></a>
					</li>
					<li>
						<form method="post">
							<input type="hidden" name="reset-font-defaults" value="yes">
							<input type="submit" class="button-primary button button-large button-next"
							       value="<?php esc_attr_e( 'Reset font style and colors' ); ?>" name="save_step">
							<?php wp_nonce_field( 'envato-setup' ); ?>
						</form>
					</li>
				</ul>
				<p class="envato-setup-actions step">
					<a href="<?php echo esc_url(wp_get_referer() && !strpos(wp_get_referer(), 'update.php') ? wp_get_referer() : admin_url(''));?>" class="button button-large"><?php esc_html_e('Cancel'); ?></a>
				</p>
				<?php } else {*/?>

				<h1><?php printf(esc_html__('Welcome to the %s setup wizard', 'knd'), wp_get_theme());?></h1>
				<p><?php printf(esc_html__("Hello! Let's set up your organization website together. With few simple steps we will configure minimal necessary settings, like installing of required plugins, setting up default website content and the logo. It should only take 5 minutes. You can always change any of these settings later on, in the Plugins admin folder.", 'knd')); ?></p>

				<p class="envato-setup-actions step">
					<a href="<?php echo esc_url($this->get_next_step_link());?>" class="button-primary button button-large button-next"><?php esc_html_e("Let's go!", 'knd'); ?></a>
					<a href="<?php echo esc_url(wp_get_referer() && !strpos(wp_get_referer(), 'update.php') ? wp_get_referer() : admin_url(''));?>" class="button button-large"><?php esc_html_e('Not right now', 'knd');?></a>
				</p>
            <?php
			//}
		}

		public function filter_options( $options ) {
			return $options;
		}

		/**
		 *
		 * Handles save button from welcome page. This is to perform tasks when the setup wizard has already been run. E.g. reset defaults
		 *
		 * @since 1.2.5
		 */
		/*public function envato_setup_introduction_save() {

			check_admin_referer( 'envato-setup' );

			if ( ! empty( $_POST['reset-font-defaults'] ) && $_POST['reset-font-defaults'] == 'yes' ) {

				// clear font options
				update_option( 'tt_font_theme_options', array() );

				// do other reset options here.

				// reset site color
				remove_theme_mod( 'dtbwp_site_color' );

				if ( class_exists( 'dtbwp_customize_save_hook' ) ) {
					$site_color_defaults = new dtbwp_customize_save_hook();
					$site_color_defaults->save_color_options();
				}

				$file_name = get_template_directory() . '/style.custom.css';
				if ( file_exists( $file_name ) ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
					WP_Filesystem();
					global $wp_filesystem;
					$wp_filesystem->put_contents( $file_name, '' );
				}
				?>
				<p>
					<strong><?php esc_html_e( 'Options have been reset. Please go to Appearance > Customize in the WordPress backend.' ); ?></strong>
				</p>
				<?php
				return true;
			}

			return false;
		}*/


		private function _get_plugins() {

			$instance = call_user_func(array(get_class($GLOBALS['tgmpa']), 'get_instance'));
			$plugins  = array(
				'all'      => array(), // Meaning: all plugins which still have open actions.
				'install'  => array(),
				'update'   => array(),
				'activate' => array(),
			);

			foreach ( $instance->plugins as $slug => $plugin ) {
				if ( $instance->is_plugin_active( $slug ) && false === $instance->does_plugin_have_update( $slug ) ) {
					// No need to display plugins if they are installed, up-to-date and active.
					continue;
				} else {
					$plugins['all'][ $slug ] = $plugin;

					if ( ! $instance->is_plugin_installed( $slug ) ) {
						$plugins['install'][ $slug ] = $plugin;
					} else {
						if ( false !== $instance->does_plugin_have_update( $slug ) ) {
							$plugins['update'][ $slug ] = $plugin;
						}

						if ( $instance->can_plugin_activate( $slug ) ) {
							$plugins['activate'][ $slug ] = $plugin;
						}
					}
				}
			}

			return $plugins;

		}

		/**
		 * Page setup
		 */
		public function envato_setup_default_plugins() {

			tgmpa_load_bulk_installer();
			if( !class_exists('TGM_Plugin_Activation') || !isset($GLOBALS['tgmpa']) ) {
				die(__('Failed to find TGM plugin', 'knd'));
			}
			$url = wp_nonce_url(add_query_arg(array('plugins' => 'go')), 'envato-setup');

			$method = ''; // Leave blank so WP_Filesystem can populate it as necessary.
			$fields = array_keys($_POST); // Extra fields to pass to WP_Filesystem.

			if(false === ($creds = request_filesystem_credentials(esc_url_raw($url), $method, false, false, $fields))) {
				return true; // Stop the normal page form from displaying, credential request form will be shown.
			}

			// Now we have some credentials, setup WP_Filesystem
			if ( !WP_Filesystem($creds) ) { // Our credentials were no good, ask the user for them again

				request_filesystem_credentials( esc_url_raw( $url ), $method, true, false, $fields );
				return true;

			}?>

			<h1><?php esc_html_e('Default Plugins', 'knd');?></h1>
			<form method="post">

				<?php $plugins = $this->_get_plugins();
				if($plugins['all']) {

                    $plugins_required = $plugins_recommended = array();

				    foreach($plugins['all'] as $slug => $plugin) {
				        if(empty($plugin['required'])) {
				            $plugins_recommended[$slug] = $plugin;
                        } else {
				            $plugins_required[$slug] = $plugin;
                        }
                    }

                    if($plugins_required) {?>

					<p><?php esc_html_e('Your website needs a few essential plugins. The following plugins will be installed or updated:', 'knd');?></p>
                    <p><?php esc_html_e('You can add and remove plugins later on, in the Plugins admin folder.', 'knd');?></p>

					<ul class="envato-wizard-plugins">
						<?php foreach($plugins_required as $slug => $plugin) {?>
                        <li data-slug="<?php echo esc_attr($slug);?>"><?php echo esc_html($plugin['name']);?>
                            <span><?php $plugin_status = '';

                            if(isset($plugins['install'][$slug])) {
                                $plugin_status = __('Installation required', 'knd');
                            } else if(isset($plugins['update'][$slug])) {
                                $plugin_status = isset($plugins['activate'][$slug]) ?
                                    __('Update and activation required', 'knd') : __('Update required', 'knd');
                            } else if(isset($plugins['activate'][$slug])) {
                                $plugin_status = __('Activation required', 'knd');
                            }

                            echo $plugin_status;?></span>
                            <div class="spinner"></div>
                        </li>
						<?php }?>
					</ul>
                    <?php }

                    if($plugins_recommended) {?>

                    <p><?php esc_html_e('We also recommend to add several more:', 'knd');?></p>
                    <ul class="envato-wizard-plugins-recommended">
                    <?php foreach($plugins_recommended as $slug => $plugin) {?>
                        <li data-slug="<?php echo esc_attr($slug);?>"><?php echo esc_html($plugin['name']);?><span>

                        <?php $plugin_status = '';

                        if(isset($plugins['install'][$slug])) {
                            $plugin_status = __('Install', 'knd');
                        } else if(isset($plugins['update'][$slug])) {
                            $plugin_status = isset($plugins['activate'][$slug]) ?
                                __('Update and activate', 'knd') : __('Update', 'knd');
                        } else if(isset($plugins['activate'][$slug])) {
                            $plugin_status = __('Activate', 'knd');
                        }?>

                        <label>
                            <input type="checkbox" class="plugin-accepted" name="knd-recommended-plugin-<?php echo $slug;?>">
                            <?php echo $plugin_status;?>
                        </label>

                    </span><div class="spinner"></div></li>
                    <?php }?>
                    </ul>

                <?php }

                } else {
					echo '<p><strong>'.esc_html_e("Good news! All plugins are already installed and up to date. Let's proceed further.", 'knd').'</strong></p>';
				}?>

				<p class="envato-setup-actions step">
					<a href="<?php echo esc_url($this->get_next_step_link());?>" class="button-primary button button-large button-next" data-callback="install_plugins">
                        <?php esc_html_e('Continue', 'knd'); ?>
                    </a>
					<a href="<?php echo esc_url($this->get_next_step_link());?>" class="button button-large button-next">
                        <?php esc_html_e('Skip this step', 'knd');?>
                    </a>
					<?php wp_nonce_field('envato-setup');?>
				</p>
			</form>
			<?php
		}

		public function ajax_plugins() {

			if( !check_ajax_referer('envato_setup_nonce', 'wpnonce') || empty($_POST['slug']) ) {
				wp_send_json_error(array('error' => 1, 'message' => esc_html__('No slug found', 'knd')));
			}
			$json = array(); // Send back some json we use to hit up TGM

			$plugins = $this->_get_plugins();

			// what are we doing with this plugin?
			foreach($plugins['all'] as $slug => $plugin) {

                if( !empty($plugins['activate'][$slug]) && $_POST['slug'] == $slug) {

					$json = array(
						'url'           => admin_url($this->tgmpa_url),
						'plugin'        => array($slug),
						'tgmpa-page'    => $this->tgmpa_menu_slug,
						'plugin_status' => 'all',
						'_wpnonce'      => wp_create_nonce('bulk-plugins'),
						'action'        => 'tgmpa-bulk-activate',
						'action2'       => -1,
						'message'       => esc_html__('Activating Plugin', 'knd'),
					);
					break;

				} else if( !empty($plugins['update'][$slug]) && $_POST['slug'] == $slug ) {

                    $json = array(
                        'url'           => admin_url( $this->tgmpa_url ),
                        'plugin'        => array( $slug ),
                        'tgmpa-page'    => $this->tgmpa_menu_slug,
                        'plugin_status' => 'all',
                        '_wpnonce'      => wp_create_nonce( 'bulk-plugins' ),
                        'action'        => 'tgmpa-bulk-update',
                        'action2'       => -1,
                        'message'       => esc_html__( 'Updating Plugin' ),
                    );
                    break;

                } else if( !empty($plugins['install'][$slug]) && $_POST['slug'] == $slug ) {

                    $json = array(
                        'url'           => admin_url( $this->tgmpa_url ),
                        'plugin'        => array( $slug ),
                        'tgmpa-page'    => $this->tgmpa_menu_slug,
                        'plugin_status' => 'all',
                        '_wpnonce'      => wp_create_nonce( 'bulk-plugins' ),
                        'action'        => 'tgmpa-bulk-install',
                        'action2'       => -1,
                        'message'       => esc_html__( 'Installing Plugin' ),
                    );
                    break;

                }

			}

			if($json) {
				$json['hash'] = md5(serialize($json)); // used for checking if duplicates happen, move to next plugin
				wp_send_json($json);
			} else {
				wp_send_json(array('done' => 1, 'message' => esc_html__('Success')));
			}
			exit;

		}

		public function _content_install_posts() {

            knd_import_starter_data_from_csv('posts.csv', 'post');
            knd_update_posts();

            return true;

        }
        public function _content_install_pages() {

            do_action('knd_save_demo_content');
            return true;

        }
        public function _content_install_settings() {

            knd_set_theme_options();
            return true;

        }
        public function _content_install_menu() {

            knd_setup_menus();  // all menus except main nav menu
            return true;

        }

		private function _content_default_get() {

			$content = array();

            $content['pages'] = array(
                'title'            => esc_html__('Pages', 'knd'),
                'description'      => esc_html__('Insert default website pages as seen in the demo.', 'knd'),
                'pending'          => esc_html__('Pending', 'knd'),
                'installing'       => esc_html__('Installing...', 'knd'),
                'success'          => esc_html__('Success!', 'knd'),
                'install_callback' => array($this, '_content_install_pages'),
                'checked'          => $this->is_default_content_installed(),
            );
            $content['posts'] = array(
                'title'            => esc_html__('Posts', 'knd'),
                'description'      => esc_html__('Insert default website posts as seen in the demo.', 'knd'),
                'pending'          => esc_html__('Pending', 'knd'),
                'installing'       => esc_html__('Installing...', 'knd'),
                'success'          => esc_html__('Success!', 'knd'),
                'install_callback' => array($this, '_content_install_posts'),
                'checked'          => $this->is_default_content_installed(),
            );
			$content['settings'] = array(
                'title'            => esc_html__('Settings', 'knd'),
                'description'      => esc_html__('Insert default website settings as seen in the demo.', 'knd'),
                'pending'          => esc_html__('Pending', 'knd'),
                'installing'       => esc_html__('Installing...', 'knd'),
                'success'          => esc_html__('Success!', 'knd'),
                'install_callback' => array($this, '_content_install_settings'),
                'checked'          => $this->is_default_content_installed(),
			);
            $content['menu'] = array(
                'title'            => esc_html__('Menu', 'knd'),
                'description'      => esc_html__('Insert default website menu as seen in the demo.', 'knd'),
                'pending'          => esc_html__('Pending', 'knd'),
                'installing'       => esc_html__('Installing...', 'knd'),
                'success'          => esc_html__('Success!', 'knd'),
                'install_callback' => array($this, '_content_install_menu'),
                'checked'          => $this->is_default_content_installed(),
            );

			return apply_filters($this->theme_name.'_theme_setup_wizard_content', $content);

		}

		/**
		 * Page setup
		 */
		public function envato_setup_default_content() {?>

			<h1><?php esc_html_e('Theme default content', 'knd');?></h1>
			<form method="post">
				<?php if($this->is_default_content_installed()) {?>
					<p><?php esc_html_e('It looks like you already have content installed on this website. If you would like to install the default demo content as well you can select it below. Otherwise just choose the upgrade option to ensure everything is up to date.', 'knd');?></p>
				<?php } else {?>
					<p><?php esc_html_e("It's time to insert some default content for your new WordPress website. Choose what you would like inserted below and click Continue. It is recommended to leave everything selected. Once inserted, this content can be managed from the WordPress admin dashboard.", 'knd');?></p>
				<?php }?>
				<table class="envato-setup-pages" cellspacing="0">
					<thead>
                        <tr>
                            <td class="check"></td>
                            <th class="item"><?php esc_html_e('Item', 'knd');?></th>
                            <th class="description"><?php esc_html_e('Description', 'knd');?></th>
                            <th class="status"><?php esc_html_e('Status', 'knd'); ?></th>
                        </tr>
					</thead>
					<tbody>
					<?php foreach($this->_content_default_get() as $slug => $default) {?>
						<tr class="envato_default_content" data-content="<?php echo esc_attr($slug);?>">
							<td>
								<input type="checkbox" name="default_content[<?php echo esc_attr($slug);?>]" class="envato_default_content" id="default_content_<?php echo esc_attr($slug);?>" value="1" <?php echo !isset($default['checked']) || $default['checked'] ? 'checked="checked"' : '';?>>
							</td>
							<td>
                                <label for="default_content_<?php echo esc_attr($slug);?>">
                                    <?php echo esc_html($default['title']);?>
                                </label>
							</td>
							<td class="description"><?php echo esc_html($default['description']);?></td>
							<td class="status"><span><?php echo esc_html($default['pending']);?></span>
								<div class="spinner"></div>
							</td>
						</tr>
					<?php }?>
					</tbody>
				</table>

				<p class="envato-setup-actions step">
					<a href="<?php echo esc_url($this->get_next_step_link());?>" class="button-primary button button-large button-next" data-callback="install_content">
                        <?php esc_html_e('Continue', 'knd');?>
                    </a>
					<a href="<?php echo esc_url($this->get_next_step_link());?>" class="button button-large button-next">
                        <?php esc_html_e('Skip this step', 'knd');?>
                    </a>
					<?php wp_nonce_field('envato-setup');?>
				</p>
			</form>
        <?php
		}

		public function ajax_content() {

			$content = $this->_content_default_get();
			if(
                !check_ajax_referer('envato_setup_nonce', 'wpnonce') ||
                empty($_POST['content']) &&
                isset($content[$_POST['content']])
            ) {
				wp_send_json_error(array('error' => 1, 'message' => esc_html__('No content Found', 'knd')));
			}

			$json = false;
			$this_content = $content[$_POST['content']];

			if(empty($_POST['proceed'])) {
                $json = array(
                    'url'      => admin_url( 'admin-ajax.php' ),
                    'action'   => 'envato_setup_content',
                    'proceed'  => 'true',
                    'content'  => $_POST['content'],
                    '_wpnonce' => wp_create_nonce('envato_setup_nonce'),
                    'message'  => $this_content['installing'],
                    'logs'     => $this->logs,
                    'errors'   => $this->errors,
                );
			} else {

                $this->log(' -!! STARTING SECTION for '.$_POST['content']);

                // init delayed posts from transient.
                $this->delay_posts = get_transient( 'delayed_posts' );
                if ( ! is_array( $this->delay_posts ) ) {
                    $this->delay_posts = array();
                }

                if( !empty($this_content['install_callback']) ) {
                    if($result = call_user_func($this_content['install_callback'])) {

                        $this->log(' -- FINISH. Writing '.count($this->delay_posts, COUNT_RECURSIVE).' delayed posts to transient ');
                        set_transient( 'delayed_posts', $this->delay_posts, 60 * 60 * 24 );

                        if(is_array($result) && isset($result['retry'])) {
                            $json = array(
                                'url'         => admin_url( 'admin-ajax.php' ),
                                'action'      => 'envato_setup_content',
                                'proceed'     => 'true',
                                'retry'       => time(),
                                'retry_count' => $result['retry_count'],
                                'content'     => $_POST['content'],
                                '_wpnonce'    => wp_create_nonce( 'envato_setup_nonce' ),
                                'message'     => $this_content['installing'],
                                'logs'        => $this->logs,
                                'errors'      => $this->errors,
                            );
                        } else {
                            $json = array(
                                'done'    => 1,
                                'message' => $this_content['success'],
                                'debug'   => $result,
                                'logs'    => $this->logs,
                                'errors'  => $this->errors,
                            );
                        }
                    }
                }

			}

			if($json) {
				$json['hash'] = md5(serialize($json)); // used for checking if duplicates happen, move to next plugin
				wp_send_json($json);
			} else {
				wp_send_json(array(
					'error'   => 1,
					'message' => esc_html__('Error', 'knd'),
					'logs'    => $this->logs,
					'errors'  => $this->errors,
				));
			}

			exit;

		}


		private function _imported_term_id( $original_term_id, $new_term_id = false ) {
//			$terms = get_transient( 'importtermids' );
//			if ( ! is_array( $terms ) ) {
//				$terms = array();
//			}
//			if ( $new_term_id ) {
//				if ( ! isset( $terms[ $original_term_id ] ) ) {
//					$this->log( 'Insert old TERM ID ' . $original_term_id . ' as new TERM ID: ' . $new_term_id );
//				} else if ( $terms[ $original_term_id ] != $new_term_id ) {
//					$this->error( 'Replacement OLD TERM ID ' . $original_term_id . ' overwritten by new TERM ID: ' . $new_term_id );
//				}
//				$terms[ $original_term_id ] = $new_term_id;
//				set_transient( 'importtermids', $terms, 60 * 60 * 24 );
//			} else if ( $original_term_id && isset( $terms[ $original_term_id ] ) ) {
//				return $terms[ $original_term_id ];
//			}

			return false;
		}


		public function vc_post( $post_id = false ) {

			$vc_post_ids = get_transient( 'import_vc_posts' );
			if ( ! is_array( $vc_post_ids ) ) {
				$vc_post_ids = array();
			}
			if ( $post_id ) {
				$vc_post_ids[ $post_id ] = $post_id;
				set_transient( 'import_vc_posts', $vc_post_ids, 60 * 60 * 24 );
			} else {

				$this->log( 'Processing vc pages 2: ' );

				return;
				if ( class_exists( 'Vc_Manager' ) && class_exists( 'Vc_Post_Admin' ) ) {
					$this->log( $vc_post_ids );
					$vc_manager = Vc_Manager::getInstance();
					$vc_base    = $vc_manager->vc();
					$post_admin = new Vc_Post_Admin();
					foreach ( $vc_post_ids as $vc_post_id ) {
						$this->log( 'Save ' . $vc_post_id );
						$vc_base->buildShortcodesCustomCss( $vc_post_id );
						$post_admin->save( $vc_post_id );
						$post_admin->setSettings( $vc_post_id );
						//twice? bug?
						$vc_base->buildShortcodesCustomCss( $vc_post_id );
						$post_admin->save( $vc_post_id );
						$post_admin->setSettings( $vc_post_id );
					}
				}
			}

		}


		public function elementor_post( $post_id = false ) {

			// regenrate the CSS for this Elementor post
			if( class_exists( 'Elementor\Post_CSS_File' ) ) {
                $post_css = new Elementor\Post_CSS_File($post_id);
				$post_css->update();
			}

		}



		private function _imported_post_id( $original_id = false, $new_id = false ) {
			if ( is_array( $original_id ) || is_object( $original_id ) ) {
				return false;
			}
			$post_ids = get_transient( 'importpostids' );
			if ( ! is_array( $post_ids ) ) {
				$post_ids = array();
			}
			if ( $new_id ) {
				if ( ! isset( $post_ids[ $original_id ] ) ) {
					$this->log( 'Insert old ID ' . $original_id . ' as new ID: ' . $new_id );
				} else if ( $post_ids[ $original_id ] != $new_id ) {
					$this->error( 'Replacement OLD ID ' . $original_id . ' overwritten by new ID: ' . $new_id );
				}
				$post_ids[ $original_id ] = $new_id;
				set_transient( 'importpostids', $post_ids, 60 * 60 * 24 );
			} else if ( $original_id && isset( $post_ids[ $original_id ] ) ) {
				return $post_ids[ $original_id ];
			} else if ( $original_id === false ) {
				return $post_ids;
			}

			return false;
		}

		private function _post_orphans( $original_id = false, $missing_parent_id = false ) {
			$post_ids = get_transient( 'postorphans' );
			if ( ! is_array( $post_ids ) ) {
				$post_ids = array();
			}
			if ( $missing_parent_id ) {
				$post_ids[ $original_id ] = $missing_parent_id;
				set_transient( 'postorphans', $post_ids, 60 * 60 * 24 );
			} else if ( $original_id && isset( $post_ids[ $original_id ] ) ) {
				return $post_ids[ $original_id ];
			} else if ( $original_id === false ) {
				return $post_ids;
			}

			return false;
		}

		private function _cleanup_imported_ids() {
			// loop over all attachments and assign the correct post ids to those attachments.

		}

		private $delay_posts = array();

		private function _delay_post_process( $post_type, $post_data ) {
			if ( ! isset( $this->delay_posts[ $post_type ] ) ) {
				$this->delay_posts[ $post_type ] = array();
			}
			$this->delay_posts[ $post_type ][ $post_data['post_id'] ] = $post_data;

		}


		// return the difference in length between two strings
		public function cmpr_strlen( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}

		private function _parse_gallery_shortcode_content($content){
			// we have to format the post content. rewriting images and gallery stuff
			$replace      = $this->_imported_post_id();
			$urls_replace = array();
			foreach ( $replace as $key => $val ) {
				if ( $key && $val && ! is_numeric( $key ) && ! is_numeric( $val ) ) {
					$urls_replace[ $key ] = $val;
				}
			}
			if ( $urls_replace ) {
				uksort( $urls_replace, array( &$this, 'cmpr_strlen' ) );
				foreach ( $urls_replace as $from_url => $to_url ) {
					$content = str_replace( $from_url, $to_url, $content );
				}
			}
			if ( preg_match_all( '#\[gallery[^\]]*\]#', $content, $matches ) ) {
				foreach ( $matches[0] as $match_id => $string ) {
					if ( preg_match( '#ids="([^"]+)"#', $string, $ids_matches ) ) {
						$ids = explode( ',', $ids_matches[1] );
						foreach ( $ids as $key => $val ) {
							$new_id = $val ? $this->_imported_post_id( $val ) : false;
							if ( ! $new_id ) {
								unset( $ids[ $key ] );
							} else {
								$ids[ $key ] = $new_id;
							}
						}
						$new_ids                   = implode( ',', $ids );
						$content = str_replace( $ids_matches[0], 'ids="' . $new_ids . '"', $content );
					}
				}
			}
			// contact form 7 id fixes.
			if ( preg_match_all( '#\[contact-form-7[^\]]*\]#', $content, $matches ) ) {
				foreach ( $matches[0] as $match_id => $string ) {
					if ( preg_match( '#id="(\d+)"#', $string, $id_match ) ) {
						$new_id = $this->_imported_post_id( $id_match[1] );
						if ( $new_id ) {
							$content = str_replace( $id_match[0], 'id="' . $new_id . '"', $content );
						} else {
							// no imported ID found. remove this entry.
							$content = str_replace( $matches[0], '(insert contact form here)', $content );
						}
					}
				}
			}
			return $content;
		}

		private function _handle_delayed_posts( $last_delay = false ) {

			$this->log( ' ---- Processing ' . count( $this->delay_posts, COUNT_RECURSIVE ) . ' delayed posts' );
			for ( $x = 1; $x < 4; $x ++ ) {
				foreach ( $this->delay_posts as $delayed_post_type => $delayed_post_datas ) {
					foreach ( $delayed_post_datas as $delayed_post_id => $delayed_post_data ) {
						if ( $this->_imported_post_id( $delayed_post_data['post_id'] ) ) {
							$this->log( $x . ' - Successfully processed ' . $delayed_post_type . ' ID ' . $delayed_post_data['post_id'] . ' previously.' );
							unset( $this->delay_posts[ $delayed_post_type ][ $delayed_post_id ] );
							$this->log( ' ( ' . count( $this->delay_posts, COUNT_RECURSIVE ) . ' delayed posts remain ) ' );
						} else if ( $this->_process_post_data( $delayed_post_type, $delayed_post_data, $last_delay ) ) {
							$this->log( $x . ' - Successfully found delayed replacement for ' . $delayed_post_type . ' ID ' . $delayed_post_data['post_id'] . '.' );
							// successfully inserted! don't try again.
							unset( $this->delay_posts[ $delayed_post_type ][ $delayed_post_id ] );
							$this->log( ' ( ' . count( $this->delay_posts, COUNT_RECURSIVE ) . ' delayed posts remain ) ' );
						}
					}
				}
			}
		}

		private function _fetch_remote_file( $url, $post ) {
			// extract the file name and extension from the url
			$file_name  = basename( $url );
			$local_file = trailingslashit( get_template_directory() ) . 'images/stock/' . $file_name;
			$upload     = false;
			if ( is_file( $local_file ) && filesize( $local_file ) > 0 ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				WP_Filesystem();
				global $wp_filesystem;
				$file_data = $wp_filesystem->get_contents( $local_file );
				$upload    = wp_upload_bits( $file_name, 0, $file_data, $post['upload_date'] );
				if ( $upload['error'] ) {
					return new WP_Error( 'upload_dir_error', $upload['error'] );
				}
			}

			if ( ! $upload || $upload['error'] ) {
				// get placeholder file in the upload dir with a unique, sanitized filename
				$upload = wp_upload_bits( $file_name, 0, '', $post['upload_date'] );
				if ( $upload['error'] ) {
					return new WP_Error( 'upload_dir_error', $upload['error'] );
				}

				// fetch the remote url and write it to the placeholder file
				//$headers = wp_get_http( $url, $upload['file'] );

				$max_size = (int) apply_filters( 'import_attachment_size_limit', 0 );

				// we check if this file is uploaded locally in the source folder.
				$response = wp_remote_get( $url );
				if ( is_array( $response ) && ! empty( $response['body'] ) && $response['response']['code'] == '200' ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
					$headers = $response['headers'];
					WP_Filesystem();
					global $wp_filesystem;
					$wp_filesystem->put_contents( $upload['file'], $response['body'] );
					//
				} else {
					// required to download file failed.
					@unlink( $upload['file'] );

					return new WP_Error( 'import_file_error', esc_html__( 'Remote server did not respond' ) );
				}

				$filesize = filesize( $upload['file'] );

				if ( isset( $headers['content-length'] ) && $filesize != $headers['content-length'] ) {
					@unlink( $upload['file'] );

					return new WP_Error( 'import_file_error', esc_html__( 'Remote file is incorrect size' ) );
				}

				if ( 0 == $filesize ) {
					@unlink( $upload['file'] );

					return new WP_Error( 'import_file_error', esc_html__( 'Zero size file downloaded' ) );
				}

				if ( ! empty( $max_size ) && $filesize > $max_size ) {
					@unlink( $upload['file'] );

					return new WP_Error( 'import_file_error', sprintf( esc_html__( 'Remote file is too large, limit is %s' ), size_format( $max_size ) ) );
				}
			}

			// keep track of the old and new urls so we can substitute them later
			$this->_imported_post_id( $url, $upload['url'] );
			$this->_imported_post_id( $post['guid'], $upload['url'] );
			// keep track of the destination if the remote url is redirected somewhere else
			if ( isset( $headers['x-final-location'] ) && $headers['x-final-location'] != $url ) {
				$this->_imported_post_id( $headers['x-final-location'], $upload['url'] );
			}

			return $upload;
		}


		private function _content_install_widgets() {
			// todo: pump these out into the 'content/' folder along with the XML so it's a little nicer to play with
//			$import_widget_positions = $this->_get_json( 'widget_positions.json' );
//			$import_widget_options   = $this->_get_json( 'widget_options.json' );
//
//			// importing.
//			$widget_positions = get_option( 'sidebars_widgets' );
//			if ( ! is_array( $widget_positions ) ) {
//				$widget_positions = array();
//			}
//
//			foreach ( $import_widget_options as $widget_name => $widget_options ) {
//				// replace certain elements with updated imported entries.
//				foreach ( $widget_options as $widget_option_id => $widget_option ) {
//
//					// replace TERM ids in widget settings.
//					foreach ( array( 'nav_menu' ) as $key_to_replace ) {
//						if ( ! empty( $widget_option[ $key_to_replace ] ) ) {
//							// check if this one has been imported yet.
//							$new_id = $this->_imported_term_id( $widget_option[ $key_to_replace ] );
//							if ( ! $new_id ) {
//								// do we really clear this out? nah. well. maybe.. hmm.
//							} else {
//								$widget_options[ $widget_option_id ][ $key_to_replace ] = $new_id;
//							}
//						}
//					}
//					// replace POST ids in widget settings.
//					foreach ( array( 'image_id', 'post_id' ) as $key_to_replace ) {
//						if ( ! empty( $widget_option[ $key_to_replace ] ) ) {
//							// check if this one has been imported yet.
//							$new_id = $this->_imported_post_id( $widget_option[ $key_to_replace ] );
//							if ( ! $new_id ) {
//								// do we really clear this out? nah. well. maybe.. hmm.
//							} else {
//								$widget_options[ $widget_option_id ][ $key_to_replace ] = $new_id;
//							}
//						}
//					}
//				}
//				$existing_options = get_option( 'widget_' . $widget_name, array() );
//				if ( ! is_array( $existing_options ) ) {
//					$existing_options = array();
//				}
//				$new_options = $existing_options + $widget_options;
//				update_option( 'widget_' . $widget_name, $new_options );
//			}
//			update_option( 'sidebars_widgets', array_merge( $widget_positions, $import_widget_positions ) );

			return true;

		}

		public function _get_json( $file ) {

			$theme_style = __DIR__.'/content/'.basename(get_theme_mod('dtbwp_site_style', $this->get_default_theme_style())).'/';
			if ( is_file( $theme_style . basename( $file ) ) ) {
				WP_Filesystem();
				global $wp_filesystem;
				$file_name = $theme_style . basename( $file );
				if ( file_exists( $file_name ) ) {
					return json_decode( $wp_filesystem->get_contents( $file_name ), true );
				}
			}
            // backwards compat:
			if ( is_file( __DIR__ . '/content/' . basename( $file ) ) ) {
				WP_Filesystem();
				global $wp_filesystem;
				$file_name = __DIR__ . '/content/' . basename( $file );
				if ( file_exists( $file_name ) ) {
					return json_decode( $wp_filesystem->get_contents( $file_name ), true );
				}
			}

			return array();
		}

		private function _get_sql( $file ) {
			if ( is_file( __DIR__ . '/content/' . basename( $file ) ) ) {
				WP_Filesystem();
				global $wp_filesystem;
				$file_name = __DIR__ . '/content/' . basename( $file );
				if ( file_exists( $file_name ) ) {
					return $wp_filesystem->get_contents( $file_name );
				}
			}

			return false;
		}


		public $logs = array();

		public function log( $message ) {
			$this->logs[] = $message;
		}

		public $errors = array();

		public function error( $message ) {
			$this->logs[] = 'ERROR!!!! ' . $message;
		}

		public function envato_setup_color_style() {

			?>
            <h1><?php esc_html_e( 'Site Style' ); ?></h1>
            <form method="post">
                <p><?php esc_html_e( 'Please choose your site style below.' ); ?></p>

                <div class="theme-presets">
                    <ul>
	                    <?php
	                    $current_style = get_theme_mod( 'dtbwp_site_style', $this->get_default_theme_style() );
	                    foreach ( $this->site_styles as $style_name => $style_data ) {
		                    ?>
                            <li<?php echo $style_name == $current_style ? ' class="current" ' : ''; ?>>
                                <a href="#" data-style="<?php echo esc_attr( $style_name ); ?>"><img
                                            src="<?php echo esc_url(get_template_directory_uri() .'/plugins/envato_setup/images/'.$style_name.'/style.jpg');?>"></a>
                            </li>
	                    <?php } ?>
                    </ul>
                </div>

                <input type="hidden" name="new_style" id="new_style" value="">

                <p><em>Please Note: Advanced changes to website graphics/colors may require extensive PhotoShop and Web
                        Development knowledge. We recommend hiring an expert from <a
                                href="http://studiotracking.envato.com/aff_c?offer_id=4&aff_id=1564&source=DemoInstall"
                                target="_blank">Envato Studio</a> to assist with any advanced website changes.</em></p>
                <div style="display: none;">
                    <img src="http://studiotracking.envato.com/aff_i?offer_id=4&aff_id=1564&source=DemoInstall"
                         width="1" height="1"/>
                </div>

                <p class="envato-setup-actions step">
                    <input type="submit" class="button-primary button button-large button-next"
                           value="<?php esc_attr_e( 'Continue' ); ?>" name="save_step"/>
                    <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
                       class="button button-large button-next"><?php esc_html_e( 'Skip this step' ); ?></a>
					<?php wp_nonce_field( 'envato-setup' ); ?>
                </p>
            </form>
			<?php
		}

		/**
		 * Save logo & design options
		 */
		public function envato_setup_color_style_save() {
			check_admin_referer( 'envato-setup' );

			$new_style = isset( $_POST['new_style'] ) ? $_POST['new_style'] : false;
			if ( $new_style ) {
				set_theme_mod( 'dtbwp_site_style', $new_style );
			}

			wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
			exit;
		}

		/**
		 * Logo & Design
		 */
		public function envato_setup_logo_design() {?>

			<h1><?php esc_html_e('Logo', 'knd');?></h1>
			<form method="post">
				<p><?php printf(esc_html__('Please add your organization main logo below. The recommended size is 315x66px for Image only mode and 66x66px for Image with site name. The logo can be changed at any time from the Appearance > Customize area in your dashboard. Try our %sPaseka program%s if you need a new logo designed.', 'knd'), '<a href="https://paseka.te-st.ru/" target="_blank">', '</a>');?></p>
				<table>
					<tr>
						<td>
							<div id="current-logo">
                            <?php $image_url = knd_get_logo_img_url();
                            if($image_url) {
                                printf('<img class="site-logo" src="%s" alt="%s" style="width: %s; height: auto;">', $image_url, get_bloginfo('name'), $this->get_header_logo_width());
                            }?>
							</div>
						</td>
						<td>
							<a href="#" class="button button-upload"><?php esc_html_e( 'Upload New Logo' ); ?></a>
						</td>
					</tr>
				</table>

				<input type="hidden" name="new_logo_id" id="new_logo_id" value="">

				<p class="envato-setup-actions step">
					<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e('Continue', 'knd');?>" name="save_step">
					<a href="<?php echo esc_url($this->get_next_step_link());?>" class="button button-large button-next">
                        <?php esc_html_e('Skip this step', 'knd');?>
                    </a>
					<?php wp_nonce_field('knd-setup-design');?>
				</p>
			</form>
			<?php
		}

		/**
		 * Save logo & design options
		 */
		public function envato_setup_logo_design_save() {

			check_admin_referer('knd-setup-design');

			$new_logo_id = (int)$_POST['new_logo_id'];

			if($new_logo_id) {
				$attr = wp_get_attachment_image_src($new_logo_id, 'full');
				if ( $attr && ! empty( $attr[1] ) && ! empty( $attr[2] ) ) {

					set_theme_mod( 'custom_logo', $new_logo_id );

					// we have a width and height for this image. awesome.
//					$logo_width  = (int) get_theme_mod( 'logo_header_image_width', '467' );
//					$scale       = $logo_width / $attr[1];
//					$logo_height = intval( $attr[2] * $scale );
//					if ( $logo_height > 0 ) {
//						set_theme_mod( 'logo_header_image_height', $logo_height );
//					}
				}
			}

			wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
			exit;

		}

		/**
		 * Payments Step
		 */
		public function envato_setup_updates() {
			?>
			<h1><?php esc_html_e( 'Theme Updates' ); ?></h1>
			<?php if ( function_exists( 'envato_market' ) ) { ?>
				<form method="post">
					<?php
					$option = envato_market()->get_options();

					$my_items = array();
					if ( $option && ! empty( $option['items'] ) ) {
						foreach ( $option['items'] as $item ) {
							if ( ! empty( $item['oauth'] ) && ! empty( $item['token_data']['expires'] ) && $item['oauth'] == $this->envato_username && $item['token_data']['expires'] >= time() ) {
								// token exists and is active
								$my_items[] = $item;
							}
						}
					}
					if ( count( $my_items ) ) {
						?>
						<p>Thanks! Theme updates have been enabled for the following items: </p>
						<ul>
							<?php foreach ( $my_items as $item ) { ?>
								<li><?php echo esc_html( $item['name'] ); ?></li>
							<?php } ?>
						</ul>
						<p>When an update becomes available it will show in the Dashboard with an option to install.</p>
						<p>Change settings from the 'Envato Market' menu in the WordPress Dashboard.</p>

						<p class="envato-setup-actions step">
							<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
							   class="button button-large button-next button-primary"><?php esc_html_e( 'Continue' ); ?></a>
						</p>
						<?php
					} else {
						?>
						<p><?php esc_html_e( 'Please login using your ThemeForest account to enable Theme Updates. We update themes when a new feature is added or a bug is fixed. It is highly recommended to enable Theme Updates.' ); ?></p>
						<p>When an update becomes available it will show in the Dashboard with an option to install.</p>
						<p>
							<em>On the next page you will be asked to Login with your ThemeForest account and grant
								permissions to enable Automatic Updates. If you have any questions please <a
									href="http://dtbaker.net/envato/" target="_blank">contact us</a>.</em>
						</p>
						<p class="envato-setup-actions step">
							<input type="submit" class="button-primary button button-large button-next"
							       value="<?php esc_attr_e( 'Login with Envato' ); ?>" name="save_step"/>
							<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
							   class="button button-large button-next"><?php esc_html_e( 'Skip this step' ); ?></a>
							<?php wp_nonce_field( 'envato-setup' ); ?>
						</p>
					<?php } ?>
				</form>
			<?php } else { ?>
				Please ensure the Envato Market plugin has been installed correctly. <a
					href="<?php echo esc_url( $this->get_step_link( 'default_plugins' ) ); ?>">Return to Required
					Plugins installer</a>.
			<?php } ?>
			<?php
		}

		/**
		 * Payments Step save
		 */
		public function envato_setup_updates_save() {
			check_admin_referer( 'envato-setup' );

			// redirect to our custom login URL to get a copy of this token.
			$url = $this->get_oauth_login_url( $this->get_step_link( 'updates' ) );

			wp_redirect( esc_url_raw( $url ) );
			exit;
		}


		public function envato_setup_customize() {
			?>

			<h1>Theme Customization</h1>
			<p>
				Most changes to the website can be made through the Appearance > Customize menu from the WordPress
				dashboard. These include:
			</p>
			<ul>
				<li>Typography: Font Sizes, Style, Colors (over 200 fonts to choose from) for various page elements.
				</li>
				<li>Logo: Upload a new logo and adjust its size.</li>
				<li>Background: Upload a new background image.</li>
				<li>Layout: Enable/Disable responsive layout, page and sidebar width.</li>
			</ul>
			<p>To change the Sidebars go to Appearance > Widgets. Here widgets can be "drag &amp; droped" into sidebars.
				To control which "widget areas" appear, go to an individual page and look for the "Left/Right Column"
				menu. Here widgets can be chosen for display on the left or right of a page. More details in
				documentation.</p>
			<p>
				<em>Advanced Users: If you are going to make changes to the theme source code please use a <a
						href="https://codex.wordpress.org/Child_Themes" target="_blank">Child Theme</a> rather than
					modifying the main theme HTML/CSS/PHP code. This allows the parent theme to receive updates without
					overwriting your source code changes. <br/> See <code>child-theme.zip</code> in the main folder for
					a sample.</em>
			</p>

			<p class="envato-setup-actions step">
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
				   class="button button-primary button-large button-next"><?php esc_html_e( 'Continue' ); ?></a>
			</p>

			<?php
		}

		public function envato_setup_help_support() {
			?>
			<h1>Help and Support</h1>
			<p>This theme comes with 6 months item support from purchase date (with the option to extend this period).
				This license allows you to use this theme on a single website. Please purchase an additional license to
				use this theme on another website.</p>
			<p>Item Support can be accessed from <a href="http://dtbaker.net/envato/" target="_blank">http://dtbaker.net/envato/</a>
				and includes:</p>
			<ul>
				<li>Availability of the author to answer questions</li>
				<li>Answering technical questions about item features</li>
				<li>Assistance with reported bugs and issues</li>
				<li>Help with bundled 3rd party plugins</li>
			</ul>

			<p>Item Support <strong>DOES NOT</strong> Include:</p>
			<ul>
				<li>Customization services (this is available through <a
						href="http://studiotracking.envato.com/aff_c?offer_id=4&aff_id=1564&source=DemoInstall"
						target="_blank">Envato Studio</a>)
				</li>
				<li>Installation services (this is available through <a
						href="http://studiotracking.envato.com/aff_c?offer_id=4&aff_id=1564&source=DemoInstall"
						target="_blank">Envato Studio</a>)
				</li>
				<li>Help and Support for non-bundled 3rd party plugins (i.e. plugins you install yourself later on)</li>
			</ul>
			<p>More details about item support can be found in the ThemeForest <a
					href="http://themeforest.net/page/item_support_policy" target="_blank">Item Support Polity</a>. </p>
			<p class="envato-setup-actions step">
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
				   class="button button-primary button-large button-next"><?php esc_html_e( 'Agree and Continue' ); ?></a>
				<?php wp_nonce_field( 'envato-setup' ); ?>
			</p>
			<?php
		}

		/**
		 * Final step
		 */
		public function envato_setup_ready() {

			update_option( 'envato_setup_complete', time() );
			update_option( 'dtbwp_update_notice', strtotime('-4 days') );
			?>
			<a href="https://twitter.com/share" class="twitter-share-button"
			   data-url="http://themeforest.net/user/dtbaker/portfolio?ref=dtbaker"
			   data-text="<?php echo esc_attr( 'I just installed the ' . wp_get_theme() . ' #WordPress theme from #ThemeForest' ); ?>"
			   data-via="EnvatoMarket" data-size="large">Tweet</a>
			<script>!function (d, s, id) {
					var js, fjs = d.getElementsByTagName(s)[0];
					if (!d.getElementById(id)) {
						js = d.createElement(s);
						js.id = id;
						js.src = "//platform.twitter.com/widgets.js";
						fjs.parentNode.insertBefore(js, fjs);
					}
				}(document, "script", "twitter-wjs");</script>

			<h1><?php esc_html_e( 'Your Website is Ready!' ); ?></h1>

			<p>Congratulations! The theme has been activated and your website is ready. Login to your WordPress
				dashboard to make changes and modify any of the default content to suit your needs.</p>
			<p>Please come back and <a href="http://themeforest.net/downloads" target="_blank">leave a 5-star rating</a>
				if you are happy with this theme. <br/>Follow <a href="https://twitter.com/dtbaker" target="_blank">@dtbaker</a>
				on Twitter to see updates. Thanks! </p>

			<div class="envato-setup-next-steps">
				<div class="envato-setup-next-steps-first">
					<h2><?php esc_html_e( 'Next Steps' ); ?></h2>
					<ul>
						<li class="setup-product"><a class="button button-primary button-large"
						                             href="https://twitter.com/dtbaker"
						                             target="_blank"><?php esc_html_e( 'Follow @dtbaker on Twitter' ); ?></a>
						</li>
						<li class="setup-product"><a class="button button-next button-large"
						                             href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'View your new website!' ); ?></a>
						</li>
					</ul>
				</div>
				<div class="envato-setup-next-steps-last">
					<h2><?php esc_html_e( 'More Resources' ); ?></h2>
					<ul>
						<li class="documentation"><a href="http://dtbaker.net/envato/documentation/"
						                             target="_blank"><?php esc_html_e( 'Read the Theme Documentation' ); ?></a>
						</li>
						<li class="howto"><a href="https://wordpress.org/support/"
						                     target="_blank"><?php esc_html_e( 'Learn how to use WordPress' ); ?></a>
						</li>
						<li class="rating"><a href="http://themeforest.net/downloads"
						                      target="_blank"><?php esc_html_e( 'Leave an Item Rating' ); ?></a></li>
						<li class="support"><a href="http://dtbaker.net/envato/"
						                       target="_blank"><?php esc_html_e( 'Get Help and Support' ); ?></a></li>
					</ul>
				</div>
			</div>
			<?php
		}

//		public function envato_market_admin_init() {
//
//			if ( ! function_exists( 'envato_market' ) ) {
//				return;
//			}
//
//			global $wp_settings_sections;
//			if ( ! isset( $wp_settings_sections[ envato_market()->get_slug() ] ) ) {
//				// means we're running the admin_init hook before envato market gets to setup settings area.
//				// good - this means our oauth prompt will appear first in the list of settings blocks
//				register_setting( envato_market()->get_slug(), envato_market()->get_option_name() );
//			}
//
//			// pull our custom options across to envato.
//			$option         = get_option( 'envato_setup_wizard', array() );
//			$envato_options = envato_market()->get_options();
//			$envato_options = $this->_array_merge_recursive_distinct( $envato_options, $option );
//			if(!empty($envato_options['items'])) {
//				foreach($envato_options['items'] as $key => $item) {
//					if(!empty($item['id']) && is_string($item['id'])) {
//						$envato_options['items'][$key]['id'] = (int)$item['id'];
//					}
//				}
//			}
//			update_option( envato_market()->get_option_name(), $envato_options );
//
//			//add_thickbox();
//
//			if ( ! empty( $_POST['oauth_session'] ) && ! empty( $_POST['bounce_nonce'] ) && wp_verify_nonce( $_POST['bounce_nonce'], 'envato_oauth_bounce_' . $this->envato_username ) ) {
//				// request the token from our bounce url.
//				$my_theme    = wp_get_theme();
//				$oauth_nonce = get_option( 'envato_oauth_' . $this->envato_username );
//				if ( ! $oauth_nonce ) {
//					// this is our 'private key' that is used to request a token from our api bounce server.
//					// only hosts with this key are allowed to request a token and a refresh token
//					// the first time this key is used, it is set and locked on the server.
//					$oauth_nonce = wp_create_nonce( 'envato_oauth_nonce_' . $this->envato_username );
//					update_option( 'envato_oauth_' . $this->envato_username, $oauth_nonce );
//				}
//				$response = wp_remote_post( $this->oauth_script, array(
//						'method'      => 'POST',
//						'timeout'     => 15,
//						'redirection' => 1,
//						'httpversion' => '1.0',
//						'blocking'    => true,
//						'headers'     => array(),
//						'body'        => array(
//							'oauth_session' => $_POST['oauth_session'],
//							'oauth_nonce'   => $oauth_nonce,
//							'get_token'     => 'yes',
//							'url'           => home_url(),
//							'theme'         => $my_theme->get( 'Name' ),
//							'version'       => $my_theme->get( 'Version' ),
//						),
//						'cookies'     => array(),
//					)
//				);
//				if ( is_wp_error( $response ) ) {
//					$error_message = $response->get_error_message();
//					$class         = 'error';
//					echo "<div class=\"$class\"><p>" . sprintf( esc_html__( 'Something went wrong while trying to retrieve oauth token: %s' ), $error_message ) . '</p></div>';
//				} else {
//					$token  = @json_decode( wp_remote_retrieve_body( $response ), true );
//					$result = false;
//					if ( is_array( $token ) && ! empty( $token['access_token'] ) ) {
//						$token['oauth_session'] = $_POST['oauth_session'];
//						$result                 = $this->_manage_oauth_token( $token );
//					}
//					if ( $result !== true ) {
//						echo 'Failed to get oAuth token. Please go back and try again';
//						exit;
//					}
//				}
//			}
//
//			add_settings_section(
//				envato_market()->get_option_name() . '_' . $this->envato_username . '_oauth_login',
//				sprintf( esc_html__( 'Login for %s updates' ), $this->envato_username ),
//				array( $this, 'render_oauth_login_description_callback' ),
//				envato_market()->get_slug()
//			);
//			// Items setting.
//			add_settings_field(
//				$this->envato_username . 'oauth_keys',
//				esc_html__( 'oAuth Login' ),
//				array( $this, 'render_oauth_login_fields_callback' ),
//				envato_market()->get_slug(),
//				envato_market()->get_option_name() . '_' . $this->envato_username . '_oauth_login'
//			);
//		}

//		private static $_current_manage_token = false;

//		private function _manage_oauth_token( $token ) {
//			if ( is_array( $token ) && ! empty( $token['access_token'] ) ) {
//				if ( self::$_current_manage_token == $token['access_token'] ) {
//					return false; // stop loops when refresh auth fails.
//				}
//				self::$_current_manage_token = $token['access_token'];
//				// yes! we have an access token. store this in our options so we can get a list of items using it.
//				$option = get_option( 'envato_setup_wizard', array() );
//				if ( ! is_array( $option ) ) {
//					$option = array();
//				}
//				if ( empty( $option['items'] ) ) {
//					$option['items'] = array();
//				}
//				// check if token is expired.
//				if ( empty( $token['expires'] ) ) {
//					$token['expires'] = time() + 3600;
//				}
//				if ( $token['expires'] < time() + 120 && ! empty( $token['oauth_session'] ) ) {
//					// time to renew this token!
//					$my_theme    = wp_get_theme();
//					$oauth_nonce = get_option( 'envato_oauth_' . $this->envato_username );
//					$response    = wp_remote_post( $this->oauth_script, array(
//							'method'      => 'POST',
//							'timeout'     => 10,
//							'redirection' => 1,
//							'httpversion' => '1.0',
//							'blocking'    => true,
//							'headers'     => array(),
//							'body'        => array(
//								'oauth_session' => $token['oauth_session'],
//								'oauth_nonce'   => $oauth_nonce,
//								'refresh_token' => 'yes',
//								'url'           => home_url(),
//								'theme'         => $my_theme->get( 'Name' ),
//								'version'       => $my_theme->get( 'Version' ),
//							),
//							'cookies'     => array(),
//						)
//					);
//					if ( is_wp_error( $response ) ) {
//						$error_message = $response->get_error_message();
//						// we clear any stored tokens which prompts the user to re-auth with the update server.
//                        $this->_clear_oauth();
//					} else {
//						$new_token = @json_decode( wp_remote_retrieve_body( $response ), true );
//						$result    = false;
//						if ( is_array( $new_token ) && ! empty( $new_token['new_token'] ) ) {
//							$token['access_token'] = $new_token['new_token'];
//							$token['expires']      = time() + 3600;
//						}else {
//							//refresh failed, we clear any stored tokens which prompts the user to re-register.
//                            $this->_clear_oauth();
//						}
//					}
//				}
//				// use this token to get a list of purchased items
//				// add this to our items array.
//				$response                    = envato_market()->api()->request( 'https://api.envato.com/v3/market/buyer/purchases', array(
//					'headers' => array(
//						'Authorization' => 'Bearer ' . $token['access_token'],
//					),
//				) );
//				self::$_current_manage_token = false;
//				if ( is_array( $response ) && is_array( $response['purchases'] ) ) {
//					// up to here, add to items array
//					foreach ( $response['purchases'] as $purchase ) {
//						// check if this item already exists in the items array.
//						$exists = false;
//						foreach ( $option['items'] as $id => $item ) {
//							if ( ! empty( $item['id'] ) && $item['id'] == $purchase['item']['id'] ) {
//								$exists = true;
//								// update token.
//								$option['items'][ $id ]['token']      = $token['access_token'];
//								$option['items'][ $id ]['token_data'] = $token;
//								$option['items'][ $id ]['oauth']      = $this->envato_username;
//								if ( ! empty( $purchase['code'] ) ) {
//									$option['items'][ $id ]['purchase_code'] = $purchase['code'];
//								}
//							}
//						}
//						if ( ! $exists ) {
//							$option['items'][] = array(
//								'id'            => '' . $purchase['item']['id'],
//								// item id needs to be a string for market download to work correctly.
//								'name'          => $purchase['item']['name'],
//								'token'         => $token['access_token'],
//								'token_data'    => $token,
//								'oauth'         => $this->envato_username,
//								'type'          => ! empty( $purchase['item']['wordpress_theme_metadata'] ) ? 'theme' : 'plugin',
//								'purchase_code' => ! empty( $purchase['code'] ) ? $purchase['code'] : '',
//							);
//						}
//					}
//				} else {
//					return false;
//				}
//				if ( ! isset( $option['oauth'] ) ) {
//					$option['oauth'] = array();
//				}
//				// store our 1 hour long token here. we can refresh this token when it comes time to use it again (i.e. during an update)
//				$option['oauth'][ $this->envato_username ] = $token;
//				update_option( 'envato_setup_wizard', $option );
//
//				$envato_options = envato_market()->get_options();
//				$envato_options = $this->_array_merge_recursive_distinct( $envato_options, $option );
//				update_option( envato_market()->get_option_name(), $envato_options );
//				envato_market()->items()->set_themes( true );
//				envato_market()->items()->set_plugins( true );
//
//				return true;
//			} else {
//				return false;
//			}
//		}
//
//		public function _clear_oauth() {
//			$envato_options = envato_market()->get_options();
//			unset( $envato_options['oauth'] );
//			update_option( envato_market()->get_option_name(), $envato_options );
//		}



		public function ajax_notice_handler() {
			check_ajax_referer( 'dtnwp-ajax-nonce', 'security' );
			// Store it in the options table
			update_option( 'dtbwp_update_notice', time() );
		}

		public function admin_theme_auth_notice() {


			if(function_exists('envato_market')) {
				$option = envato_market()->get_options();

				$envato_items = get_option( 'envato_setup_wizard', array() );

				if ( !$option || empty($option['oauth']) || empty( $option['oauth'][ $this->envato_username ] ) || empty($envato_items) || empty($envato_items['items']) || !envato_market()->items()->themes( 'purchased' )) {

					// we show an admin notice if it hasn't been dismissed
					$dissmissed_time = get_option('dtbwp_update_notice', false );

					if ( ! $dissmissed_time || $dissmissed_time < strtotime('-7 days') ) {
						// Added the class "notice-my-class" so jQuery pick it up and pass via AJAX,
						// and added "data-notice" attribute in order to track multiple / different notices
						// multiple dismissible notice states ?>
                        <div class="notice notice-warning notice-dtbwp-themeupdates is-dismissible">
                            <p><?php
                            _e( 'Please activate ThemeForest updates to ensure you have the latest version of this theme.' );
                                ?></p>
                            <p>
                            <?php printf( __( '<a class="button button-primary" href="%s">Activate Updates</a>' ),  esc_url($this->get_oauth_login_url( admin_url( 'admin.php?page=' . envato_market()->get_slug() . '' ) ) ) ); ?>
                            </p>
                        </div>
                        <script type="text/javascript">
                            jQuery(function($) {
                                $( document ).on( 'click', '.notice-dtbwp-themeupdates .notice-dismiss', function () {
                                    $.ajax( ajaxurl,
                                        {
                                            type: 'POST',
                                            data: {
                                                action: 'dtbwp_update_notice_handler',
                                                security: '<?php echo wp_create_nonce( "dtnwp-ajax-nonce" ); ?>'
                                            }
                                        } );
                                } );
                            });
                        </script>
					<?php }

				}
			}



		}
		/**
		 * @param $array1
		 * @param $array2
		 *
		 * @return mixed
		 *
		 *
		 * @since    1.1.4
		 */
		private function _array_merge_recursive_distinct( $array1, $array2 ) {
			$merged = $array1;
			foreach ( $array2 as $key => &$value ) {
				if ( is_array( $value ) && isset( $merged [ $key ] ) && is_array( $merged [ $key ] ) ) {
					$merged [ $key ] = $this->_array_merge_recursive_distinct( $merged [ $key ], $value );
				} else {
					$merged [ $key ] = $value;
				}
			}

			return $merged;
		}

		/**
		 * @param $args
		 * @param $url
		 *
		 * @return mixed
		 *
		 * Filter the WordPress HTTP call args.
		 * We do this to find any queries that are using an expired token from an oAuth bounce login.
		 * Since these oAuth tokens only last 1 hour we have to hit up our server again for a refresh of that token before using it on the Envato API.
		 * Hacky, but only way to do it.
		 */
		public function envato_market_http_request_args( $args, $url ) {
			if ( strpos( $url, 'api.envato.com' ) && function_exists( 'envato_market' ) ) {
				// we have an API request.
				// check if it's using an expired token.
				if ( ! empty( $args['headers']['Authorization'] ) ) {
					$token = str_replace( 'Bearer ', '', $args['headers']['Authorization'] );
					if ( $token ) {
						// check our options for a list of active oauth tokens and see if one matches, for this envato username.
						$option = envato_market()->get_options();
						if ( $option && ! empty( $option['oauth'][ $this->envato_username ] ) && $option['oauth'][ $this->envato_username ]['access_token'] == $token && $option['oauth'][ $this->envato_username ]['expires'] < time() + 120 ) {
							// we've found an expired token for this oauth user!
							// time to hit up our bounce server for a refresh of this token and update associated data.
							$this->_manage_oauth_token( $option['oauth'][ $this->envato_username ] );
							$updated_option = envato_market()->get_options();
							if ( $updated_option && ! empty( $updated_option['oauth'][ $this->envato_username ]['access_token'] ) ) {
								// hopefully this means we have an updated access token to deal with.
								$args['headers']['Authorization'] = 'Bearer ' . $updated_option['oauth'][ $this->envato_username ]['access_token'];
							}
						}
					}
				}
			}

			return $args;
		}

//		public function render_oauth_login_description_callback() {
//			echo 'If you have purchased items from ' . esc_html( $this->envato_username ) . ' on ThemeForest or CodeCanyon please login here for quick and easy updates.';
//
//		}

    /*
		public function render_oauth_login_fields_callback() {
			$option = envato_market()->get_options();
			?>
			<div class="oauth-login" data-username="<?php echo esc_attr( $this->envato_username ); ?>">
				<a href="<?php echo esc_url( $this->get_oauth_login_url( admin_url( 'admin.php?page=' . envato_market()->get_slug() . '#settings' ) ) ); ?>"
				   class="oauth-login-button button button-primary">Login with Envato to activate updates</a>
			</div>
			<?php
		}
    */

		/// a better filter would be on the post-option get filter for the items array.
		// we can update the token there.

//		public function get_oauth_login_url( $return ) {
//			return $this->oauth_script . '?bounce_nonce=' . wp_create_nonce( 'envato_oauth_bounce_' . $this->envato_username ) . '&wp_return=' . urlencode( $return );
//		}

		/**
		 * Helper function
		 * Take a path and return it clean
		 *
		 * @param string $path
		 *
		 * @since    1.1.2
		 */
		public static function cleanFilePath( $path ) {
			$path = str_replace( '', '', str_replace( array( '\\', '\\\\', '//' ), '/', $path ) );
			if ( $path[ strlen( $path ) - 1 ] === '/' ) {
				$path = rtrim( $path, '/' );
			}

			return $path;
		}

		public function is_submenu_page() {
			return !!$this->parent_slug;
		}
	}

}// if !class_exists

/**
 * Loads the main instance of Envato_Theme_Setup_Wizard to have
 * ability extend class functionality
 *
 * @since 1.1.1
 * @return object Envato_Theme_Setup_Wizard
 */
add_action('after_setup_theme', 'envato_theme_setup_wizard');
if( !function_exists('envato_theme_setup_wizard') ) {
    function envato_theme_setup_wizard() {

        if( !is_admin() ) {
            return;
        }

        Envato_Theme_Setup_Wizard::get_instance();

    }
}
//add_action('init', 'envato_theme_setup_wizard', 1); // No admin_init here!

// To remove the notice from Disable Comments plugin:
add_action('wp_loaded', function(){
    if(
        class_exists('Disable_Comments') &&
        has_action('admin_print_footer_scripts', array(Disable_Comments::get_instance(), 'discussion_notice'))
    ) {
        remove_action('admin_print_footer_scripts', array(Disable_Comments::get_instance(), 'discussion_notice'));
    }
}, 100);