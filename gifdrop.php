<?php
/*
Plugin Name: GifDrop
Plugin URI: http://gifdrop.com/
Description: Creates a repository page for all your animated gifs.
Version: 0.9-beta-1
Author: Mark Jaquith and Andrew Norcross
Author URI: http://gifdrop.com/
Text Domain: gifdrop
Domain Path: /languages
*/

/*
Copyright 2014 Mark Jaquith, Andrew Norcross

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

defined( 'WPINC' ) or die;

// Pull in the plugin classes and initialize
include( dirname( __FILE__ ) . '/classes/plugin.php' );
GifDrop_Plugin::get_instance( __FILE__ );

// Pull in the version checker and initialize
include( dirname( __FILE__ ) . '/classes/version-check.php' );
GifDrop_Version_Check::get_instance();
