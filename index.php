<?php
/**
 * Plugin Name:       NotionLink
 * Plugin URI:        https://github.com/neonarray/wp-notion-link
 * Description:       Adds a link to a corresponding Notion page for each plugin that is assigned.
 * Version:           1.1.1
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Aaron Arney <2738518+NeonArray@users.noreply.github.com>
 * Author URI:        https://github.com/neonarray
 */

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
    define( 'DAY_IN_SECONDS', 24 * 60 * 60 );
}

/**
 * Add settings link to plugin actions
 *
 * @param array $plugin_actions
 * @param string $plugin_file
 *
 * @return array
 * @since  1.0
 */
add_action( 'admin_init', static function () {
    if ( 'plugins.php' !== basename( $_SERVER['PHP_SELF'] ) ) {
        return;
    }
    new NotionLink();
}, 9999 );


/**
 *
 */
final class NotionLink {
    private const KEY = 'NotionLink_data';
    private array $pluginData;

    public function __construct() {
        if ( ! defined( 'NOTIONLINK_ENDPOINT' ) ) {
            add_action( 'admin_notices', static function () {
                $class   = 'notice notice-error';
                $message = __( 'No endpoint URL has been set. Add a `define( "NOTIONLINK_ENDPOINT, "https://url.com/example" );` to your functions.php or wp-config.php file.', 'NotionLink' );
                printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
            } );

            return;
        }

        if ( isset( $_GET['cacheBust'] ) ) {
            delete_transient( self::KEY );
        }

        $data = $this->getCache();

        if ( ! empty( $data ) ) {
            $this->pluginData = $data;
        } else {
            $this->pluginData = $this->getCachedOrLiveData();
        }

        add_filter( 'plugin_row_meta', [ $this, 'addNotionLinkToMeta' ], 9999, 4 );
    }


    /**
     * @param array $plugin_meta
     * @param string $plugin_file
     * @param array $plugin_data
     * @param string $status
     *
     * @return array
     */
    public function addNotionLinkToMeta( array $plugin_meta, string $plugin_file, array $plugin_data, string $status ): array {

        if ( empty( $this->pluginData ) ) {
            return [];
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return $plugin_meta;
        }

        $plugins = array_column( $this->pluginData, 'plugin' );

        if (
            in_array( $plugin_file, $plugins, true )
        ) {
            $url = array_column( $this->pluginData, 'url', 'plugin' )[ $plugin_file ];

            $link_and_icon = '<a href="' . $url . '" target="_blank" style="transform: translateY(5px);height: 21px;margin-left: 4px;display: inline-block;">' . notionIcon() . '</a>';

            /**
             * Filter the link and icon html right before it gets added to the plugin meta.
             *
             * @var string $link_and_icon
             */
            $link_and_icon = apply_filters( 'notionlink_link_html', $link_and_icon );

            $plugin_meta[] = $link_and_icon;
        }

        return $plugin_meta;
    }


    /**
     * @return array|false
     */
    private function getCache(): array|false {
        return get_transient( self::KEY );
    }


    /**
     * @param array $data
     *
     * @return void
     */
    private function setCache( array $data ): void {
        set_transient( self::KEY, $data, DAY_IN_SECONDS );
    }


    /**
     * @return array
     */
    private function getCachedOrLiveData(): array {
        if ( ! defined( 'NOTIONLINK_ENDPOINT' ) ) {
            return [];
        }

        $args = apply_filters( 'notionlink_remote_request_args', [] );
        $json = wp_remote_get( NOTIONLINK_ENDPOINT, $args );

        if ( is_wp_error( $json ) ) {
            return [];
        }

        try {
            $data = json_decode( $json['body'], true, 512, JSON_THROW_ON_ERROR );
        } catch ( JsonException $e ) {
            return [];
        } finally {
            /**
             * Filter the plugin data before it is saved into the cache.
             *
             * @var mixed $data The plugin data.
             */
            $data = apply_filters( 'notionlink_plugin_data', $data );

            // Only want to cache it if it exists and is valid - for 24 hours.
            if ( ! empty( $data ) ) {
                $this->setCache( $data );
            }
        }

        return $data;
    }
}


/**
 *
 * @return string
 */
