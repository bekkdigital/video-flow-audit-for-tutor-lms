<?php
/**
 * Video Flow Core — provider detection.
 *
 * Pure string/regex helpers that pull video IDs out of stored data
 * (post content, meta values, oEmbed cache). No HTTP, no WordPress
 * dependency — trivially unit-testable.
 *
 * Supported providers: Vimeo, YouTube, Bunny Stream, local/self-hosted.
 *
 * @package VideoFlowCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'vfaudit_core_strip_embed_shortcode' ) ) {

	/**
	 * Turn [embed]https://…[/embed] wrappers into bare URLs so a single URL
	 * regex catches both shapes.
	 */
	function vfaudit_core_strip_embed_shortcode( string $text ): string {
		return (string) preg_replace( '#\[embed\](https?://[^\[]+)\[/embed\]#i', '$1', $text );
	}

	/**
	 * Every Vimeo numeric ID referenced by a blob of text (post content,
	 * a meta value, an oEmbed cache entry…).
	 *
	 * @return string[] Unique numeric IDs, in first-seen order.
	 */
	function vfaudit_core_extract_vimeo_ids( string $text ): array {
		if ( '' === $text || false === stripos( $text, 'vimeo.com' ) ) {
			return array();
		}

		$scan = vfaudit_core_strip_embed_shortcode( $text );
		preg_match_all( '#https?://(?:(?:www\.|player\.)?vimeo\.com/(?:video/)?(\d+))#i', $scan, $m );

		$ids = array();
		foreach ( $m[1] as $id ) {
			$ids[ $id ] = true;
		}

		// Also handle a raw "vimeo.com/12345" with no scheme (rare, but seen
		// in hand-entered Tutor "Video URL" fields).
		if ( preg_match_all( '#(?:^|[^\w/])vimeo\.com/(?:video/)?(\d+)#i', $scan, $m2 ) ) {
			foreach ( $m2[1] as $id ) {
				$ids[ $id ] = true;
			}
		}

		// array_keys() casts numeric string keys to int — force back to string
		// so IDs compare cleanly against string meta values everywhere else.
		return array_map( 'strval', array_keys( $ids ) );
	}

	/**
	 * The Vimeo ID inside a player.vimeo.com iframe/embed string, or null.
	 */
	function vfaudit_core_extract_vimeo_id_from_iframe( string $html ): ?string {
		if ( false === strpos( $html, 'player.vimeo.com' ) ) {
			return null;
		}
		return preg_match( '#video/(\d+)#', $html, $m ) ? $m[1] : null;
	}

	/**
	 * The 11-character YouTube ID from a single URL, or null.
	 *
	 * Accepts watch?v=, youtu.be/, /embed/, /v/, /shorts/, and the
	 * -nocookie host.
	 */
	function vfaudit_core_extract_youtube_id( string $url ): ?string {
		$url = trim( $url );
		if ( '' === $url ) {
			return null;
		}

		$patterns = array(
			'#youtu\.be/([a-zA-Z0-9_-]{11})#',
			'#youtube(?:-nocookie)?\.com/watch\?(?:[^ ]*&)?v=([a-zA-Z0-9_-]{11})#',
			'#youtube(?:-nocookie)?\.com/(?:embed|v|shorts)/([a-zA-Z0-9_-]{11})#',
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $m ) ) {
				return $m[1];
			}
		}
		return null;
	}

	/**
	 * Every YouTube ID referenced by a blob of text.
	 *
	 * @return string[] Unique IDs, in first-seen order.
	 */
	function vfaudit_core_extract_youtube_ids( string $text ): array {
		if ( '' === $text || ( false === stripos( $text, 'youtube' ) && false === stripos( $text, 'youtu.be' ) ) ) {
			return array();
		}

		$scan = vfaudit_core_strip_embed_shortcode( $text );
		preg_match_all( '#https?://(?:www\.)?(?:youtube(?:-nocookie)?\.com|youtu\.be)/[^\s"\'<>]+#i', $scan, $m );

		$ids = array();
		foreach ( $m[0] as $url ) {
			$id = vfaudit_core_extract_youtube_id( $url );
			if ( null !== $id ) {
				$ids[ $id ] = true;
			}
		}

		// oEmbed cache stores a rendered <iframe src=".../embed/<id>">.
		if ( preg_match_all( '#/embed/([a-zA-Z0-9_-]{11})#', $scan, $m2 ) ) {
			foreach ( $m2[1] as $id ) {
				$ids[ $id ] = true;
			}
		}

		return array_map( 'strval', array_keys( $ids ) );
	}

	/**
	 * The Bunny Stream video GUID from a mediadelivery.net embed URL, or null.
	 */
	function vfaudit_core_extract_bunny_guid( string $url ): ?string {
		if ( false === stripos( $url, 'mediadelivery.net' ) ) {
			return null;
		}
		return preg_match( '#mediadelivery\.net/(?:embed|play)/[^/]+/([a-f0-9-]{20,})#i', $url, $m ) ? strtolower( $m[1] ) : null;
	}

	/**
	 * Every Bunny GUID referenced by a blob of text.
	 *
	 * @return string[] Unique GUIDs, in first-seen order.
	 */
	function vfaudit_core_extract_bunny_guids( string $text ): array {
		if ( '' === $text || false === stripos( $text, 'mediadelivery.net' ) ) {
			return array();
		}
		preg_match_all( '#mediadelivery\.net/(?:embed|play)/[^/]+/([a-f0-9-]{20,})#i', $text, $m );

		$ids = array();
		foreach ( $m[1] as $id ) {
			$ids[ strtolower( $id ) ] = true;
		}
		return array_keys( $ids );
	}

	/**
	 * Loose check that a string looks like a Bunny/UUID video GUID.
	 */
	function vfaudit_core_looks_like_bunny_guid( string $value ): bool {
		return (bool) preg_match( '#^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$#i', $value );
	}

	/**
	 * Local/self-hosted video URLs referenced directly in post content:
	 * Gutenberg's <video src="…"> block markup and the classic [video]
	 * shortcode. Caller decides which of these actually live under the
	 * site's own uploads directory.
	 *
	 * @return string[] Unique URLs, in first-seen order.
	 */
	function vfaudit_core_extract_local_video_srcs( string $content ): array {
		$srcs = array();

		if ( preg_match_all( '#<video\b[^>]*\bsrc=["\']([^"\']+)["\']#i', $content, $m1 ) ) {
			foreach ( $m1[1] as $src ) {
				$srcs[ $src ] = true;
			}
		}
		if ( preg_match_all( '#\[video\b[^\]]*\b(?:src|mp4)=["\']?([^"\'\]\s]+)#i', $content, $m2 ) ) {
			foreach ( $m2[1] as $src ) {
				$srcs[ $src ] = true;
			}
		}

		return array_keys( $srcs );
	}
}
