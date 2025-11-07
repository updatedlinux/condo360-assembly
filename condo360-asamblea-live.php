<?php
/**
 * Plugin Name: Condo360 - Asamblea en Vivo
 * Plugin URI: https://condo360.com
 * Description: Shortcode para mostrar transmisión en vivo de Asamblea General de Condominio desde YouTube
 * Version: 1.0.0
 * Author: Condo360
 * Author URI: https://condo360.com
 * Text Domain: condo360-asamblea
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Condo360_Asamblea_Live {
	
	private static $instance = null;
	
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_shortcode( 'condo360_asamblea_live', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}
	
	public function init() {
		load_plugin_textdomain( 'condo360-asamblea', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	
	public function add_admin_menu() {
		add_options_page(
			'Asamblea en Vivo - Configuración',
			'Asamblea en Vivo',
			'manage_options',
			'condo360-asamblea-live',
			array( $this, 'render_admin_page' )
		);
	}
	
	public function register_settings() {
		register_setting( 'condo360_asamblea_settings', 'condo360_asamblea_youtube_url', array(
			'sanitize_callback' => array( $this, 'sanitize_youtube_url' ),
			'default' => ''
		) );
	}
	
	public function sanitize_youtube_url( $url ) {
		// Extraer el ID del video de YouTube de diferentes formatos de URL
		$url = esc_url_raw( $url );
		return $url;
	}
	
	public function get_youtube_id( $url ) {
		if ( empty( $url ) ) {
			return '';
		}
		
		// Patrones para diferentes formatos de URL de YouTube
		$patterns = array(
			'/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
			'/youtube\.com\/live\/([a-zA-Z0-9_-]{11})/',
			'/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
		);
		
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $matches ) ) {
				return $matches[1];
			}
		}
		
		// Si no se encuentra un patrón, intentar usar la URL completa
		return '';
	}
	
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Guardar configuración
		if ( isset( $_POST['submit'] ) && check_admin_referer( 'condo360_asamblea_settings' ) ) {
			update_option( 'condo360_asamblea_youtube_url', sanitize_text_field( $_POST['condo360_asamblea_youtube_url'] ) );
			echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
		}
		
		$youtube_url = get_option( 'condo360_asamblea_youtube_url', '' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="">
				<?php wp_nonce_field( 'condo360_asamblea_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="condo360_asamblea_youtube_url">URL de YouTube</label>
						</th>
						<td>
							<input 
								type="url" 
								id="condo360_asamblea_youtube_url" 
								name="condo360_asamblea_youtube_url" 
								value="<?php echo esc_attr( $youtube_url ); ?>" 
								class="regular-text"
								placeholder="https://www.youtube.com/watch?v=VIDEO_ID o https://youtu.be/VIDEO_ID"
							/>
							<p class="description">
								Ingrese la URL completa de la transmisión en vivo de YouTube. 
								Puede ser un video normal o una transmisión en vivo.
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Guardar configuración' ); ?>
			</form>
			<hr>
			<h2>Uso del Shortcode</h2>
			<p>Para mostrar el reproductor de YouTube en cualquier página o entrada, use el siguiente shortcode:</p>
			<code>[condo360_asamblea_live]</code>
		</div>
		<?php
	}
	
	public function enqueue_styles() {
		// Registrar y encolar estilos para asegurar que se carguen
		wp_register_style( 'condo360-asamblea-styles', false );
		wp_enqueue_style( 'condo360-asamblea-styles' );
		wp_add_inline_style( 'condo360-asamblea-styles', $this->get_custom_styles() );
	}
	
	public function get_custom_styles() {
		return '
		/* Contenedor principal con estilo moderno - Similar a wc-custom-card */
		.condo360-asamblea-container {
			max-width: 1200px;
			margin: 30px auto;
			padding: 0;
			background: #ffffff;
			border-radius: 16px;
			box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
			border: 1px solid #e1e5e9;
			overflow: hidden;
			width: 100%;
			box-sizing: border-box;
		}
		
		/* Contenedor del video - Similar a wc-form-wrapper */
		.condo360-asamblea-video-section {
			width: 100%;
			padding: 40px;
			background: transparent;
			border: none;
			box-sizing: border-box;
		}
		
		/* Wrapper del video - debe ser grande y visible */
		.condo360-asamblea-video-wrapper {
			position: relative;
			width: 100%;
			padding-bottom: 56.25%; /* 16:9 aspect ratio */
			height: 0;
			overflow: hidden;
			border-radius: 12px;
			background: #000;
			box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
		}
		
		.condo360-asamblea-video-wrapper iframe {
			position: absolute;
			top: 0;
			left: 0;
			width: 100% !important;
			height: 100% !important;
			border: none;
			min-width: 100%;
			min-height: 100%;
		}
		
		/* Mensaje cuando no hay URL configurada */
		.condo360-asamblea-no-url {
			text-align: center;
			padding: 60px 40px;
			color: #6b7280;
			background: #ffffff;
			border-radius: 12px;
			border: 2px dashed #e5e7eb;
		}
		
		.condo360-asamblea-no-url::before {
			content: "⚠️";
			font-size: 48px;
			display: block;
			margin-bottom: 15px;
		}
		
		.condo360-asamblea-no-url p {
			font-size: 16px;
			margin: 0;
			color: #6b7280;
		}
		
		/* Responsive para tablets */
		@media (max-width: 1024px) {
			.condo360-asamblea-container {
				margin: 25px auto;
				max-width: 95%;
			}
			
			.condo360-asamblea-video-section {
				padding: 30px;
			}
		}
		
		/* Responsive para móviles */
		@media (max-width: 768px) {
			.condo360-asamblea-container {
				margin: 20px;
				border-radius: 12px;
			}
			
			.condo360-asamblea-video-section {
				padding: 20px;
			}
			
			.condo360-asamblea-video-wrapper {
				border-radius: 8px;
			}
		}
		
		/* Responsive para móviles pequeños */
		@media (max-width: 480px) {
			.condo360-asamblea-container {
				margin: 15px;
			}
			
			.condo360-asamblea-video-section {
				padding: 15px;
			}
		}
		';
	}
	
	public function render_shortcode( $atts ) {
		$youtube_url = get_option( 'condo360_asamblea_youtube_url', '' );
		
		if ( empty( $youtube_url ) ) {
			return '
			<div class="condo360-asamblea-container">
				<div class="condo360-asamblea-no-url">
					<p>Por favor, configure la URL de YouTube en el panel de administración de WordPress.</p>
					<p><small>Configuración → Asamblea en Vivo</small></p>
				</div>
			</div>
			';
		}
		
		$youtube_id = $this->get_youtube_id( $youtube_url );
		
		// Construir la URL de embed
		$embed_url = '';
		
		// Si ya es una URL de embed, usarla directamente
		if ( strpos( $youtube_url, 'youtube.com/embed/' ) !== false ) {
			$embed_url = $youtube_url;
		}
		// Si es una URL de live, convertirla a embed
		elseif ( strpos( $youtube_url, 'youtube.com/live/' ) !== false ) {
			$embed_url = str_replace( 'youtube.com/live/', 'youtube.com/embed/', $youtube_url );
		}
		// Si tenemos un ID válido, construir la URL de embed
		elseif ( ! empty( $youtube_id ) && strlen( $youtube_id ) === 11 ) {
			$embed_url = 'https://www.youtube.com/embed/' . $youtube_id;
		}
		// Si no podemos parsear, mostrar error
		else {
			return '
			<div class="condo360-asamblea-container">
				<div class="condo360-asamblea-no-url">
					<p>Error: La URL de YouTube no es válida. Por favor, verifique la configuración.</p>
					<p><small>Configuración → Asamblea en Vivo</small></p>
					<p><small>URL configurada: ' . esc_html( $youtube_url ) . '</small></p>
				</div>
			</div>
			';
		}
		
		// Agregar parámetros para mejor reproducción
		$separator = ( strpos( $embed_url, '?' ) !== false ) ? '&' : '?';
		$embed_url .= $separator . 'autoplay=0&rel=0&modestbranding=1';
		
		// Debug: Verificar que la URL esté correcta (solo para administradores)
		$debug_info = '';
		if ( current_user_can( 'manage_options' ) && isset( $_GET['debug_asamblea'] ) ) {
			$debug_info = '<div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px; border-radius: 4px; font-size: 12px;">
				<strong>Debug Info:</strong><br>
				URL Original: ' . esc_html( $youtube_url ) . '<br>
				YouTube ID: ' . esc_html( $youtube_id ) . '<br>
				Embed URL: ' . esc_html( $embed_url ) . '
			</div>';
		}
		
		ob_start();
		?>
		<style>
		/* Contenedor principal - Resoluciones estándar PC */
		.condo360-asamblea-container {
			max-width: 1400px !important;
			margin: 30px auto !important;
			padding: 0 !important;
			background: #ffffff !important;
			border-radius: 16px !important;
			box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;
			border: 1px solid #e1e5e9 !important;
			overflow: hidden !important;
			width: 95% !important;
			box-sizing: border-box !important;
			display: block !important;
		}
		
		/* Sección del video - Padding reducido para video más grande */
		.condo360-asamblea-video-section {
			width: 100% !important;
			padding: 20px !important;
			background: transparent !important;
			border: none !important;
			box-sizing: border-box !important;
			display: block !important;
		}
		
		/* Wrapper del video - Tamaño grande para PC */
		.condo360-asamblea-video-wrapper {
			position: relative !important;
			width: 100% !important;
			padding-bottom: 56.25% !important; /* 16:9 aspect ratio - esto crea la altura */
			height: 0 !important;
			overflow: hidden !important;
			border-radius: 12px !important;
			background: #000 !important;
			box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
		}
		
		/* Asegurar altura mínima visible en PC */
		@media (min-width: 1024px) {
			.condo360-asamblea-video-wrapper {
				min-height: 600px !important;
				padding-bottom: 0 !important; /* Desactivar padding-bottom cuando hay min-height */
			}
		}
		
		@media (min-width: 1024px) and (max-width: 1365px) {
			.condo360-asamblea-video-wrapper {
				min-height: 550px !important;
			}
		}
		
		/* Iframe del video - Asegurar que se vea */
		.condo360-asamblea-video-wrapper iframe {
			position: absolute !important;
			top: 0 !important;
			left: 0 !important;
			width: 100% !important;
			height: 100% !important;
			border: none !important;
			display: block !important;
			z-index: 1 !important;
		}
		
		/* Para pantallas grandes (1920px y más) */
		@media (min-width: 1920px) {
			.condo360-asamblea-container {
				max-width: 1600px !important;
			}
			.condo360-asamblea-video-section {
				padding: 30px !important;
			}
		}
		
		/* Para pantallas medianas (1366px - 1919px) */
		@media (min-width: 1366px) and (max-width: 1919px) {
			.condo360-asamblea-container {
				max-width: 1300px !important;
			}
		}
		
		/* Para pantallas estándar (1024px - 1365px) */
		@media (min-width: 1024px) and (max-width: 1365px) {
			.condo360-asamblea-container {
				max-width: 1200px !important;
			}
		}
		
		/* Solo aplicar responsive para tablets y móviles */
		@media (max-width: 1023px) {
			.condo360-asamblea-container {
				max-width: 100% !important;
				margin: 20px !important;
				width: calc(100% - 40px) !important;
				border-radius: 12px !important;
			}
			.condo360-asamblea-video-section {
				padding: 15px !important;
			}
		}
		</style>
		<?php echo $debug_info; ?>
		<div class="condo360-asamblea-container">
			<!-- Contenedor del video -->
			<div class="condo360-asamblea-video-section">
				<div class="condo360-asamblea-video-wrapper">
					<iframe 
						src="<?php echo esc_url( $embed_url ); ?>" 
						title="<?php esc_attr_e( 'Asamblea General de Condominio en Vivo', 'condo360-asamblea' ); ?>"
						allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
						allowfullscreen="true"
						width="100%"
						height="100%"
						frameborder="0"
						style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"
					></iframe>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

// Inicializar el plugin
Condo360_Asamblea_Live::get_instance();

