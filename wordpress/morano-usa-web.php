<?php
/**
 * Plugin Name:       Morano USA — Web corporativa
 * Description:       Web de Morano USA (portada y términos de venta). Se publica desde Ajustes → Web Morano USA.
 * Version:           __MUW_VERSION__
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Morano USA
 * License:           Propietaria
 *
 * Las páginas son HTML ya compilado (pages/) y los recursos estáticos (site/)
 * los sirve el servidor directamente. Este archivo solo decide qué página
 * corresponde a cada dirección. No toca el tema, Elementor ni el contenido de
 * WordPress: al desactivar el plugin vuelve la web anterior.
 *
 * Estados (opción muw_live):
 * - Vista previa (por defecto): solo los administradores conectados ven la web nueva.
 * - Publicada: todos los visitantes. Las direcciones de la web anterior que ya
 *   no existen redirigen a su equivalente o muestran la página 404 nueva.
 */

defined( 'ABSPATH' ) || exit;

define( 'MUW_VERSION', '__MUW_VERSION__' );
define( 'MUW_OPTION', 'muw_live' );
define( 'MUW_DIR', __DIR__ );

add_action( 'init', 'muw_route', 0 );
add_action( 'template_redirect', 'muw_fallback', 0 );
add_filter( 'robots_txt', 'muw_robots', 99, 2 );
add_filter( 'wp_sitemaps_enabled', 'muw_sitemaps_enabled', 99 );
add_action( 'admin_menu', 'muw_admin_menu' );
add_action( 'admin_init', 'muw_admin_init' );
add_action( 'admin_post_muw_upload_img', 'muw_handle_img_upload' );
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'muw_action_links' );
add_action( 'add_option_' . MUW_OPTION, 'muw_purge_cache' );
add_action( 'update_option_' . MUW_OPTION, 'muw_purge_cache' );
register_activation_hook( __FILE__, 'muw_purge_cache' );
register_deactivation_hook( __FILE__, 'muw_purge_cache' );

function muw_is_live() {
	return (bool) get_option( MUW_OPTION, false );
}

/** ¿Ve esta petición la web nueva? (publicada, o administrador en vista previa) */
function muw_applies() {
	return muw_is_live() || current_user_can( 'manage_options' );
}

/** Solo las peticiones normales de páginas que pasan por index.php. */
function muw_is_front_request() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}
	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
		return false;
	}
	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( (string) $_SERVER['SCRIPT_NAME'] ) : 'index.php';
	if ( 'index.php' !== $script ) {
		return false; // wp-login.php, wp-signup.php, xmlrpc.php…
	}
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
	return 'GET' === $method || 'HEAD' === $method;
}

/** Ruta pedida, sin la subcarpeta de WordPress si la hubiera. null = no es de este sitio. */
function muw_request_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$base = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( '' !== $base ) {
		if ( $path !== $base && 0 !== strpos( $path, $base . '/' ) ) {
			return null;
		}
		$path = (string) substr( $path, strlen( $base ) );
	}
	return '' === $path ? '/' : $path;
}

/** Decide si la petición es de la web nueva y, si lo es, la sirve y termina. */
function muw_route() {
	if ( ! muw_is_front_request() || ! muw_applies() ) {
		return;
	}
	$path = muw_request_path();
	if ( null === $path ) {
		return;
	}

	// www.moranousa.com → moranousa.com (misma ruta y parámetros).
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$home = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	if ( '' !== $home && 'www.' . $home === $host ) {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
		muw_clean_buffers();
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
		wp_redirect( 'https://' . $home . $uri, 301, 'Morano USA' );
		exit;
	}

	// Portada (sin parámetros propios de WordPress: búsquedas, vistas previas…).
	if ( '/' === $path ) {
		if ( muw_is_plain_request() ) {
			muw_send( MUW_DIR . '/pages/index.html', 'text/html; charset=UTF-8', 200 );
		}
		return;
	}

	if ( '/sitemap.xml' === $path ) {
		muw_send( MUW_DIR . '/pages/sitemap.xml', 'application/xml; charset=UTF-8', 200 );
	}

	// Direcciones de la web anterior que ya no existen.
	$to = muw_redirect_target( $path );
	if ( null !== $to ) {
		muw_redirect( $to, 301, false );
	}

	// Páginas de la web nueva: /slug/ o /slug/sub/.
	if ( ! preg_match( '#^(/[a-z0-9][a-z0-9-]*)+/?$#', $path ) ) {
		return; // wp-content, archivos, etc.: siguen siendo de WordPress o del servidor.
	}
	$dir = MUW_DIR . '/pages' . rtrim( $path, '/' );
	if ( ! is_file( $dir . '/index.html' ) ) {
		return; // Lo resuelve WordPress; si no es nada, muw_fallback() muestra el 404 nuevo.
	}
	if ( '/' !== substr( $path, -1 ) ) {
		muw_redirect( $path . '/', 301, true );
	}
	muw_send( $dir . '/index.html', 'text/html; charset=UTF-8', 200 );
}

