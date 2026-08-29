<?php
/**
 * Video Flow Core — the scanner.
 *
 * Given a course ID, walk the course post and every lesson/topic in its
 * curriculum (via the active LMS adapter) and report every video found,
 * regardless of where it is hosted: Vimeo, YouTube, Bunny Stream, or a
 * local/self-hosted file.
 *
 * Discovery sources per post:
 *   1. `_video` meta        — the LMS video-source modal (Tutor-style)
 *   2. `post_content`       — raw URLs, [embed], <video>, [video]
 *   3. `_oembed_*` meta     — WordPress oEmbed cache
 *   4. `bunny_video_id` meta — videos this plugin family uploaded to Bunny
 *
 * `vfaudit_core_get_course_videos()` itself performs no HTTP calls and writes
 * nothing to the database. The cached wrapper `vfaudit_core_get_course_videos_cached()`
 * stores one plugin-owned transient (`vfaudit_course_videos_<id>`, 5 min) and
 * nothing else. Nothing here ever touches LMS content, post meta, or a
 * video host. Plugins that can talk to Bunny (the paid Video Flow plugin)
 * enrich Bunny rows with live title / duration / views / transcode status
 * by hooking the `vfaudit_core_enrich_videos` filter.
 *
 * Untrusted-meta note: several fields read here (`_video`, `bunny_video_id`,
 * `*_video_title`) live on posts a lower-privilege user may be able to
 * edit. WordPress unserializes keyed meta on read, so every value that
 * could later hit a string/int cast is scalar-checked first — an injected
 * serialized object is flattened to '' before it can reach __toString().
 *
 * @package VideoFlowCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'vfaudit_core_get_course_videos' ) ) {

	/**
	 * A single post-meta value as a string — but '' unless it is scalar.
	 * Neutralises a serialized-object meta value before any string cast.
	 */
	function vfaudit_core_meta_string( int $post_id, string $key ): string {
		$value = get_post_meta( $post_id, $key, true );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * A multi-value post-meta key as a list of non-empty strings, dropping
	 * any non-scalar entry.
	 *
	 * @return string[]
	 */
	function vfaudit_core_meta_string_list( int $post_id, string $key ): array {
		$out = array();
		foreach ( (array) get_post_meta( $post_id, $key, false ) as $value ) {
			if ( is_scalar( $value ) && '' !== (string) $value ) {
				$out[] = (string) $value;
			}
		}
		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>> One row per (video_id, post_id).
	 */
	function vfaudit_core_get_course_videos( int $course_id ): array {

		if ( ! vfaudit_core_has_adapter() ) {
			vfaudit_core_log( 'vfaudit_core_get_course_videos called with no LMS adapter registered' );
			return array();
		}

		$course_post = get_post( $course_id );
		if ( ! $course_post || get_post_type( $course_post ) !== vfaudit_core_adapter_course_post_type() ) {
			return array();
		}

		vfaudit_core_log_debug( "scan course_id={$course_id}" );

		// --- Collect the posts to scan ---------------------------------------
		$posts   = array( $course_post );
		$seen_id = array( $course_id => true );

		foreach ( vfaudit_core_adapter_get_content_post_ids( $course_id ) as $pid ) {
			if ( isset( $seen_id[ $pid ] ) ) {
				continue;
			}
			$p = get_post( $pid );
			if ( $p ) {
				$seen_id[ $pid ] = true;
				$posts[]         = $p;
			}
		}

		// --- Detect ---------------------------------------------------------
		$videos = array();
		foreach ( $posts as $post ) {
			$used_in = vfaudit_core_adapter_used_in_label( (int) $post->ID, $course_id );

			$videos = array_merge(
				$videos,
				vfaudit_core_scan_video_meta( $post, $used_in ),
				vfaudit_core_scan_post_content( $post, $used_in ),
				vfaudit_core_scan_oembed_meta( $post, $used_in ),
				vfaudit_core_scan_bunny_meta( $post, $used_in )
			);
		}

		// --- Deduplicate: one row per (video_id, post_id) -------------------
		$videos = vfaudit_core_dedupe_videos( $videos );

		// --- Classify ------------------------------------------------------
		foreach ( $videos as &$video ) {
			$provider        = $video['provider'] ?? 'bunny';
			$video['type']   = $provider;
			$video['status'] = $video['status'] ?? 'ready';
			$video['error']  = $video['error'] ?? null;

			if ( 'bunny' === $provider ) {
				$post_id      = (int) ( $video['post_id'] ?? 0 );
				$video_id_str = (string) ( $video['video_id'] ?? '' );
				$owned_ids    = vfaudit_core_meta_string_list( $post_id, 'bunny_video_id' );
				$is_owned     = '' !== $video_id_str && in_array( $video_id_str, $owned_ids, true );

				$video['managed'] = $is_owned;
				$video['placed']  = $is_owned
					? vfaudit_core_video_is_embedded_in_post( $video_id_str, $post_id )
					: null;
			} else {
				$video['managed'] = false;
				$video['placed']  = $video['placed'] ?? null;
			}
		}
		unset( $video );

		/**
		 * Enrich rows with data Core cannot obtain on its own (live Bunny
		 * title / duration / views / transcode status). The paid Video Flow
		 * plugin hooks this; the free audit plugin does not.
		 *
		 * @param array<int,array<string,mixed>> $videos
		 * @param int                            $course_id
		 */
		$videos = apply_filters( 'vfaudit_core_enrich_videos', $videos, $course_id );

		// --- Normalise filename for display ------------------------------
		foreach ( $videos as &$video ) {
			$raw = (string) ( $video['filename'] ?? $video['title'] ?? '' );
			if ( false !== strpos( $raw, '/' ) ) {
				$parts = explode( '/', $raw );
				$raw   = (string) end( $parts );
			}
			$video['filename'] = (string) preg_replace( '/\.[a-z0-9]{2,4}$/i', '', $raw );
		}
		unset( $video );

		vfaudit_core_log_debug( "scan course_id={$course_id} -> " . count( $videos ) . ' videos' );

		return array_values( $videos );
	}

	/**
	 * Cached wrapper for display callers. 5-minute transient.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	function vfaudit_core_get_course_videos_cached( int $course_id ): array {
		// Never cache (or scan) for anything that is not a real course post —
		// keeps a CSRF'd ?course_id=<n> loop from churning the options table.
		if ( ! vfaudit_core_has_adapter() || get_post_type( $course_id ) !== vfaudit_core_adapter_course_post_type() ) {
			return array();
		}

		$key    = 'vfaudit_course_videos_' . $course_id;
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}
		$videos = vfaudit_core_get_course_videos( $course_id );
		set_transient( $key, $videos, 5 * MINUTE_IN_SECONDS );
		return $videos;
	}

	/**
	 * Drop the cached video list for the course that owns $post_id
	 * (a lesson, topic, or the course itself).
	 */
	function vfaudit_core_invalidate_course_videos_cache( int $post_id ): void {
		if ( $post_id <= 0 ) {
			return;
		}
		$course_id = vfaudit_core_adapter_resolve_course_id( $post_id );
		if ( $course_id <= 0 ) {
			$course_id = $post_id;
		}
		delete_transient( 'vfaudit_course_videos_' . $course_id );
	}

	/*
	|--------------------------------------------------------------------------
	| Detection passes
	|--------------------------------------------------------------------------
	*/

	/**
	 * Pass 1 — the `_video` meta modal (Tutor-style video sources).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	function vfaudit_core_scan_video_meta( object $post, string $used_in ): array {
		$vm = get_post_meta( $post->ID, '_video', true );
		if ( ! is_array( $vm ) ) {
			return array();
		}

		// Flatten any non-scalar entry (e.g. an injected serialized object,
		// which get_post_meta() unserializes) to '' so nothing downstream can
		// trigger __toString() on attacker-controlled data.
		$vm = array_map(
			static function ( $value ) {
				return is_scalar( $value ) ? $value : '';
			},
			$vm
		);

		$source = (string) ( $vm['source'] ?? '' );
		$rows   = array();
		$base   = array(
			'post_id'    => (int) $post->ID,
			'post_title' => (string) $post->post_title,
			'post_type'  => (string) $post->post_type,
			'used_in'    => $used_in,
		);

		// Vimeo.
		$vimeo_raw = '';
		if ( 'vimeo' === $source && ! empty( $vm['source_vimeo'] ) ) {
			$vimeo_raw = (string) $vm['source_vimeo'];
		} elseif ( ! empty( $vm['source_url'] ) && false !== stripos( (string) $vm['source_url'], 'vimeo.com' ) ) {
			$vimeo_raw = (string) $vm['source_url'];
		}
		foreach ( vfaudit_core_extract_vimeo_ids( $vimeo_raw ) as $vid ) {
			$rows[] = vfaudit_core_vimeo_row( $base, $vid, $post );
		}
		if ( ! empty( $vm['source_embedded'] ) ) {
			$vid = vfaudit_core_extract_vimeo_id_from_iframe( (string) $vm['source_embedded'] );
			if ( null !== $vid ) {
				$rows[] = vfaudit_core_vimeo_row( $base, $vid, $post );
			}
		}

		// YouTube.
		$yt_raw = '';
		if ( 'youtube' === $source && ! empty( $vm['source_youtube'] ) ) {
			$yt_raw = (string) $vm['source_youtube'];
		} elseif ( ! empty( $vm['source_url'] ) && ( false !== stripos( (string) $vm['source_url'], 'youtube' ) || false !== stripos( (string) $vm['source_url'], 'youtu.be' ) ) ) {
			$yt_raw = (string) $vm['source_url'];
		}
		if ( '' !== $yt_raw ) {
			$yid = vfaudit_core_extract_youtube_id( $yt_raw );
			if ( null !== $yid ) {
				$rows[] = vfaudit_core_youtube_row( $base, $yid );
			}
		}

		// Bunny entered directly as a mediadelivery.net URL.
		if ( ! empty( $vm['source_url'] ) ) {
			$guid = vfaudit_core_extract_bunny_guid( (string) $vm['source_url'] );
			if ( null !== $guid ) {
				$rows[] = vfaudit_core_bunny_row( $base, $guid, $post );
			}
		}
		if ( ! empty( $vm['source_embedded'] ) ) {
			foreach ( vfaudit_core_extract_bunny_guids( (string) $vm['source_embedded'] ) as $guid ) {
				$rows[] = vfaudit_core_bunny_row( $base, $guid, $post );
			}
		}

		// Local / self-hosted attachment.
		$attachment_id = (int) ( $vm['source_video_id'] ?? 0 );
		if ( in_array( $source, array( 'html5', 'self_hosted' ), true ) && $attachment_id > 0 ) {
			$rows[] = vfaudit_core_local_row_from_attachment( $base, $attachment_id );
		} elseif ( ! empty( $vm['source_url'] ) ) {
			$local = vfaudit_core_local_row_from_url( $base, (string) $vm['source_url'] );
			if ( null !== $local ) {
				$rows[] = $local;
			}
		}

		return $rows;
	}

	/**
	 * Pass 2 — raw references in `post_content`.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	function vfaudit_core_scan_post_content( object $post, string $used_in ): array {
		$content = (string) $post->post_content;
		if ( '' === $content ) {
			return array();
		}

		$base = array(
			'post_id'    => (int) $post->ID,
			'post_title' => (string) $post->post_title,
			'post_type'  => (string) $post->post_type,
			'used_in'    => $used_in,
		);

		$rows = array();

		foreach ( vfaudit_core_extract_vimeo_ids( $content ) as $vid ) {
			$rows[] = vfaudit_core_vimeo_row( $base, $vid, $post );
		}
		foreach ( vfaudit_core_extract_youtube_ids( $content ) as $yid ) {
			$rows[] = vfaudit_core_youtube_row( $base, $yid );
		}
		foreach ( vfaudit_core_extract_bunny_guids( $content ) as $guid ) {
			$rows[] = vfaudit_core_bunny_row( $base, $guid, $post );
		}

		$upload_baseurl = (string) wp_upload_dir()['baseurl'];
		if ( '' !== $upload_baseurl && false !== strpos( $content, $upload_baseurl ) ) {
			foreach ( vfaudit_core_extract_local_video_srcs( $content ) as $src ) {
				if ( 0 !== strpos( $src, $upload_baseurl ) ) {
					continue;
				}
				$local = vfaudit_core_local_row_from_url( $base, $src );
				if ( null !== $local ) {
					$rows[] = $local;
				}
			}
		}

		return $rows;
	}

	/**
	 * Pass 3 — the WordPress oEmbed cache (`_oembed_*` meta).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	function vfaudit_core_scan_oembed_meta( object $post, string $used_in ): array {
		$base = array(
			'post_id'    => (int) $post->ID,
			'post_title' => (string) $post->post_title,
			'post_type'  => (string) $post->post_type,
			'used_in'    => $used_in,
		);

		$rows = array();
		foreach ( get_post_meta( $post->ID ) as $key => $values ) {
			if ( 0 !== strpos( (string) $key, '_oembed_' ) || 0 === strpos( (string) $key, '_oembed_time_' ) ) {
				continue;
			}
			$cached = is_array( $values ) ? (string) ( $values[0] ?? '' ) : (string) $values;
			if ( '' === $cached ) {
				continue;
			}

			$vid = vfaudit_core_extract_vimeo_id_from_iframe( $cached );
			if ( null !== $vid ) {
				$row                = vfaudit_core_vimeo_row( $base, $vid, $post );
				$row['from_oembed'] = true;
				$rows[]             = $row;
			}
			foreach ( vfaudit_core_extract_youtube_ids( $cached ) as $yid ) {
				$row                = vfaudit_core_youtube_row( $base, $yid );
				$row['from_oembed'] = true;
				$rows[]             = $row;
			}
			foreach ( vfaudit_core_extract_bunny_guids( $cached ) as $guid ) {
				$rows[] = vfaudit_core_bunny_row( $base, $guid, $post );
			}
		}

		return $rows;
	}

	/**
	 * Pass 4 — Bunny videos this plugin family uploaded (`bunny_video_id` meta).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	function vfaudit_core_scan_bunny_meta( object $post, string $used_in ): array {
		$base = array(
			'post_id'    => (int) $post->ID,
			'post_title' => (string) $post->post_title,
			'post_type'  => (string) $post->post_type,
			'used_in'    => $used_in,
		);

		$rows = array();
		foreach ( vfaudit_core_meta_string_list( (int) $post->ID, 'bunny_video_id' ) as $guid ) {
			$rows[] = vfaudit_core_bunny_row( $base, $guid, $post );
		}
		return $rows;
	}

	/*
	|--------------------------------------------------------------------------
	| Row builders
	|--------------------------------------------------------------------------
	*/

	/**
	 * @param array<string,mixed> $base
	 * @return array<string,mixed>
	 */
	function vfaudit_core_vimeo_row( array $base, string $vimeo_id, object $post ): array {
		$title = vfaudit_core_meta_string( (int) $post->ID, 'vimeo_video_title' );
		return array_merge(
			$base,
			array(
				'video_id'  => $vimeo_id,
				'provider'  => 'vimeo',
				'title'     => '' !== $title ? $title : 'Vimeo video (' . $vimeo_id . ')',
				'filename'  => '' !== $title ? $title : 'Vimeo video (' . $vimeo_id . ')',
				'duration'  => 0,
				'exists'    => true,
				'status'    => 'ready',
				'embed_url' => 'https://player.vimeo.com/video/' . $vimeo_id,
			)
		);
	}

	/**
	 * @param array<string,mixed> $base
	 * @return array<string,mixed>
	 */
	function vfaudit_core_youtube_row( array $base, string $youtube_id ): array {
		return array_merge(
			$base,
			array(
				'video_id'  => $youtube_id,
				'provider'  => 'youtube',
				'title'     => 'YouTube video (' . $youtube_id . ')',
				'filename'  => 'YouTube video (' . $youtube_id . ')',
				'duration'  => 0,
				'exists'    => true,
				'status'    => 'ready',
				'embed_url' => 'https://www.youtube.com/watch?v=' . $youtube_id,
			)
		);
	}

	/**
	 * @param array<string,mixed> $base
	 * @return array<string,mixed>
	 */
	function vfaudit_core_bunny_row( array $base, string $guid, object $post ): array {
		$title = vfaudit_core_meta_string( (int) $post->ID, 'bunny_video_title' );
		return array_merge(
			$base,
			array(
				'video_id' => $guid,
				'provider' => 'bunny',
				'title'    => '' !== $title ? $title : 'Bunny video (' . substr( $guid, 0, 8 ) . ')',
				'filename' => '' !== $title ? $title : 'Bunny video (' . substr( $guid, 0, 8 ) . ')',
				'duration' => 0,
				'exists'   => true,
				'status'   => 'ready',
			)
		);
	}

	/**
	 * @param array<string,mixed> $base
	 * @return array<string,mixed>
	 */
	function vfaudit_core_local_row_from_attachment( array $base, int $attachment_id ): array {
		$file  = get_attached_file( $attachment_id );
		$title = (string) get_the_title( $attachment_id );
		if ( '' === $title && $file ) {
			$title = basename( $file );
		}

		return array_merge(
			$base,
			array(
				'video_id' => (string) $attachment_id,
				'provider' => 'local',
				'title'    => $title,
				'filename' => $title,
				'duration' => vfaudit_core_read_local_duration( $file ?: '' ),
				'exists'   => (bool) ( $file && file_exists( $file ) ),
				'status'   => 'ready',
			)
		);
	}

	/**
	 * @param array<string,mixed> $base
	 * @return array<string,mixed>|null Null when the URL is not under this site's uploads.
	 */
	function vfaudit_core_local_row_from_url( array $base, string $src ): ?array {
		$upload_baseurl = (string) wp_upload_dir()['baseurl'];
		if ( '' === $upload_baseurl || 0 !== strpos( $src, $upload_baseurl ) ) {
			return null;
		}

		$attachment_id = attachment_url_to_postid( $src );
		if ( $attachment_id > 0 ) {
			return vfaudit_core_local_row_from_attachment( $base, $attachment_id );
		}

		$path  = wp_parse_url( $src, PHP_URL_PATH );
		$title = basename( is_string( $path ) ? $path : $src );

		return array_merge(
			$base,
			array(
				'video_id' => md5( $src ),
				'provider' => 'local',
				'title'    => $title,
				'filename' => $title,
				'duration' => 0,
				'exists'   => true,
				'status'   => 'ready',
			)
		);
	}

	/**
	 * Read a local video file's duration in whole seconds (0 when unavailable).
	 */
	function vfaudit_core_read_local_duration( string $file ): int {
		if ( '' === $file || ! file_exists( $file ) ) {
			return 0;
		}
		if ( ! function_exists( 'wp_read_video_metadata' ) ) {
			$media = ABSPATH . 'wp-admin/includes/media.php';
			if ( ! is_readable( $media ) ) {
				return 0;
			}
			require_once $media;
		}
		$meta = wp_read_video_metadata( $file );
		return is_array( $meta ) ? (int) ( $meta['length'] ?? 0 ) : 0;
	}

	/*
	|--------------------------------------------------------------------------
	| Deduplication
	|--------------------------------------------------------------------------
	*/

	/**
	 * Collapse to one row per (video_id, post_id), keeping the richest source.
	 * Priority: a real embedded reference beats an oEmbed-cache-only one;
	 * otherwise first-seen wins.
	 *
	 * @param array<int,array<string,mixed>> $videos
	 * @return array<int,array<string,mixed>>
	 */
	function vfaudit_core_dedupe_videos( array $videos ): array {
		$map = array();
		foreach ( $videos as $video ) {
			$vid = (string) ( $video['video_id'] ?? '' );
			if ( '' === $vid ) {
				continue;
			}
			$key = $vid . '|' . (int) ( $video['post_id'] ?? 0 );

			if ( ! isset( $map[ $key ] ) ) {
				$map[ $key ] = $video;
				continue;
			}

			// Prefer a row that is NOT oEmbed-cache-only.
			$existing_cache_only = ! empty( $map[ $key ]['from_oembed'] );
			$incoming_cache_only = ! empty( $video['from_oembed'] );
			if ( $existing_cache_only && ! $incoming_cache_only ) {
				$map[ $key ] = $video;
			}
		}

		return array_values( $map );
	}
}
