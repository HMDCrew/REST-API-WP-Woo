<?php

use \SimpleJwtLoginClient\SimpleJwtLoginClient;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'User_Role_Api' ) ) :

	class User_Role_Api {

		private static $instance;

		private $protocol;
		private $site_url;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof User_Role_Api ) ) {
				self::$instance = new User_Role_Api;
				self::$instance->init();
				self::$instance->hooks();
			}

			return self::$instance;
		}

		public function init() {
			$this->protocol = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https://' : 'http://' );
			$this->site_url = $this->protocol . $_SERVER['HTTP_HOST'];
		}

		/**
		 * Action/filter hooks
		 */
		public function hooks() {
			add_action( 'init', array( $this, 'wpr_user_roles' ), 10 );
			add_action( 'rest_api_init', array( $this, 'wpr_rest_api_user_routes' ), 10 );
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

		/**
		 * It registers a new route for the WordPress REST API, which is accessible at `/wpr-login` and
		 * accepts POST requests
		 *
		 * @param server The server object.
		 */
		public function wpr_rest_api_user_routes( $server ) {

			$server->register_route(
				'rest-api-wordpress',
				'/wpr-login',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_login_auth_callback' ),
				)
			);
		}


		/**
		 * It takes the email and password from the request, sends them to the remote site, and returns the
		 * result
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_login_auth_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$email    = ( ! empty( $params['email'] ) ? preg_replace( '/[^0-9a-zA-Z\.\-\_\@]/i', '', $params['email'] ) : '' );
			$password = ( ! empty( $params['password'] ) ? preg_replace( '/[^0-9a-zA-Z\.\-\_\@\:\;\#\+\*\[\]\!\%\&\(\)\=]/i', '', $params['password'] ) : '' );

			$simple_jwt_login = new SimpleJwtLoginClient( $this->site_url, '/simple-jwt-login/v1' );
			$result           = $simple_jwt_login->authenticate( $email, $password );

			$user = get_user_by( 'email', $email );

			if ( isset( $result['success'] ) && $result['success'] ) {
				$result['data']['id']    = $user->ID;
				$result['data']['email'] = $email;
			}

			wp_send_json( $result );
		}
	}

endif;

User_Role_Api::instance();