/**
 * Publicada, cualquier otra dirección pública de la web anterior (entradas,
 * archivos, adjuntos, búsquedas) muestra el 404 de la web nueva. Los usuarios
 * conectados siguen viendo WordPress para poder editar y previsualizar.
 */
function muw_fallback() {
	if ( ! muw_is_live() || is_user_logged_in() || ! muw_is_front_request() ) {
		return;
	}
	if ( is_robots() || is_favicon() || is_preview() || is_customize_preview() ) {
		return;
	}
	muw_not_found();
}

/** Destino de una dirección antigua (redirects.json, generado desde vercel.json). */
function muw_redirect_target( $path ) {
	static $map = null;
	if ( null === $map ) {
		$json = @file_get_contents( MUW_DIR . '/pages/redirects.json' ); // phpcs:ignore
		$map  = $json ? json_decode( $json, true ) : array();
		if ( ! is_array( $map ) ) {
			$map = array();
		}
	}
	$key = '/' === $path ? '/' : rtrim( $path, '/' );
	if ( isset( $map['exact'][ $key ] ) ) {
		return $map['exact'][ $key ];
	}
	if ( ! empty( $map['prefix'] ) ) {
		foreach ( $map['prefix'] as $prefix => $to ) {
			if ( $key === $prefix || 0 === strpos( $key, $prefix . '/' ) ) {
				return $to;
			}
		}
	}
	return null;
}

/** Sin parámetros de WordPress (búsquedas, vistas previas, IDs…). */
function muw_is_plain_request() {
	$wp_keys = array( 's', 'p', 'page_id', 'preview', 'preview_id', 'preview_nonce', 'attachment_id', 'cat', 'tag', 'author', 'feed', 'm', 'post_type', 'name', 'pagename', 'rest_route', 'sitemap', 'sitemap-subtype', 'sitemap-stylesheet', 'elementor-preview', 'elementor_library', 'customize_changeset_uuid', 'customize_theme', 'doing_wp_cron', 'litespeed_action' );
	foreach ( $wp_keys as $key ) {
		if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}
	}
	return true;
}

function muw_clean_buffers() {
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}
}

/** $to puede llevar #ancla. $keep_query: conservar ?utm=… de la petición. */
function muw_redirect( $to, $status, $keep_query ) {
	$query = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : '';
	$hash  = '';
	if ( false !== strpos( $to, '#' ) ) {
		list( $to, $hash ) = explode( '#', $to, 2 );
		$hash = '#' . $hash;
	}
	$url = home_url( $to ) . ( $keep_query && '' !== $query ? '?' . $query : '' ) . $hash;
	muw_clean_buffers();
	muw_drop_cookies();
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	wp_redirect( $url, $status, 'Morano USA' );
	exit;
}

/** La web no usa cookies: a los visitantes sin sesión no se les envía ninguna. */
function muw_drop_cookies() {
	if ( ! is_user_logged_in() ) {
		header_remove( 'Set-Cookie' );
	}
}

function muw_not_found() {
	muw_send( MUW_DIR . '/pages/404.html', 'text/html; charset=UTF-8', 404 );
}

