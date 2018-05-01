<?php
/*
* Toolbar Login Button
* Show Wordpress toolbar (formerly admin bar) with a login button on front end for remembered (previously logged in) browsers. Miscellaneous show/hide/remember toolbar options.
* Copyright (c) 2017 Volkan Kucukcakar
*
* This file is part of Toolbar Login Button.
*
* Toolbar Login Button is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 2 of the License, or
* (at your option) any later version.
*
* Toolbar Login Button is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* This copyright notice and license must be retained in all files and derivative works.
*/
namespace toolbar_login_button;

/*
* Plugin settings
*/
final class tlb_settings {

    public static $default_options = array(
        'tlb_show_logged_in'        => '1',
        'tlb_show_logged_out'       => '1',
        'tlb_remember_user_role'    => 'editor',
        'tlb_redirect_after_login'  => 'current',
        'tlb_cookie_expire'         => '31536000',
        'tlb_uninstall_delete'      => '0',
    );

    private static $initialised = false;

    const OPTIONS_SLUG = 'toolbar_login_button_options';
    const REVIEW_URL = 'https://wordpress.org/plugins/toolbar-login-button/#reviews';
    const SUPPORT_URL = 'https://wordpress.org/support/plugin/toolbar-login-button';

    /*
    * Initializer
    */
    static function init() {
        if ( !self::$initialised ) {
            self::$initialised = true;
            // Handle plugin uninstall ($file must point to the plugin main file)
            register_deactivation_hook( dirname( dirname( __FILE__ ) ) . '/plugin.php', array( __CLASS__, 'uninstall' ) );
            // Create options menu
            add_action( 'admin_menu', array( __CLASS__, 'create_plugin_options_menu' ) );
            // Register settings
            add_action( 'admin_init', array( __CLASS__, 'register_plugin_settings' ) );
            // Add admin css
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'custom_admin_style' ) );
        }// if
    }// function

    /*
    * Get option or plugin default
    */
    static function get_option( $option ) {
        return get_option( $option, self::$default_options[$option] );
    }// function

    /*
    * Plugin options menu
    */
    static function create_plugin_options_menu() {
        $admin_page = add_options_page( 'Toolbar Login Button', 'Toolbar Login Button', 'manage_options', self::OPTIONS_SLUG, array( __CLASS__, 'display_plugin_options' ) );
        // Add a help tab when this plugin's options page gets loaded
        add_action( 'load-' . $admin_page, array( __CLASS__, 'add_help_tab' ) );
    }// function

    /*
    * Add plugin help tab
    */
    static function add_help_tab() {
        $screen = get_current_screen();
        // Plugin help tab content
        $screen->add_help_tab( array(
            'id'        => 'toolbar_login_button_help_tab',
            'title'     => 'Toolbar Login Button',
            'content'   => '<p>' . __( 'Toolbar Login Button plugin shows Wordpress toolbar (formerly admin bar) with a login button on front end for remembered (previously logged in) browsers.', 'toolbar-login-button') . '</p>' .
                           '<p>' . __( 'Miscellaneous show/hide/remember toolbar options are listed below.', 'toolbar-login-button' ) . '</p>' .
                           '<p>' . __( 'Uninstall feature completely deletes plugin settings from database.', 'toolbar-login-button' ) . '</p>'
        ) );
        // Plugin help sidebar content
         $screen->set_help_sidebar(
            '<p><a href="' . esc_url( self::REVIEW_URL ) . '">' . __( 'Rate', 'toolbar-login-button' ) . '</a></p>' .
            '<p><a href="' . esc_url( self::REVIEW_URL ) . '">' . __( 'Leave feedback', 'toolbar-login-button' ) . '</a></p>' .
            '<p><a href="' . esc_url( self::SUPPORT_URL ) . '">' . __( 'Support', 'toolbar-login-button' ) . '</a></p>'
         );
    }// function

    /*
    * Register plugin settings
    */
    static function register_plugin_settings() {
        // Register plugin settings
        register_setting( 'toolbar_login_button_settings', 'tlb_show_logged_in', 'intval' );
        register_setting( 'toolbar_login_button_settings', 'tlb_show_logged_out', 'intval' );
        register_setting( 'toolbar_login_button_settings', 'tlb_remember_user_role' );
        register_setting( 'toolbar_login_button_settings', 'tlb_redirect_after_login' );
        register_setting( 'toolbar_login_button_settings', 'tlb_cookie_expire', function( $value ) {
            return ( filter_var( $value, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 2592000, 'max_range' => 31536000*10 ) ) ) ) ? $value : self::$default_options['tlb_cookie_expire'];
        } );
        register_setting( 'toolbar_login_button_unistall', 'tlb_uninstall_delete', 'intval' );
    }// function

    /*
    * Plugin uninstall
    */
    static function uninstall() {
        // Delete plugin options from database
        if ( '1' == self::get_option( 'tlb_uninstall_delete' ) ) {
            foreach ( array_keys( self::$default_options ) as $option_name ) {
                delete_option( $option_name );
            }
        }
    }// function

    /*
    * Custom admin style
    */
    static function custom_admin_style( $hook ) {
        // Add custom css only into the plugin's options page
        if( $hook == 'settings_page_' . self::OPTIONS_SLUG ) {
            wp_enqueue_style( 'toolbar_login_button_admin_css', plugins_url( 'admin/css/admin.css', dirname( __FILE__ ) ) );
        }
    }// function

    /*
    * Echo the word "default" translated if option value is default
    */
    private static function e_default( $option, $value ) {
        if ( $value == tlb_settings::$default_options[$option] ) {
            echo ' (' . esc_html__( 'default', 'toolbar-login-button' ) . ')';
        }
    }// function

    /*
    * Echo "like" string with links. (For use with this plugin's options page)
    */
    private static function e_like() {
		$text = __( '<p>If you like this plugin, please <a href="%s">rate</a> or <a href="%s">leave feedback</a>.</p> <p>Bug reports are also welcomed at the <a href="%s">support</a> page.</p>', 'toolbar-login-button' );
		$formatted_text = ( preg_match( '~(<a\s+href=[\'"]%s[\'"]>[^<]+</a>.*?){3}~', $text ) ) ? sprintf( $text, esc_url( self::REVIEW_URL ), esc_url( self::REVIEW_URL ), esc_url( self::SUPPORT_URL ) ) : $text;
        echo $formatted_text;
    }// function

    /*
    * Display sidebar content
    */
    private static function display_sidebar_content() {
        ?>
        <!--sidebar content-->
        <div id="postbox-container-1" class="postbox-container">
            <div class="meta-box-sortables">
                <div class="postbox">
                    <h2 class="hndle"><span class="dashicons dashicons-heart tlb-like"></span> <span><?php esc_html_e('Plugin support', 'toolbar-login-button'); ?></span></h2>
                    <div class="inside">
                        <p class="tlb-brand">Toolbar Login Button v<?php echo toolbar_login_button::VERSION; ?></p>
                        <div class="tlb-like"></div>
                        <p><?php self::e_like(); ?></p>
                    </div><!--.inside-->
                </div><!--.postbox-->
            </div><!--.meta-box-sortables-->
        </div><!--#postbox-container-1 .postbox-container-->
        <?php
    }// function

    /*
    * Diplay plugin options page
    */
    static function display_plugin_options() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        // Display tabs
        $tabs = array(
            'settings'	=> '<span class="dashicons dashicons-admin-generic"></span> ' . esc_html__( 'Settings', 'toolbar-login-button' ),
            'uninstall'	=> '<span class="dashicons dashicons-trash"></span> ' . esc_html__( 'Uninstall', 'toolbar-login-button' ),
            'about'		=> '<span class="dashicons dashicons-info"></span> ' . esc_html__( 'About', 'cat-avatars' ),
        );
        ?>
        <div class="wrap">
            <h1>Toolbar Login Button</h1>
            <h2 class="nav-tab-wrapper">
                <?php
                $current_tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : 'settings';
                foreach( $tabs as $tab => $name ) {
                    $class = ( $tab == $current_tab ) ? ' nav-tab-active' : '';
                    echo '<a class="nav-tab' . $class . '" href="?page=' . self::OPTIONS_SLUG . '&tab=' . $tab . '">' . $name . '</a>' . PHP_EOL;
                }
                ?>
            </h2>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <?php
                    // Display the selected tab content
                    switch ( $current_tab ){ 
                        // Settings tab
                        case 'settings':
                            ?>
                            <form method="post" action="options.php">
                                <?php settings_fields( 'toolbar_login_button_settings' ); ?>
                                <!--main content-->
                                <div id="post-body-content">
                                    <div class="meta-box-sortables ui-sortable">
                                        <!--settings group-->
                                        <div class="postbox">
                                            <h2 class="hndle"><span><?php esc_html_e( 'Logged in users', 'toolbar-login-button' ); ?></span></h2>
                                            <div class="inside">
                                                <table class="form-table">
                                                    <tr valign="top">
                                                        <th scope="row"><?php esc_html_e( 'Show front end toolbar', 'toolbar-login-button' ); ?></th>
                                                        <td>
                                                            <select name="tlb_show_logged_in" title="">
                                                                <option title="" value="1"<?php $tlb_show_logged_in = tlb_settings::get_option( 'tlb_show_logged_in' ); selected( '1', $tlb_show_logged_in ); ?>><?php esc_html_e( 'Show', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_show_logged_in', '1' ); ?></option>
                                                                <option title="" value="0"<?php selected( '0', $tlb_show_logged_in ); ?>><?php esc_html_e( 'Hide', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_show_logged_in', '0' ); ?></option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div><!--.inside-->
                                        </div><!--.postbox-->

                                        <!--settings group-->
                                        <div class="postbox">
                                            <h2 class="hndle"><span><?php esc_html_e( 'Logged out users', 'toolbar-login-button' ); ?></span></h2>
                                            <div class="inside">
                                                <table class="form-table">
                                                    <tr valign="top">
                                                        <th scope="row"><?php esc_html_e( 'Show front end toolbar', 'toolbar-login-button' ); ?></th>
                                                        <td>
                                                            <select name="tlb_show_logged_out" title="">
                                                                <option title="" value="1"<?php $tlb_show_logged_out = tlb_settings::get_option( 'tlb_show_logged_out' ); selected( '1', $tlb_show_logged_out ); ?>><?php esc_html_e( 'Show if remembered', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_show_logged_out', '1' ); ?></option>
                                                                <option title="" value="0"<?php selected( '0', $tlb_show_logged_out ); ?>><?php esc_html_e( 'Hide', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_show_logged_out', '0' ); ?></option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr valign="top">
                                                        <th scope="row"><?php esc_html_e( 'Remember browser for', 'toolbar-login-button' ); ?></th>
                                                        <td>
                                                            <select name="tlb_cookie_expire" title="">
                                                                <option title="" value="2592000"<?php $tlb_cookie_expire = tlb_settings::get_option( 'tlb_cookie_expire' ); selected( '2592000', $tlb_cookie_expire ); ?>><?php printf( esc_html__( '%s month', 'toolbar-login-button' ), '1' ); ?><?php self::e_default( 'tlb_cookie_expire', '2592000' ); ?></option>
                                                                <option title="" value="15552000"<?php selected( '15552000', $tlb_cookie_expire ); ?>><?php printf( esc_html__( '%s months', 'toolbar-login-button' ), '6' ); ?><?php self::e_default( 'tlb_cookie_expire', '15552000' ); ?></option>
                                                                <option title="" value="31536000"<?php selected( '31536000', $tlb_cookie_expire ); ?>><?php printf( esc_html__( '%s months', 'toolbar-login-button' ), '12' ); ?><?php self::e_default( 'tlb_cookie_expire', '31536000' ); ?></option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr valign="top">
                                                        <th scope="row"><?php esc_html_e( 'Remember user', 'toolbar-login-button' ); ?></th>
                                                        <td>
                                                            <select name="tlb_remember_user_role" title="">
                                                                <option title="" value="subscriber"<?php $tlb_remember_user_role = tlb_settings::get_option( 'tlb_remember_user_role' ); selected( 'subscriber', $tlb_remember_user_role ); ?>><?php esc_html_e( 'Subscriber', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_remember_user_role', 'subscriber' ); ?></option>
                                                                <option title="" value="contributor"<?php selected( 'contributor', $tlb_remember_user_role ); ?>><?php esc_html_e( 'Contributor', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_remember_user_role', 'contributor' ); ?></option>
                                                                <option title="" value="author"<?php selected( 'author', $tlb_remember_user_role ); ?>><?php esc_html_e( 'Author', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_remember_user_role', 'author' ); ?></option>
                                                                <option title="" value="editor"<?php selected( 'editor', $tlb_remember_user_role ); ?>><?php esc_html_e( 'Editor', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_remember_user_role', 'editor' ); ?></option>
                                                                <option title="" value="administrator"<?php selected( 'administrator', $tlb_remember_user_role ); ?>><?php esc_html_e( 'Administrator', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_remember_user_role', 'administrator' ); ?></option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr valign="top">
                                                        <th scope="row"><?php esc_html_e( 'Redirect after login', 'toolbar-login-button' ); ?></th>
                                                        <td>
                                                            <select name="tlb_redirect_after_login" title="">
                                                                <option title="" value="current"<?php $tlb_redirect_after_login = tlb_settings::get_option( 'tlb_redirect_after_login' ); selected( 'current', $tlb_redirect_after_login ); ?>><?php esc_html_e( 'Current page', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_redirect_after_login', 'current' ); ?></option>
                                                                <option title="" value="admin"<?php selected( 'admin', $tlb_redirect_after_login ); ?>><?php esc_html_e( 'Admin panel', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_redirect_after_login', 'admin' ); ?></option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div><!--.inside-->
                                        </div><!--.postbox-->

                                    </div><!--.meta-box-sortables .ui-sortable-->
                                </div><!--#post-body-content-->
                                <?php self::display_sidebar_content(); ?>
                                <?php submit_button(); ?>
                            </form>
                            <?php
                            break;
                        // Uninstall tab
                        case 'uninstall':
                            ?>
                            <form method="post" action="options.php">
                                <?php settings_fields( 'toolbar_login_button_unistall' ); ?>
                                <!--main content-->
                                <div id="post-body-content">
                                    <div class="meta-box-sortables ui-sortable">
                                        <!--settings group-->
                                        <div class="postbox">
                                            <h2 class="hndle"><span><?php esc_html_e( 'Uninstall plugin', 'toolbar-login-button' ); ?></span></h2>
                                            <div class="inside">
                                                <table class="form-table">
                                                    <tr valign="top">
                                                        <th scope="row"><?php esc_html_e( 'On plugin deactivation', 'toolbar-login-button' ); ?></th>
                                                        <td>
                                                            <select name="tlb_uninstall_delete" title="">
                                                                <option title="" value="1"<?php $tlb_uninstall_delete = tlb_settings::get_option( 'tlb_uninstall_delete' ); selected( '1', $tlb_uninstall_delete ); ?>><?php esc_html_e( 'Delete plugin data', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_uninstall_delete', '1' ); ?></option>
                                                                <option title="" value="0"<?php selected( '0', $tlb_uninstall_delete ); ?>><?php esc_html_e( 'Keep plugin data', 'toolbar-login-button' ); ?><?php self::e_default( 'tlb_uninstall_delete', '0' ); ?></option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div><!--.inside-->
                                        </div><!--.postbox-->

                                    </div><!--.meta-box-sortables .ui-sortable-->
                                </div><!--#post-body-content-->
                                <?php self::display_sidebar_content(); ?>
                                <?php submit_button(); ?>
                            </form>
                            <?php
                            break;
                        // About tab
                        case 'about':
                            ?>
							<!--main content-->
							<div id="post-body-content">
								<div class="meta-box-sortables ui-sortable">
									<!--settings group-->
									<div class="postbox">
										<h2 class="hndle"><span><?php esc_html_e( 'About plugin', 'cat-avatars' ); ?></span></h2>
										<div class="inside">
											<p>Toolbar Login Button</p>
											<p>Copyright (c) 2017 Volkan Kucukcakar</p>
											<p><?php _e( 'Toolbar Login Button plugin shows Wordpress toolbar (formerly admin bar) with a login button on front end for remembered (previously logged in) browsers.', 'cat-avatars' ); ?></p>
										</div><!--.inside-->
									</div><!--.postbox-->

								</div><!--.meta-box-sortables .ui-sortable-->
							</div><!--#post-body-content-->
							<?php self::display_sidebar_content(); ?>
                            <?php
                            break;
                    }// switch
                    ?>
                </div><!--#post-body .metabox-holder .columns-2-->
                <br class="clear">
            </div><!--#poststuff-->
        </div><!--.wrap-->
        <?php
    }// function

}// class
