# wp-admin-settings-page

An extensible class for quickly creating WordPress administration screens.

### Installation
```
composer require wp-orbit/wp-admin-settings-page
```

### Usage

Create an AbstractSettingsPage class extension.

```php
<?php

use WPOrbit\Components\Administration\AbstractSettingsPage;

class ExampleSettingsPage extends AbstractSettingsPage {

    public function __construct( $args = [] ) {

        // Define the options for our settings page.
        $args = [
            /* 0 through 100 */
            'position'    => null,

            /* If set, this page is added as a submenu page.
               If not set, this page is added as a top level menu item.
               Example: 'tools.php' for Tools or 'options-general.php' for Settings. */
            'parent_slug' => '',

            /* Sets the menu and page titles. */
            'page_name'   => 'Example Settings Page',

            /* A URL friendly unique slug for this menu page. */
            'page_slug'   => 'example-settings-page',

            /* A URL to image file or WP Dashicon string.
               If not set the default icon will display.
               https://developer.wordpress.org/resource/dashicons/ */
            'icon'        => '',

            /* An array of [tab_key => tab_label] key value pairs.
               These generate our tabs, define our rendering functions, and POST save callbacks.
               Each key needs a corresponding render function (with the same name) in this class.
               Note - make sure not to override any parent class functions (or just prefix your keys).
               Each value represents the tab label. */
            'tabs'        => [
                'options' => 'Options',
            ]
        ];

        // Initialize parent functionality.
        parent::__construct( $args );
    }

    /* Each render function is automatically wrapped in a <form> tag.
       Nonce token validation is performed automatically in the save callback,
       so render functions only need to print our form elements relevant per tab. */
    public function options() {

        // Get options.
        $my_option = get_option( 'my_option', 'Default value' );
        ?>

        <!-- Print form elements HTML for this. -->
        <p>
            <label>Input Option</label>
            <input name="my_option" value="<?php echo $my_option; ?>">
        </p>

        <!-- No form submit button is printed by default, so we need to include one. -->
        <p>
            <button class="button button-primary">
                Save
            </button>
        </p>
        <?php
    }

    /* When a form is submitted save() is called.
       Nonce token validation occurs automatically before save() fires. */
    public function save() {

        // At minimum we need to check for our form fields in $_POST.
        if ( isset( $_POST['my_option'] ) ) {

            // Sanitize user input when necessary.
            $clean = sanitize_text_field( $_POST['my_option'] );

            // Update the option.
            update_option( 'my_option', $clean );
        }

        // Or we can isolate tab updates with a switch case.
        switch ( $this->get_active_tab() ) {

            // Case key is the tab key.
            case 'options':

                // Perform $_POST updates.
                break;
        }
    }
}
```

Include the class in your theme or plugin, then instantiate the class 
to register the settings page into WordPress.

```php
<?php
new ExampleSettingsPage;
```

### Hooks
The settings page is pluggable.

Filters:
- wp_admin_settings_page_args_{$class_name} ($args)

Actions:
- wp_after_settings_page_after_tab_html ($class, $tab)
- wp_after_settings_page_before_tab_html ($class, $tab)

```php
<?php
$class_name = ExampleSettingsPage::class;

// Filter the constructor arguments.
add_filter( "wp_admin_settings_page_args_{$class_name}", function( $args ) {
    return $args;
});

// Do something before inner tab content. 
add_action( 'wp_admin_settings_page_before_tab_html', function( $class, $tab ) {
    if ( 'options' === $tab && ExampleSettingsPage::class === $class ) {   	
    }
}, 10, 2 );

// Do something after inner tab content.
add_action( 'wp_admin_settings_page_after_tab_html', function( $class, $tab ) {
    if ( 'options' === $tab && ExampleSettingsPage::class === $class ) {
    }
}, 10, 2 ); 
```
