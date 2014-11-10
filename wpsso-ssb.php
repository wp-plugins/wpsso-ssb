<?php
/*
 * Plugin Name: WPSSO Social Sharing Buttons (WPSSO SSB)
 * Plugin URI: http://surniaulula.com/extend/plugins/wpsso-ssb/
 * Author: Jean-Sebastien Morisset
 * Author URI: http://surniaulula.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Description: WPSSO extension to provide fast and accurate Social Sharing Buttons - with support for hashtags, shortening, bbPress, and BuddyPress.
 * Requires At Least: 3.0
 * Tested Up To: 4.0
 * Version: 1.0.10.4
 * 
 * Copyright 2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSsb' ) ) {

	class WpssoSsb {

		private $opt_version = 'ssb2';
		private $min_version = '2.6.9.1';
		private $has_min_ver = true;

		public $p;				// class object variables

		public function __construct() {
			// don't continue if the social sharing buttons are disabled
			if ( defined( 'WPSSOSSB_SOCIAL_SHARING_DISABLE' ) &&
				WPSSOSSB_SOCIAL_SHARING_DISABLE )
					return;

			require_once ( dirname( __FILE__ ).'/lib/config.php' );
			WpssoSsbConfig::set_constants( __FILE__ );
			WpssoSsbConfig::require_libs( __FILE__ );

			add_filter( 'wpssossb_installed_version', array( &$this, 'filter_installed_version' ), 10, 1 );
			add_filter( 'wpsso_get_config', array( &$this, 'filter_get_config' ), 30, 1 );

			add_action( 'wpsso_init_options', array( &$this, 'init_options' ), 10 );
			add_action( 'wpsso_init_addon', array( &$this, 'init_addon' ), 10 );
		}

		// this filter is executed at init priority -1
		public function filter_get_config( $cf ) {
			if ( version_compare( $cf['plugin']['wpsso']['version'], $this->min_version, '<' ) ) {
				$this->has_min_ver = false;
				return $cf;
			}
			$cf['opt']['version'] .= $this->opt_version;
			$cf = SucomUtil::array_merge_recursive_distinct( $cf, WpssoSsbConfig::$cf );
			return $cf;
		}

		// this action is executed when WpssoOptions::__construct() is executed (class object is created)
		public function init_options() {
			global $wpsso;
			$this->p =& $wpsso;

			if ( $this->has_min_ver === false )
				return;

			$this->p->is_avail['ssb'] = true;
			$this->p->is_avail['admin']['apikeys'] = true;
			$this->p->is_avail['admin']['sharing'] = true;
			$this->p->is_avail['admin']['style'] = true;
			$this->p->is_avail['util']['shorten'] = ( ! empty( $this->p->options['twitter_shortener'] ) && 
				$this->p->options['twitter_shortener'] !== 'none' ? true : false );
		}

		// this action is executed once all class objects and addons have been created
		public function init_addon() {
			$shortname = WpssoSsbConfig::$cf['plugin']['wpssossb']['short'];

			if ( $this->has_min_ver === false ) {
				$wpsso_version = $this->p->cf['plugin']['wpsso']['version'];
				$this->p->debug->log( $shortname.' requires WPSSO version '.$this->min_version.' or newer ('.$wpsso_version.' installed)' );
				if ( is_admin() )
					$this->p->notice->err( $shortname.' v'.WpssoSsbConfig::$cf['plugin']['wpssossb']['version'].
					' requires WPSSO v'.$this->min_version.' or newer ('.$wpsso_version.' is currently installed).', true );
				return;
			}

			if ( is_admin() && 
				! empty( $this->p->options['plugin_wpssossb_tid'] ) && 
				! $this->p->check->aop( 'wpssossb', false ) ) {
				$this->p->notice->inf( 'An Authentication ID was entered for '.$shortname.', 
				but the Pro version is not installed yet &ndash; 
				don\'t forget to update the '.$shortname.' plugin to install the Pro version.', true );
			}

			WpssoSsbConfig::load_lib( false, 'sharing' );
			$this->p->sharing = new WpssoSsbSharing( $this->p, __FILE__ );
		}

		public function filter_installed_version( $version ) {
			if ( ! $this->p->check->aop( 'wpssossb', false ) )
				$version = '0.'.$version;
			return $version;
		}
	}

        global $wpssossb;
	$wpssossb = new WpssoSsb();
}

?>
