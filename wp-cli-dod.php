<?php
/**
 * Plugin Name: WP CLI DOD
 *
 * 1. Run `wp dod check`		Info.
 *
 * @since 1.0.0
 *
 * @package Dod
 */

if ( ! class_exists( 'DOD' ) && class_exists( 'WP_CLI_Command' ) ) :

	/**
	 * Dod
	 */
	class DOD extends WP_CLI_Command {

		function get_used_functions( $uses = array(), $all_used_functions ) {
			if( $uses ) {
				foreach ( $uses as $uses_key => $functions) {

					/**
					 * FUNCTIONS
					 */
					if( 'functions' === $uses_key ) {
						foreach ($functions as $uses_function_key => $uses_function) {
							if( ! in_array($uses_function['name'], $all_used_functions) ) {
								// WP_CLI::line( $uses_function['name'] );
								$all_used_functions[] = $uses_function['name'];								

							}
							// if( 'bsf_extension_nag' == $uses_function['name'] ) {
							// 	WP_CLI::error($uses_function['name']);
							// }
						}
					} else {
						// WP_CLI::line( 'Not have used functions().' );
					}
				}
			}

			return $all_used_functions;
		}

		function get_used_methods( $uses = array(), $all_used_methods ) {
			if( $uses ) {
				foreach ( $uses as $uses_key => $functions) {

					/**
					 * METHODS
					 */
					if( 'methods' === $uses_key ) {
						foreach ($functions as $uses_method_key => $uses_method) {

							// $all_used_methods[ $uses_method['class'] ][] = $uses_method['name'];
							$class_name = str_replace("\\", '', $uses_method['class']);
							if( ! isset( $all_used_methods[ $class_name ] ) ) {
								$all_used_methods[ $class_name ][] = $uses_method['name'];
							} else if( ! in_array( $uses_method['name'], $all_used_methods[ $class_name ] ) ) {
								// WP_CLI::line( $uses_method['name'] );
								$all_used_methods[ $class_name ][] = $uses_method['name'];
							} else {
							}

							// if( 'bsf_extension_nag' == $uses_method['name'] ) {
							// 	WP_CLI::error($uses_method['name']);
							// }
						}

					} else {
						// WP_CLI::line( 'Not have used CLASSS().' );
					}
				}
			}

			return $all_used_methods;
		}

		/**
		 * Info
		 *
		 * @param  array $args       Arguments.
		 * @param  array $assoc_args Associated Arguments.
		 * @return void
		 */
		public function check( $args, $assoc_args ) {

			// Show the unused functions or methods.
			$show_functions = ( isset( $assoc_args['show-functions'] ) ) ? true : false;
			$show_methods = ( isset( $assoc_args['show-methods'] ) ) ? true : false;

			WP_CLI::runcommand( 'parser export . all-details.json' );
			
			$files = file_get_contents( ASTRA_PORTFOLIO_DIR . 'all-details.json' );

			$all_functions = array();
			$all_methods   = array();

			$all_used_functions = array();
			$all_used_methods   = array();

			$files = json_decode( $files, true );
			// // WP_CLI::line( print_r( $files ) );

			foreach ($files as $key => $file) {
				
				// SKIP /node_modules/ direcotry.
				if (strpos($file['path'], 'node_modules') === false) {

					/**
					 * FUNCTIONS
					 */
					if( isset( $file['functions'] ) ) {
						foreach ($file['functions'] as $function_key => $function) {
							// WP_CLI::line( $function['name'] );
							$all_functions[] = $function['name'];
	

							/**
							 * Used functions and methods into the current function()
							 */
							if( isset( $function['uses'] ) ) {
								$all_used_functions = $this->get_used_functions( $function['uses'], $all_used_functions );
								$all_used_methods   = $this->get_used_methods( $function['uses'], $all_used_methods );
							}
						}


					} else {
						// WP_CLI::line( 'Not have functions().' );
					}


					/**
					 * METHODS
					 */
					if( isset( $file['classes'] ) ) {
						
						foreach ($file['classes'] as $class_key => $class) {

							if( isset( $class['methods'] ) ) {
								// WP_CLI::line( 'Class "' . $class['name'] . '" have ' . count( $class['methods'] ) . ' methods.' );
								foreach ($class['methods'] as $method_key => $method) {
									// WP_CLI::line( $method['name'] );
									$all_methods[ $class['name'] ][] = $method['name'];


									/**
									 * Used functions and methods into the current method()
									 */
									if( isset( $method['uses'] ) ) {
										$all_used_functions = $this->get_used_functions( $method['uses'], $all_used_functions );
										$all_used_methods   = $this->get_used_methods( $method['uses'], $all_used_methods );
									}
								}
							} else {
								// WP_CLI::line( 'Class "' . $class['name'] . '" have NO methods.' );
							}
						}

					} else {
						// WP_CLI::line( 'Not have CLASSS().' );
					}


					/**
					 * Used Methods & Functions in Current File
					 */
					if( isset( $file['uses'] ) ) {
						$all_used_functions = $this->get_used_functions( $file['uses'], $all_used_functions );
						$all_used_methods   = $this->get_used_methods( $file['uses'], $all_used_methods );
					}

				}

			}

			// Uniqueue.
			$all_functions      = array_unique($all_functions);
			$all_used_functions = array_unique($all_used_functions);

			WP_CLI::line( "ALL_FUNCTIONS: ".count( $all_functions ) );
			// WP_CLI::line( print_r( $all_functions ) );
			
			WP_CLI::line( "ALL_USED_FUNCTIONS: ".count( $all_used_functions ) );
			// WP_CLI::line( print_r( $all_used_functions ) );

			WP_CLI::line( "ALL_METHODS: ".count( $all_methods ) );
			// WP_CLI::line( print_r( $all_methods ) );

			WP_CLI::line( "ALL_USED_METHODS: ".count( $all_used_methods ) );
			// WP_CLI::line( print_r( $all_used_methods ) );


			$unused_functions = array();
			foreach ($all_functions as $key => $function) {
				if( ! in_array( $function, $all_used_functions ) ) {
					$unused_functions[] = $function;
				}
			}
			WP_CLI::line( "-----------------------------------" );
			WP_CLI::line( "Unused Functions: (".count( $unused_functions ).")" );
			if( $show_functions ) {
				WP_CLI::line( print_r( $unused_functions ) );
			}

			$unused_classes = array();
			$unused_methods = array();
			foreach ($all_methods as $class => $methods) {

				// Un used classes.
				if( ! array_key_exists($class, $all_used_methods) ) {
					$unused_classes[] = $class;
				} else {

					if( $all_used_methods[ $class ] ) {
						foreach ($all_used_methods[ $class ] as $key => $class_method) {

							if( ! in_array($class_method, $all_methods[ $class ])) {
								$unused_methods[ $class ][] = $class_method;
							}

						}
					}
				}

				// if( ! in_array( $method, $all_used_methods ) ) {
				// 	$unused_methods[] = $method;
				// }
			}
			WP_CLI::line( "Unused Classes: (".count( $unused_classes ).")" );
			// WP_CLI::line( print_r( $unused_classes ) );

			WP_CLI::line( "Unused Class Methods: (".count( $unused_methods ).")" );
			if( $show_methods ) {
				WP_CLI::line( print_r( $unused_methods ) );
			}


			// get_site
			// C:\xampp\htdocs\dev.fresh\wp-content\plugins\astra-portfolio\

		}
	}

	/**
	 * Add Command
	 */
	WP_CLI::add_command( 'dod', 'DOD' );

endif;
