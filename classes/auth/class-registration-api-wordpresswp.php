<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Registration_Api_WordpressWP' ) ) :

	class Registration_Api_WordpressWP {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Registration_Api_WordpressWP ) ) {
				self::$instance = new Registration_Api_WordpressWP;
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * Action/filter hooks
		 */
		public function hooks() {
			add_action( 'init', array( $this, 'wpr_user_roles' ), 10 );
		}

		/**
		 * It creates a new user role called "pending" and gives it the ability to read
		 */
		public function wpr_user_roles() {
			add_role(
				'pending',
				'Pending approval user',
				array(
					'read'    => true,
					'level_0' => true,
				)
			);
		}
	}

endif;

Registration_Api_WordpressWP::instance();