/** Envía un archivo de pages/ con las cabeceras de la web y termina. */
function muw_send( $file, $type, $status ) {
	muw_clean_buffers();
	muw_drop_cookies();
	$cacheable = ( 200 === $status ) && muw_is_live() && ! is_user_logged_in();
	status_header( $status );
	header( 'Content-Type: ' . $type );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'X-Frame-Options: DENY' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()' );
	if ( $cacheable ) {
		header( 'Cache-Control: public, max-age=0, must-revalidate' );
		header( 'X-LiteSpeed-Cache-Control: public,max-age=604800' );
		header( 'X-LiteSpeed-Tag: ' . muw_lscache_tags() );
	} else {
		nocache_headers();
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
	}
	header( 'Content-Length: ' . filesize( $file ) );
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
	if ( 'HEAD' !== $method ) {
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	exit;
}

function muw_robots( $output, $is_public ) {
	if ( ! muw_is_live() || ! $is_public ) {
		return $output;
	}
	return rtrim( $output ) . "\nSitemap: " . home_url( '/sitemap.xml' ) . "\n";
}

/** Publicada, el mapa del sitio es el de la web nueva (/sitemap.xml), no el de WordPress. */
function muw_sitemaps_enabled( $enabled ) {
	return muw_is_live() ? false : $enabled;
}

/*
 * Imágenes. Van en wp-content/uploads/morano-usa-web/img/ (no dentro del
 * plugin): así sobreviven al actualizar el plugin y el ZIP del plugin es
 * pequeño. Se suben por partes desde Ajustes → Web Morano USA.
 */

function muw_img_dir() {
	$up = wp_upload_dir( null, false );
	return trailingslashit( $up['basedir'] ) . 'morano-usa-web/img';
}

/** Imágenes que la web espera (pages/img-manifest.json: nombre → md5). */
function muw_img_manifest() {
	$json = @file_get_contents( MUW_DIR . '/pages/img-manifest.json' ); // phpcs:ignore
	$map  = $json ? json_decode( $json, true ) : array();
	return is_array( $map ) ? $map : array();
}

/** Nombres de las imágenes que faltan o no coinciden con esta versión. */
function muw_img_pending() {
	$dir     = muw_img_dir();
	$pending = array();
	foreach ( muw_img_manifest() as $name => $md5 ) {
		$file = $dir . '/' . $name;
		if ( ! is_file( $file ) || md5_file( $file ) !== $md5 ) {
			$pending[] = $name;
		}
	}
	return $pending;
}

function muw_handle_img_upload() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sin permiso.' );
	}
	check_admin_referer( 'muw_upload_img' );
	$back = admin_url( 'options-general.php?page=morano-usa-web' );
	$tmp  = isset( $_FILES['muw_zip']['tmp_name'] ) ? (string) $_FILES['muw_zip']['tmp_name'] : '';
	if ( '' === $tmp || ! is_uploaded_file( $tmp ) ) {
		wp_safe_redirect( add_query_arg( 'muw_msg', 'nofile', $back ) );
		exit;
	}
	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_safe_redirect( add_query_arg( 'muw_msg', 'nozip', $back ) );
		exit;
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $tmp ) ) {
		wp_safe_redirect( add_query_arg( 'muw_msg', 'badzip', $back ) );
		exit;
	}
	$dir = muw_img_dir();
	wp_mkdir_p( $dir );
	muw_img_htaccess( dirname( $dir ) );
	$n = 0;
	for ( $i = 0; $i < $zip->numFiles; $i++ ) {
		$name = (string) $zip->getNameIndex( $i );
		// Solo imágenes planas dentro de img/: nada de subcarpetas, «..» ni otros tipos.
		if ( ! preg_match( '#^img/([a-z0-9][a-z0-9._-]*\.(?:avif|webp|jpe?g|png|svg))$#i', $name, $m ) || false !== strpos( $name, '..' ) ) {
			continue;
		}
		$data = $zip->getFromIndex( $i );
		if ( false !== $data && false !== file_put_contents( $dir . '/' . $m[1], $data ) ) { // phpcs:ignore
			$n++;
		}
	}
	$zip->close();
	muw_purge_cache();
	wp_safe_redirect( add_query_arg( array( 'muw_msg' => 'ok', 'muw_n' => $n ), $back ) );
	exit;
}

/** Tipos y caché de las imágenes (Apache/LiteSpeed). */
function muw_img_htaccess( $dir ) {
	$file = $dir . '/.htaccess';
	if ( is_file( $file ) ) {
		return;
	}
	file_put_contents( // phpcs:ignore
		$file,
		"<IfModule mod_mime.c>\n  AddType image/avif .avif\n  AddType image/webp .webp\n</IfModule>\n<IfModule mod_headers.c>\n  <FilesMatch \"\\.(avif|webp|jpe?g|png|svg)$\">\n    Header set Cache-Control \"public, max-age=2592000\"\n  </FilesMatch>\n</IfModule>\n"
	);
	file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore
}

