<?php
/*
Plugin Name: Toolbar Login Button
Plugin URI: https://wordpress.org/plugins/toolbar-login-button/
Description: Show Wordpress toolbar (formerly admin bar) with a login button on front end for remembered (previously logged in) browsers. Miscellaneous show/hide/remember toolbar options.
Version: 1.0.0
Author: Volkan Kucukcakar
Author URI: http://volkan.xyz/
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


/*
* Stop if PHP < 5.3.0. For namespace, anonymous functions etc... (PHP < 5.3.0 is really old...)
* This is just in case to prevent some php errors (e.g. upon moving to lower php versions), other requirements may checked later.
* Stop if Wordpress < 4.0. Unfortunately testing very old versions means intensive workload to individual developers.
*/
defined( 'ABSPATH' ) || exit;
global $wp_version;
if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
    add_action( 'admin_notices', 'toolbar_login_button_php_version_notice' );
}elseif ( version_compare( $wp_version, '4.0', '<' ) ) {
    add_action( 'admin_notices', 'toolbar_login_button_wp_version_notice' );
} else{
    require_once plugin_dir_path( __FILE__ ) . 'toolbar-login-button.php';
}
function toolbar_login_button_php_version_notice() {
    if ( current_user_can( 'activate_plugins' ) ) {
        echo '<div class="error"><p><strong>Toolbar Login Button</strong> plugin requires PHP 5.3.0 or later to function properly. Please update <strong>PHP</strong>. Sorry about that.</p></div>';
    }
}
function toolbar_login_button_wp_version_notice() {
    if ( current_user_can( 'activate_plugins' ) ) {
        echo '<div class="error"><p><strong>Toolbar Login Button</strong> plugin requires Wordpress 4.0 or later to function properly. Please update <strong>Wordpress</strong>. Sorry about that.</p></div>';
    }
}
