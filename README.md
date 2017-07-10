# wp-admin-settings-page

```php
<?php
use WPOrbit\Components\Administration\AbstractSettingsPage;

class ExampleSettingsPage extends AbstractSettingsPage
{
    public function __construct( $args = [] )
    {
        $args = [
            'page_name' => 'My Settings',
            'page_slug' => 'my-settings',
            'tabs'      => [
                'options' => 'Options',
            ]
        ];

        parent::__construct( $args );
    }

    public function options()
    {
        $field_value = '...';
        ?>
        <p>
            <label>Input Option</label>
            <input name="field_name" value="<?php echo $field_value; ?>">
        </p>

        <p>
            <button type="submit" class="button button-primary">
                Save
            </button>
        </p>
        <?php
    }

    public function save()
    {
        switch ( $this->get_active_tab() )
        {
            case 'options':
                // Perform update callback for 'options' tab key.
                break;
        }
    }
}
```