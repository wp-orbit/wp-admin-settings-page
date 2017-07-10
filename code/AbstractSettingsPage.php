<?php

namespace WPOrbit\Components\Administration;

/**
 * Class AbstractSettingsPage
 *
 * @package WPOrbit\Components\Administration
 */
class AbstractSettingsPage
{
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
     * @var string
     */
    protected $default_tab = '';

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

    /**
     * AbstractSettingsPage constructor.
     *
     * @param array $args
     */
    public function __construct( $args = [] )
    {
        if ( isset( $args['capability'] ) )
        {
            $this->capability = $args['capability'];
        }
        if ( isset( $args['page_name'] ) )
        {
            $this->page_name = $args['page_name'];
        }
        if ( isset( $args['page_slug'] ) )
        {
            $this->page_slug = $args['page_slug'];
        }
        if ( isset( $args['tabs'] ) )
        {
            $this->tabs = $args['tabs'];
        }
        if ( isset( $args['show_tabs_in_menu'] ) )
        {
            $this->show_tabs_in_menu = $args['show_tabs_in_menu'];
        }

        /**
         * Fire save callbacks.
         */
        add_action( 'init', [$this, '_save'] );

        /**
         * Set up the settings page.
         */
        add_action( 'init', [$this, 'initialize'] );
    }

    public function initialize()
    {
        add_action( 'admin_menu', [$this, 'register_menu_page'] );

        // Show tabs as submenu links?
        if ( $this->show_tabs_in_menu )
        {
            add_action( 'admin_menu', [$this, 'register_submenu_links'] );
        }
    }

    public function add_feedback_message( $message, $type = 'success' )
    {
        $this->feedback[] = [
            'message' => $message,
            'type'    => $type
        ];
    }

    /**
     * Registers the top level administration menu page.
     */
    public function register_menu_page()
    {
        add_menu_page( $this->page_name, $this->page_name, $this->capability, $this->page_slug, [
            $this,
            'render_page'
        ] );
    }

    /**
     * Add tabs as submenu links.
     */
    public function register_submenu_links()
    {
        global $submenu;

        // Show tabs as sub menu items?
        if ( $this->show_tabs_in_menu )
        {
            // Loop through tabs.
            foreach ( $this->tabs as $key => $tab )
            {
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
    public function get_active_tab()
    {
        if ( isset( $_GET['tab'] ) )
        {
            return $_GET['tab'];
        }

        if ( ! empty( $this->default_tab ) )
        {
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
    public function get_base_url()
    {
        return "admin.php?page={$this->page_slug}";
    }

    /**
     * Print tabs.
     */
    public function render_tab_controls()
    {
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
    public function render_feedback()
    {
        // Loop through feedback messages.
        foreach ( $this->feedback as $feedback )
        {
            $class = '';

            if ( 'success' == $feedback['type'] )
            {
                $class = 'updated notice';
            }
            if ( 'error' == $feedback['type'] )
            {
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
    public function render_page()
    {
        $active_tab = $this->get_active_tab();
        ?>
        <div class="wrap">
            <h2><?php echo $this->page_name; ?> - <?php echo $this->tabs[ $active_tab ]; ?></h2>
            <?php $this->render_tab_controls(); ?>

            <?php if ( method_exists( $this, $active_tab ) ) : ?>
                <?php $this->render_feedback(); ?>
                <form method="post">
                    <?php $this->{$active_tab}(); ?>
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
    public function get_nonce_action()
    {
        return 'save-' . $this->page_slug . '-' . $this->get_active_tab();
    }

    /**
     * Render the form nonce.
     */
    public function render_nonce()
    {
        wp_nonce_field( $this->get_nonce_action() );
    }

    /**
     * @return bool
     */
    public function verify_nonce()
    {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $this->get_nonce_action() ) )
        {
            return false;
        }

        return true;
    }

    /** Override in extending classes. */
    public function save()
    {
        // Get the active tab.
        $active_tab = $this->get_active_tab();

        switch ( $active_tab )
        {
            default:
                $class_name = static::class;
                $this->add_feedback_message( "Override the save() function in {$class_name}.", 'error' );
                break;
        }
    }

    /**
     * Process POST data.
     */
    public function _save()
    {
        // Do nothing if $_POST is empty.
        if ( empty( $_POST ) || ! $this->verify_nonce() )
        {
            return;
        }

        $this->save();
    }
}


