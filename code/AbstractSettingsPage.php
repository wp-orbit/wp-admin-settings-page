<?php

namespace WPOrbit\Components\Administration;

/**
 * Class AbstractSettingsPage
 *
 * @package WPOrbit\Components\Administration
 */
class AbstractSettingsPage {

	/**
	 * Capability required for the user to access the settings page.
	 *
	 * @var string
	 */
	protected $capability = 'manage_options';

	/**
	 * Settings page title.
	 *
	 * @var mixed|string
	 */
	protected $page_name = 'Settings Page';

	/**
	 * @var mixed|string
	 */
	protected $page_slug = 'settings_page';

	/**
	 * A parent slug can be set to nest the settings under an existing page.
	 * For example- 'index.php', 'edit.php?post_type=post_type_slug', 'tools.php'
	 *
	 * @var string
	 */
	protected $parent_slug = '';

	/**
	 * @var string
	 */
	protected $default_tab = '';

	/**
	 * URL to icon or a WP Dash Icon
	 * https://developer.wordpress.org/resource/dashicons
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * @var int
	 */
	protected $position = null;

	/**
	 * @var array|mixed [[key => label], ... ]
	 */
	protected $tabs = [];

	/**
	 * @var array Feedback messages for the user.
	 */
	protected $feedback = [];

	/**
	 * @var bool
	 */
	protected $show_tabs_in_menu = true;

	protected $args = [];

	/**
	 * AbstractSettingsPage constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = [] ) {

		// Set args internally.
		$this->args = $args;

		/**
		 * Set up the settings page.
		 */
		add_action( 'init', [ $this, 'initialize' ] );
	}

	/**
	 * Set arguments on
	 */
	protected function apply_arguments() {

		// Add a filter for this class's arguments.
		$args = apply_filters( 'wp_admin_settings_page_args_' . static::class, $this->args );

		// List class parameters.
		$keys = [
			'capability',
			'icon',
			'page_name',
			'page_slug',
			'parent_slug',
			'position',
			'show_tabs_in_menu',
			'tabs',
		];

		// Set class parameters.
		foreach ( $keys as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$this->{$key} = $args[ $key ];
			}
		}
	}

	public function initialize() {

		// Set class arguments.
		$this->apply_arguments();

		add_action( 'admin_menu', [ $this, 'register_menu_page' ] );

		// Show tabs as submenu links?
		if ( $this->show_tabs_in_menu ) {
			add_action( 'admin_menu', [ $this, 'register_submenu_links' ] );
		}
	}

	public function add_feedback_message( $message, $type = 'success' ) {
		$this->feedback[] = [
			'message' => $message,
			'type'    => $type
		];
	}

	/**
	 * Registers the administration menu page.
	 */
	public function register_menu_page() {

		if ( empty( $this->parent_slug ) ) {
			add_menu_page( $this->page_name, $this->page_name, $this->capability, $this->page_slug, [
				$this,
				'render_page'
			], $this->icon, $this->position );
		} else {
			add_submenu_page( $this->parent_slug, $this->page_name, $this->page_name, $this->capability, $this->page_slug, [
				$this,
				'render_page'
			] );
		}
	}

	/**
	 * Add tabs as submenu links.
	 */
	public function register_submenu_links() {
		global $submenu;

		// Show tabs as sub menu items?
		if ( $this->show_tabs_in_menu ) {
			// Loop through tabs.
			foreach ( $this->tabs as $key => $tab ) {
				// Add tabs as submenu links.
				$submenu[ $this->page_slug ][] = [
					$tab,
					$this->capability,
					$this->get_base_url() . "&tab={$key}"
				];
			}
		}
	}

	/**
	 * @return string
	 */
	public function get_active_tab() {
		if ( isset( $_GET['tab'] ) ) {
			return $_GET['tab'];
		}

		if ( ! empty( $this->default_tab ) ) {
			return $this->default_tab;
		}

		// Get tab keys.
		$keys = array_keys( $this->tabs );

		return $keys[0];
	}

	/**
	 * Get base URL to settings page.
	 *
	 * @return string
	 */
	public function get_base_url() {
		return "admin.php?page={$this->page_slug}";
	}

	/**
	 * Print tabs.
	 */
	public function render_tab_controls() {
		// Get key.
		$active_tab = $this->get_active_tab();
		?>
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $this->tabs as $key => $tab ) : ?>
				<?php
				$tab_url   = $this->get_base_url() . "&tab={$key}";
				$tab_class = $key == $active_tab ? 'nav-tab nav-tab-active' : 'nav-tab';
				?>
				<a href="<?php echo $tab_url; ?>" class="<?php echo $tab_class; ?>">
					<?php echo $tab; ?>
				</a>
			<?php endforeach; ?>
		</h2>
		<?php
	}

	/**
	 * Render user feedback messages.
	 */
	public function render_feedback() {
		// Loop through feedback messages.
		foreach ( $this->feedback as $feedback ) {
			$class = '';

			if ( 'success' == $feedback['type'] ) {
				$class = 'updated notice';
			}
			if ( 'error' == $feedback['type'] ) {
				$class = 'error notice';
			}

			?>
			<div class="<?php echo $class; ?>">
				<p><?php echo $feedback['message']; ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Render the sub menu tab.
	 */
	public function render_page() {

		// Fire save callback.
		$this->_save();

		$class_name = static::class;
		$active_tab = $this->get_active_tab();
		?>
		<div class="wrap">
			<h2><?php echo $this->page_name; ?> - <?php echo $this->tabs[ $active_tab ]; ?></h2>
			<?php $this->render_tab_controls(); ?>

			<?php if ( method_exists( $this, $active_tab ) ) : ?>
				<?php $this->render_feedback(); ?>
				<form method="post">
					<?php
					// Render the tab HTML.
					ob_start();

					// Add a hook before the tab HTML.
					do_action( 'wp_admin_settings_page_before_tab_html', $class_name, $active_tab );

					// Call active tab function.
					$this->{$active_tab}();

					// Add a hook before the tab HTML.
					do_action( 'wp_admin_settings_page_after_tab_html', $class_name, $active_tab );

					// Filter the HTML.
					echo apply_filters( 'wp_admin_settings_page_tab_html_' . $class_name, ob_get_clean() ); ?>

					<?php $this->render_nonce(); ?>
				</form>
			<?php else : ?>
				<p>Method <?php echo $active_tab; ?>() is not defined in <?php echo static::class; ?>.</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @return string
	 */
	public function get_nonce_action() {
		return 'save-' . $this->page_slug . '-' . $this->get_active_tab();
	}

	/**
	 * Render the form nonce.
	 */
	public function render_nonce() {
		wp_nonce_field( $this->get_nonce_action() );
	}

	/**
	 * @return bool
	 */
	public function verify_nonce() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $this->get_nonce_action() ) ) {
			return false;
		}

		return true;
	}

	/** Override in extending classes. */
	public function save() {
		// Get the active tab.
		$active_tab = $this->get_active_tab();

		switch ( $active_tab ) {
			default:
				$class_name = static::class;
				$this->add_feedback_message( "Override the save() function in {$class_name}.", 'error' );
				break;
		}
	}

	/**
	 * Process POST data.
	 */
	public function _save() {
		// Do nothing if $_POST is empty.
		if ( empty( $_POST ) || ! $this->verify_nonce() ) {
			return;
		}

		$this->save();
	}
}

