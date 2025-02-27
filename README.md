# NotionLink

This plugin adds a Notion icon next to every plugin on the plugins page that is registered so that a user can quickly
and easily view any documentation on said plugin in Notion.

This plugin is designed to read data from a remote server so that the plugin can be used across multiple websites while
still pointing back to the same centralized pages. This JSON document simply contains a list of plugins with the
corresponding Notion page URL's.


## Configuration

To set the remote URL for the JSON, simply add a constant in your `functions.php` or `wp-config.php` file.

```php
define( 'NOTIONLINK_ENDPOINT', 'https://someurl.com/data.json' );
```


## The Data

The plugin expects a JSON structure like the following:

```JSON
[
    {
        "plugin": "advanced-custom-fields-pro/acf.php",
        "url": "https://www.notion.so/link/to/the/page"
    }
]
```

The `plugin` property should be a qualified name of the plugin, not just the entrypoint file.


## Hooks

There are a few hooks available.

| Hook | Description |
|------|-------------|
| `notionlink_plugin_data`          | The raw data before it is cached. Data being the array of plugins and notion links, fetched from the web. |
| `notionlink_notion_icon`          | The svg icon. |
| `notionlink_link_html`            | The anchor and icon, right before it gets set to the plugin metadata. |
| `notionlink_remote_request_args`  | Add any args for the remote request, such as headers or tokens. |

