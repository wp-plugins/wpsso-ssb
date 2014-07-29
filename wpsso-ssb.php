<?php
/*
 * Plugin Name: WPSSO Social Sharing Buttons (SSB)
 * Plugin URI: http://surniaulula.com/extend/plugins/wpsso-ssb/
 * Author: Jean-Sebastien Morisset
 * Author URI: http://surniaulula.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Description: Social Sharing Buttons (SSB) extension for the WordPress Social Sharing Optimization (WPSSO) plugin.
 * Requires At Least: 3.0
 * Tested Up To: 3.9.1
 * Version: 1.0
 * 
 * Copyright 2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSsb' ) ) {

	class WpssoSsb {

		private $opt_version = 'ssb1';
		private $min_version = '2.6.1';
		private $has_min_ver = true;

		public $p;		// class object variables
		public $cf = array();	// config array defined in construct method

		public function __construct() {
			// don't continue if the social sharing buttons are disabled
			if ( defined( 'WPSSOSSB_SOCIAL_SHARING_DISABLE' ) &&
				WPSSOSSB_SOCIAL_SHARING_DISABLE )
					return;

			require_once ( dirname( __FILE__ ).'/lib/config.php' );
			WpssoSsbConfig::set_constants( __FILE__ );
			WpssoSsbConfig::require_libs( __FILE__ );

			$this->add_filters();	// hooks filter to extends the wpsso config

			add_action( 'wpsso_init_options', array( &$this, 'init_options' ), 10 );
			add_action( 'wpsso_init_addon', array( &$this, 'init_addon' ), 10 );
		}

		// runs at class construct
		private function add_filters() {
			$filters_to_add = array( 'get_config' => 1 );
			foreach ( $filters_to_add as $filter_name => $filter_args )
				add_filter( 'wpsso_'.$filter_name, array( &$this, 'filter_'.$filter_name ), 10, $filter_args );
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
			if ( $this->has_min_ver === false ) {
				$this->p->debug->log( WpssoSsbConfig::$cf['plugin']['wpssossb']['short'].
					' requires WPSSO version '.$this->min_version.
					' or newer ('.$wpsso_version.' installed)' );
				if ( is_admin() )
					$this->p->notice->err( WpssoSsbConfig::$cf['plugin']['wpssossb']['short'].
						' v'.WpssoSsbConfig::$cf['plugin']['wpssossb']['version'].
						' requires WPSSO v'.$this->min_version.
						' or newer (version '.$this->p->cf['plugin']['wpsso']['version'].
						' is currently installed).', true );
				return;
			}
			WpssoSsbConfig::load_lib( false, 'sharing' );
			$this->p->sharing = new WpssoSsbSharing( $this->p, __FILE__ );
		}
	}

        global $wpssossb;
	$wpssossb = new WpssoSsb();
}

?>