function notionIcon(): string {
    $icon = '<svg height="" width="58" id="katman_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" style="enable-background:new 0 0 600 400;" xml:space="preserve" viewBox="95.08 125.72 413.83 150.55"><style type="text/css">	.st0{fill-rule:evenodd;clip-rule:evenodd;}</style><path d="M283.2,228.4v-39.7h0.7l28.6,39.7h9v-58.3h-10v39.6h-0.7l-28.6-39.6h-9v58.3L283.2,228.4L283.2,228.4z M349.8,229.3  c13.2,0,21.3-8.6,21.3-23c0-14.3-8.1-23-21.3-23c-13.1,0-21.3,8.7-21.3,23C328.6,220.7,336.6,229.3,349.8,229.3z M349.8,220.9  c-7,0-11-5.3-11-14.6c0-9.2,4-14.6,11-14.6c7,0,11,5.4,11,14.6C360.8,215.6,356.8,220.9,349.8,220.9z M380.2,173.4v11.1h-7v8h7v24.1  c0,8.6,4,12,14.2,12c1.9,0,3.8-0.2,5.3-0.5v-7.8c-1.2,0.1-2,0.2-3.4,0.2c-4.2,0-6.1-1.9-6.1-6.3v-21.7h9.5v-8h-9.5v-11.1  L380.2,173.4L380.2,173.4z M405.7,228.4h10v-44.2h-10V228.4z M410.7,176.9c3.3,0,6-2.7,6-6c0-3.4-2.7-6.1-6-6.1c-3.3,0-6,2.7-6,6.1  C404.7,174.2,407.4,176.9,410.7,176.9L410.7,176.9z M443.1,229.3c13.2,0,21.3-8.6,21.3-23c0-14.3-8.1-23-21.3-23  c-13.1,0-21.3,8.7-21.3,23C421.8,220.7,429.8,229.3,443.1,229.3z M443.1,220.9c-7,0-11-5.3-11-14.6c0-9.2,4-14.6,11-14.6  c6.9,0,11,5.4,11,14.6C454,215.6,450,220.9,443.1,220.9z M470.3,228.4h10v-25.7c0-6.5,3.8-10.6,9.7-10.6c6.1,0,8.9,3.4,8.9,10.1  v26.2h10v-28.6c0-10.6-5.4-16.5-15.2-16.5c-6.6,0-11,3-13.1,8h-0.7v-7.1h-9.7C470.3,184.2,470.3,228.4,470.3,228.4z"></path><g>	<path class="st0" d="M120,152.1c4.7,3.8,6.4,3.5,15.2,2.9l82.9-5c1.8,0,0.3-1.8-0.3-2l-13.8-9.9c-2.6-2-6.2-4.4-12.9-3.8l-80.2,5.9   c-2.9,0.3-3.5,1.8-2.3,2.9L120,152.1z M125,171.4v87.2c0,4.7,2.3,6.4,7.6,6.1l91.1-5.3c5.3-0.3,5.9-3.5,5.9-7.3v-86.6   c0-3.8-1.5-5.9-4.7-5.6l-95.2,5.6C126.2,165.8,125,167.6,125,171.4L125,171.4z M214.9,176.1c0.6,2.6,0,5.3-2.6,5.6l-4.4,0.9v64.4   c-3.8,2-7.3,3.2-10.3,3.2c-4.7,0-5.9-1.5-9.4-5.9l-28.7-45.1v43.6l9.1,2c0,0,0,5.3-7.3,5.3l-20.2,1.2c-0.6-1.2,0-4.1,2-4.7l5.3-1.5   v-57.6l-7.3-0.6c-0.6-2.6,0.9-6.4,5-6.7l21.7-1.5l29.9,45.6V184l-7.6-0.9c-0.6-3.2,1.8-5.6,4.7-5.9L214.9,176.1z M104.2,132.2   l83.5-6.1c10.2-0.9,12.9-0.3,19.3,4.4l26.6,18.7c4.4,3.2,5.9,4.1,5.9,7.6v102.7c0,6.4-2.3,10.2-10.5,10.8l-96.9,5.9   c-6.2,0.3-9.1-0.6-12.3-4.7L100.1,246c-3.5-4.7-5-8.2-5-12.3v-91.3C95.1,137.1,97.5,132.8,104.2,132.2z"></path></g></svg>';

    $allowed_tags = array_merge(
        wp_kses_allowed_html( 'post' ),
        [
            'svg'   => [
                'xmlns'       => true,
                'xmlns:xlink' => true,
                'viewbox'     => true,
                'xml:space'   => true,
                'height'      => true,
                'width'       => true,
                'id'          => true,
                'x'           => true,
                'g'           => true,
                'y'           => true,
                'style'       => true,
            ],
            'style' => [
                'type' => true,
            ],
            'path'  => [
                'd'     => true,
                'class' => true,
            ],
        ],
    );

    return wp_kses( $filtered_icon, $allowed_tags );
}

