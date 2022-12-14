<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'RegistrationApiWordpressWP' ) ) :

	class RegistrationApiWordpressWP {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RegistrationApiWordpressWP ) ) {
				self::$instance = new RegistrationApiWordpressWP;
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * Action/filter hooks
		 */
		public function hooks() {
			add_action( 'init', array( $this, 'wpr_user_roles' ), 10 );
			add_action( 'rest_api_init', array( $this, 'wpr_rest_api_auth_routes' ), 10 );
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
		 * definition new Api routes
		 *
		 * @param server The server object.
		 */
		public function wpr_rest_api_auth_routes( $server ) {
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-register',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_register_callback' ),
				)
			);
		}

		/**
		 * It creates a new user in the WordPress database.
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_register_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$name       = isset( $params['name'] ) ? preg_replace( '/[^a-zA-Z0-9\s\.\_\-]/', '', $params['name'] ) : '';
			$user       = isset( $params['username'] ) ? preg_replace( '/[^a-zA-Z0-9\.\_\-]/', '', $params['username'] ) : '';
			$email      = isset( $params['email'] ) ? preg_replace( '/[^a-zA-Z0-9\_\-\@\.]/', '', $params['email'] ) : '';
			$pass       = isset( $params['password'] ) ? preg_replace( '/[^a-zA-Z0-9\_\$\!\#\,\s\-]/', '', $params['password'] ) : '';
			$chack_pass = isset( $params['repeat_password'] ) ? preg_replace( '/[^a-zA-Z0-9\_\$\!\#\,\s\-]/', '', $params['repeat_password'] ) : '';
			$token      = isset( $params['plugin_token'] ) ? preg_replace( '/[^a-zA-Z0-9\_\$\!\#\,\s\-]/', '', $params['plugin_token'] ) : '';

			if ( REST_API_WORDPRESS_PLUGIN_TOKEN === $token ) {

				if (
					( ! empty( $user ) && ! empty( $email ) && ! empty( $pass ) && ! empty( $chack_pass ) ) &&
					( ! username_exists( $user ) && ! email_exists( $email ) && md5( $pass ) === md5( $chack_pass ) )
				) {

					$user_id = wp_create_user( $user, $pass, $email );
					$user    = new WP_User( $user_id );

					$user->set_role( 'pending' );

					if ( ! empty( $name ) ) {
						wp_update_user(
							array(
								'ID'           => $user->ID,
								'display_name' => $name,
							)
						);
					}

					/**
					 * Add token to user on response
					 *
					 * Plugin: JWT Authentication for WP REST API
					 * @link https://wordpress.org/plugins/jwt-authentication-for-wp-rest-api/
					 */
					if ( class_exists( 'Jwt_Auth_Public' ) ) {

						$request->set_param( 'username', $user->user_login );

						$test  = new Jwt_Auth_Public( 'jwt-auth', '1.1.0' );
						$token = $test->generate_token( $request );

						if ( ! is_a( $token, 'WP_Error' ) && ! empty( $token['token'] ) ) {
							$user->token = $token['token'];
						}
					}

					wp_send_json(
						array(
							'status'  => ( ! empty( $user ) ? 'success' : 'error' ),
							'message' => ( ! empty( $user ) ? $user : 'wordpress registration error' ),
						)
					);
				}

				$error_msg = array();
				if ( empty( $pass ) || empty( $chack_pass ) || md5( $pass ) !== md5( $chack_pass ) ) {
					$error_msg[] = 'please verify passwords';
				}
				if ( ! empty( $user ) && username_exists( $user ) ) {
					$error_msg[] = 'username already used';
				}
				if ( empty( $user ) ) {
					$error_msg[] = 'missing username';
				}
				if ( ! empty( $email ) && email_exists( $email ) ) {
					$error_msg[] = 'email already used';
				}
				if ( empty( $email ) ) {
					$error_msg[] = 'missing email';
				}

				if ( ! empty( $error_msg ) ) {
					wp_send_json(
						array(
							'status'  => 'error',
							'message' => implode( ', ', $error_msg ),
						),
					);
				}
			} else {
				wp_send_json(
					array(
						'status'  => 'error',
						'message' => 'Uncouth registration',
					),
				);
			}
		}
	}

endif;

RegistrationApiWordpressWP::instance();
