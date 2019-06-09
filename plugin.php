<?php
/*
Plugin Name: Toolbar Login Button
Plugin URI: https://wordpress.org/plugins/toolbar-login-button/
Description: Show Wordpress toolbar (formerly admin bar) with a login button on front end for remembered (previously logged in) browsers. Miscellaneous show/hide/remember toolbar options.
Version: 1.1.0
Author: Volkan Kucukcakar
Author URI: https://volkan.xyz/
Text Domain: toolbar-login-button
Domain Path: /languages
*/

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
* Plugin main class
*/
final class toolbar_login_button {

    const VERSION = '1.1.0';

    private static $initialised = false;

    /*
    * Initializer
    */
    static function init() {
        if ( !self::$initialised ) {
            self::$initialised = true;
            //Load translations
            add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );
            //Cookie stuff
            add_action( 'wp_loaded', array( __CLASS__, 'wp_loaded_callback' ) );
        }//if
    }// function

    /*
    * Load translations
    */
    static function load_plugin_textdomain() {
        // Use bundled languages
        //load_plugin_textdomain( 'toolbar-login-button', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');// No bundled languages currently
        // Use language packs exclusively
        load_plugin_textdomain( 'toolbar-login-button' );
    }// function

    /*
    * Cookie stuff
    * "wp_loaded" hook is a good starting point as more functions are available. !
    * (Also note that "init" hook cannot be used internally here as it occurs after "init" hook)
    */
    static function wp_loaded_callback() {
        // Set cookie if user logged in
        if ( is_user_logged_in() ) {
            // Set cookie if capability is enough (matching user role with hardcoded role slugs is not a good idea)
            $capabilities = array( 'subscriber'=>'read', 'contributor'=>'edit_posts', 'author'=>'publish_posts', 'editor'=>'edit_others_posts', 'administrator'=>'install_plugins' );
            $remember_user_role = tlb_settings::get_option( 'tlb_remember_user_role' );
            if ( isset( $capabilities[$remember_user_role] ) && current_user_can( $capabilities[$remember_user_role] ) ) {
                setcookie( 'toolbar_login_button', 'toolbar_login_button', time() + tlb_settings::get_option( 'tlb_cookie_expire' ), '/' );
            }
            // Hide toolbar if user choose to hide
            if ( '0' == tlb_settings::get_option( 'tlb_show_logged_in' ) ) {
                add_filter( 'show_admin_bar', '__return_false', PHP_INT_MAX );
            }
        // Remove cookie if user not logged in and clicked hide
        } elseif ( isset( $_GET['toolbar_login_button_action'] ) && $_GET['toolbar_login_button_action'] == 'hide' ) {
            // Delete cookie if nonce verified
            if ( wp_verify_nonce( $_GET['toolbar_login_button_nonce'], 'hide' ) ) {
                setcookie( 'toolbar_login_button', '', time() - 86400, '/' );
            }
            $url = remove_query_arg( array( 'toolbar_login_button_action', 'toolbar_login_button_nonce' ) );
            wp_redirect( $url );
            exit;
        // Display toolbar if user not logged in and cookie is present
        } elseif ( isset( $_COOKIE['toolbar_login_button'] ) && '1' == tlb_settings::get_option( 'tlb_show_logged_out' ) ) {
            // Add hooks to modify toolbar
            add_action( 'admin_bar_menu', array( __CLASS__, 'menu_add_login' ), 11 );
            add_action( 'admin_bar_menu', array( __CLASS__, 'menu_add_hide' ), 1 );
            add_action( 'wp_enqueue_scripts', array( __CLASS__, 'add_menu_style' ),11 );
            // Force show toolbar on front end even if user is not logged in ! (Hooks really late)
            add_filter( 'show_admin_bar', '__return_true', PHP_INT_MAX );
        }
    }// function


    /*
    * Add login button to toolbar
    */
    static function menu_add_login( $wp_admin_bar ) {
        $href = ( 'admin' == tlb_settings::get_option( 'tlb_redirect_after_login' ) ) ? wp_login_url( get_admin_url() ) : wp_login_url( $_SERVER['REQUEST_URI'] );// do not use get_permalink()
        $args = array(
            'id'    => 'tl_login',
            'title' => '<span class="dashicons dashicons-admin-users"></span> ' . esc_html__( 'Login','toolbar-login-button' ),
            'href'  => $href,
            'parent'=> 'top-secondary',
            'meta'  => array(
                'class' => 'toolbar-login-button',
                'title' => esc_attr__( 'Login','toolbar-login-button' ),
            )
        );
        $wp_admin_bar->add_node( $args );
    }// function

    /*
    * Add hide button to toolbar
    */
    static function menu_add_hide( $wp_admin_bar ) {
        // Url parameter used instead of javascript to help support servers with forced httponly cookies...
        $href = add_query_arg( 'toolbar_login_button_action', 'hide', $_SERVER['REQUEST_URI'] );
        $href = wp_nonce_url( $href, 'hide', 'toolbar_login_button_nonce' );
        $wp_admin_bar->add_node( array(
            'id'    => 'tl_hide',
            'title' => '<span class="dashicons dashicons-dismiss"></span>',
            'href'  => $href,
            'parent'=> 'top-secondary',
            'meta'  => array(
                'class' => 'toolbar-login-button',
                'title' => esc_attr__( 'Forget Browser (Hide)','toolbar-login-button' ),
            )
        ) );
    }//function


    /*
    * Add menu style
    */
    static function add_menu_style() {
        $custom_css = "
            #wpadminbar #wp-admin-bar-top-secondary .toolbar-login-button a{
                color: #b4b9be;
                color: rgba(240,245,250,0.7);
            }
            #wpadminbar #wp-admin-bar-top-secondary .toolbar-login-button a:hover{
                color: #00b9eb;
                background: #32373c;
            }
            #wpadminbar #wp-admin-bar-top-secondary .toolbar-login-button a span{
                font:400 20px/1 dashicons; position:relative; top:6px;
            }
            /* Do not hide on small screens */
            @media screen and ( max-width: 782px ) {
                #wpadminbar #wp-toolbar > ul > li.toolbar-login-button {
                    display: block;
                    font:400 32px/1 dashicons;
                }
                #wpadminbar #wp-admin-bar-top-secondary .toolbar-login-button a{
                    font-weight: bold;
                    padding:0 4px 0 4px;
                }
                #wpadminbar #wp-admin-bar-top-secondary .toolbar-login-button a span{
                    font:400 32px/1 dashicons; position:relative; top:6px;
                    font-weight: bold;
                }
            }
        ";
        // Add inline style to already enqueued "admin-bar" or "dashicons" css !
        wp_add_inline_style( 'admin-bar', $custom_css );
    }// function

    /*
    * Get plugin basename
    */
    static function get_plugin_basename() {
		return plugin_basename( __FILE__ );
    }// function

}// class

// Prevent direct call
if ( defined( 'ABSPATH' ) ) {
    toolbar_login_button::init();
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings.php';
    tlb_settings::init();
}
