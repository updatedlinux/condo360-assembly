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
		);
		
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $matches ) ) {
				return $matches[1];
			}
		}
		
		// Si no se encuentra un patrón, intentar usar la URL completa
		return $url;
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
		wp_add_inline_style( 'wp-block-library', $this->get_custom_styles() );
	}
	
	public function get_custom_styles() {
		return '
		/* Contenedor de Asamblea en Vivo */
		.condo360-asamblea-container {
			max-width: 1600px;
			margin: 0 auto;
			padding: 30px 20px;
			width: 100%;
			box-sizing: border-box;
		}
		
		/* Contenedor del video */
		.condo360-asamblea-video-section {
			background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
			border-radius: 20px;
			padding: 20px;
			box-shadow: 0 10px 40px rgba(102, 126, 234, 0.15), 0 4px 20px rgba(0, 0, 0, 0.08);
			border: 2px solid rgba(102, 126, 234, 0.2);
			width: 100%;
			box-sizing: border-box;
			position: relative;
			overflow: hidden;
		}
		
		.condo360-asamblea-video-section::before {
			content: "";
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			height: 4px;
			background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
		}
		
		.condo360-asamblea-video-wrapper {
			position: relative;
			padding-bottom: 56.25%; /* 16:9 aspect ratio */
			height: 0;
			overflow: hidden;
			border-radius: 16px;
			box-shadow: 0 12px 40px rgba(102, 126, 234, 0.25), 0 4px 15px rgba(0, 0, 0, 0.2);
			background: #000;
			width: 100%;
			border: 3px solid rgba(102, 126, 234, 0.1);
		}
		
		.condo360-asamblea-video-wrapper iframe {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			border: none;
		}
		
		/* Mensaje cuando no hay URL configurada */
		.condo360-asamblea-no-url {
			text-align: center;
			padding: 60px 40px;
			color: #6b7280;
			background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
			border-radius: 16px;
			border: 2px dashed rgba(102, 126, 234, 0.3);
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
		
		/* Estilos para pantallas grandes (PC) */
		@media (min-width: 1400px) {
			.condo360-asamblea-container {
				max-width: 1800px;
				padding: 40px 30px;
			}
			
			.condo360-asamblea-video-section {
				padding: 30px;
				border-radius: 24px;
			}
			
			.condo360-asamblea-video-wrapper {
				border-radius: 20px;
			}
		}
		
		/* Responsive para tablets */
		@media (max-width: 1024px) {
			.condo360-asamblea-container {
				padding: 25px 15px;
			}
			
			.condo360-asamblea-video-section {
				padding: 18px;
				border-radius: 16px;
			}
			
			.condo360-asamblea-video-wrapper {
				border-radius: 12px;
			}
		}
		
		/* Responsive para móviles */
		@media (max-width: 768px) {
			.condo360-asamblea-container {
				padding: 20px 10px;
			}
			
			.condo360-asamblea-video-section {
				padding: 15px;
				border-radius: 12px;
			}
			
			.condo360-asamblea-video-wrapper {
				border-radius: 10px;
				border-width: 2px;
			}
		}
		
		/* Responsive para móviles pequeños */
		@media (max-width: 480px) {
			.condo360-asamblea-container {
				padding: 15px 8px;
			}
			
			.condo360-asamblea-video-section {
				padding: 12px;
				border-radius: 10px;
			}
			
			.condo360-asamblea-video-wrapper {
				border-radius: 8px;
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
		
		// Si es una URL completa de embed o live, usarla directamente
		$embed_url = '';
		if ( strpos( $youtube_url, 'youtube.com/embed/' ) !== false || strpos( $youtube_url, 'youtube.com/live/' ) !== false ) {
			// Si ya es una URL de embed, usarla directamente
			if ( strpos( $youtube_url, 'youtube.com/live/' ) !== false ) {
				$embed_url = str_replace( 'youtube.com/live/', 'youtube.com/embed/', $youtube_url );
			} else {
				$embed_url = $youtube_url;
			}
		} elseif ( ! empty( $youtube_id ) && strlen( $youtube_id ) === 11 ) {
			// Si tenemos un ID válido, construir la URL de embed
			$embed_url = 'https://www.youtube.com/embed/' . esc_attr( $youtube_id );
		} else {
			// Si no podemos parsear, intentar usar la URL directamente
			$embed_url = esc_url( $youtube_url );
		}
		
		// Agregar parámetros para mejor reproducción
		$embed_url .= ( strpos( $embed_url, '?' ) !== false ? '&' : '?' ) . 'autoplay=0&rel=0&modestbranding=1';
		
		ob_start();
		?>
		<div class="condo360-asamblea-container">
			<!-- Contenedor del video -->
			<div class="condo360-asamblea-video-section">
				<div class="condo360-asamblea-video-wrapper">
					<iframe 
						src="<?php echo esc_url( $embed_url ); ?>" 
						title="<?php esc_attr_e( 'Asamblea General de Condominio en Vivo', 'condo360-asamblea' ); ?>"
						allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
						allowfullscreen
						loading="lazy"
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

