<?php
/**
 * Test básico siguiendo estándar WordPress
 *
 * @package DevTools
 */

class Test_Basic_Standard extends WP_UnitTestCase {

	/**
	 * Test que WordPress está cargado correctamente
	 */
	public function test_wordpress_loaded() {
		$this->assertTrue( function_exists( 'wp' ) );
		$this->assertTrue( function_exists( 'add_action' ) );
		$this->assertTrue( function_exists( 'add_filter' ) );
	}

	/**
	 * Test que la base de datos está configurada
	 */
	public function test_database_connection() {
		global $wpdb;
		$this->assertInstanceOf( 'wpdb', $wpdb );
		$this->assertNotEmpty( $wpdb->prefix );
		$this->assertEquals( 'wp_test_', $wpdb->prefix );
	}

	/**
	 * Test que Dev-Tools está cargado
	 */
	public function test_dev_tools_loaded() {
		// Verificar que la configuración está cargada
		$this->assertTrue( function_exists( 'dev_tools_config' ) );
		
		// Verificar que el sistema de módulos está disponible
		$this->assertTrue( class_exists( 'DevToolsModuleManager' ) );
	}

	/**
	 * Test que el plugin host puede ser detectado
	 */
	public function test_host_plugin_detection() {
		$plugin_file = dirname( dirname( dirname( __FILE__ ) ) ) . '/tarokina-pro.php';
		
		if ( file_exists( $plugin_file ) ) {
			$this->assertTrue( file_exists( $plugin_file ) );
			
			// Verificar que el header del plugin es válido
			$plugin_data = get_file_data( $plugin_file, array(
				'Name' => 'Plugin Name',
				'Version' => 'Version',
			) );
			
			$this->assertNotEmpty( $plugin_data['Name'] );
		} else {
			$this->markTestSkipped( 'Plugin host no encontrado - ejecutando en modo independiente' );
		}
	}
}