/**
 * Etiquetas de caché de las páginas de la web. El «Purgar todo» de LiteSpeed
 * Cache purga la etiqueta «{prefijo}_» que pone a cada página que cachea; las
 * páginas de este plugin no pasan por LiteSpeed Cache, así que se les pone a
 * mano (si no, «Purgar todo» no las borra y siguen en caché hasta 7 días).
 */
function muw_lscache_tags() {
	$tags = array( 'muw' );
	if ( defined( 'LSWCP_TAG_PREFIX' ) ) {
		$prefix = LSWCP_TAG_PREFIX . ( is_multisite() ? get_current_blog_id() : '' ) . '_';
		$tags[] = $prefix;
		$tags[] = $prefix . 'muw';
	}
	return implode( ',', $tags );
}

/**
 * Vacía la caché de LiteSpeed al publicar, ocultar o actualizar, a través de
 * LiteSpeed Cache (si se envía una cabecera X-LiteSpeed-Purge propia,
 * LiteSpeed Cache la reemplaza con la suya).
 */
function muw_purge_cache() {
	do_action( 'litespeed_purge_all' );
	do_action( 'litespeed_purge', 'muw' );
}

function muw_admin_init() {
	register_setting(
		'muw',
		MUW_OPTION,
		array(
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'muw_sanitize_bool',
		)
	);
	if ( get_option( 'muw_version' ) !== MUW_VERSION ) {
		update_option( 'muw_version', MUW_VERSION, false );
		muw_purge_cache();
	}
}

function muw_sanitize_bool( $value ) {
	return ! empty( $value );
}

function muw_admin_menu() {
	add_options_page( 'Web Morano USA', 'Web Morano USA', 'manage_options', 'morano-usa-web', 'muw_settings_page' );
}

function muw_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=morano-usa-web' ) ) . '">Ajustes</a>' );
	return $links;
}

function muw_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$live    = muw_is_live();
	$total   = count( muw_img_manifest() );
	$pending = muw_img_pending();
	$msg     = isset( $_GET['muw_msg'] ) ? sanitize_key( $_GET['muw_msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$msgs    = array(
		'ok'     => 'Paquete subido: ' . ( isset( $_GET['muw_n'] ) ? absint( $_GET['muw_n'] ) : 0 ) . ' imágenes guardadas.', // phpcs:ignore
		'nofile' => 'No llegó ningún archivo. Elija el ZIP de imágenes e inténtelo de nuevo.',
		'nozip'  => 'El servidor no tiene la extensión ZipArchive de PHP.',
		'badzip' => 'El archivo no es un ZIP válido.',
	);
	?>
	<div class="wrap">
		<h1>Web Morano USA</h1>
		<?php if ( isset( $msgs[ $msg ] ) ) : ?>
			<div class="notice notice-<?php echo 'ok' === $msg ? 'success' : 'error'; ?>"><p><?php echo esc_html( $msgs[ $msg ] ); ?></p></div>
		<?php endif; ?>
		<p>
			Estado:
			<strong><?php echo $live ? 'publicada para todos los visitantes' : 'vista previa (solo administradores conectados)'; ?></strong>
		</p>
		<p id="muw-img-status">
			Imágenes:
			<strong><?php echo esc_html( ( $total - count( $pending ) ) . ' de ' . $total ); ?></strong>
			<?php echo $pending ? '— faltan ' . count( $pending ) . '. Suba los paquetes de imágenes.' : '— completas.'; ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="muw_upload_img" />
			<?php wp_nonce_field( 'muw_upload_img' ); ?>
			<label>Paquete de imágenes (ZIP): <input type="file" name="muw_zip" id="muw-zip" accept=".zip,application/zip" /></label>
			<?php submit_button( 'Subir imágenes', 'secondary', 'muw-upload', false ); ?>
		</form>
		<hr />
		<p>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">Ver la web</a>
		</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'muw' ); ?>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( MUW_OPTION ); ?>" value="1" <?php checked( $live ); ?> />
				Publicar la web nueva para todos los visitantes
			</label>
			<?php submit_button( 'Guardar' ); ?>
		</form>
		<p class="description">
			Versión <?php echo esc_html( MUW_VERSION ); ?>. El escritorio de WordPress, los usuarios y el correo no cambian.
			Al desactivar el plugin vuelve la web anterior.
		</p>
	</div>
	<?php
}
