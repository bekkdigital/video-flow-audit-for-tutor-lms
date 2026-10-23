<?php
/**
 * Video Flow Core — read-only course image scanner.
 *
 * Scans the course post and its curriculum posts (same post set as the
 * video scanner) for:
 *   - featured image   (_thumbnail_id)
 *   - LMS attachments  (via the adapter's optional `get_attachment_ids`)
 *   - <img> tags in post_content
 *
 * No HTTP, no DB writes.
 *
 * @package VideoFlowCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'VFAUDIT_CORE_LARGE_IMAGE_BYTES' ) ) {
	// Images above this size are flagged "Large file".
	define( 'VFAUDIT_CORE_LARGE_IMAGE_BYTES', 500 * 1024 );
}

if ( ! function_exists( 'vfaudit_core_get_course_images' ) ) {

	/**
	 * Every image used by a course.
	 *
	 * Returns:
	 *   'images'      => rows keyed by attachment ID (or 'ext:<md5>' for images
	 *                    that don't resolve to a Media Library item):
	 *                      attachment_id, url, file, width, height, filesize,
	 *                      alt, is_external, used_in[] (post_id, post_title,
	 *                      post_type, role)
	 *   'other_files' => int (non-image attachments, counted only)
	 *
	 * One image used in several places gets one row with several used_in
	 * entries.
	 *
	 * @return array{images:array<string,array<string,mixed>>,other_files:int}
	 */
	function vfaudit_core_get_course_images( int $course_id ): array {
		$empty = array(
			'images'      => array(),
			'other_files' => 0,
		);

		if ( ! vfaudit_core_has_adapter() ) {
			vfaudit_core_log( 'vfaudit_core_get_course_images called with no LMS adapter registered' );
			return $empty;
		}

		$course_post = get_post( $course_id );
		if ( ! $course_post || get_post_type( $course_post ) !== vfaudit_core_adapter_course_post_type() ) {
			return $empty;
		}

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

		$images      = array();
		$other_files = array();

		foreach ( $posts as $post ) {
			$post_title = (string) get_post_field( 'post_title', $post->ID );

			$thumb_id = (int) vfaudit_core_meta_string( (int) $post->ID, '_thumbnail_id' );
			if ( $thumb_id > 0 ) {
				vfaudit_core_add_course_image( $images, $thumb_id, '', $post, $post_title, 'featured' );
			}

			foreach ( vfaudit_core_adapter_attachment_ids( $post ) as $attachment_id ) {
				if ( ! wp_attachment_is_image( $attachment_id ) ) {
					if ( get_post( $attachment_id ) ) {
						$other_files[ $attachment_id ] = true;
					} else {
						vfaudit_core_log_debug( "course attachment skipped: attachment_id={$attachment_id} missing (post_id={$post->ID})" );
					}
					continue;
				}
				vfaudit_core_add_course_image( $images, $attachment_id, '', $post, $post_title, 'attachment' );
			}

			$content = (string) $post->post_content;
			if ( '' === $content || false === stripos( $content, '<img' ) ) {
				continue;
			}

			preg_match_all( '/<img\b[^>]*>/i', $content, $tags );
			foreach ( $tags[0] as $tag ) {
				if ( ! preg_match( '/\ssrc=["\']([^"\']+)["\']/i', $tag, $src_match ) ) {
					continue;
				}
				$src = html_entity_decode( $src_match[1] );
				vfaudit_core_add_course_image( $images, vfaudit_core_image_src_to_attachment_id( $tag, $src ), $src, $post, $post_title, 'content' );
			}
		}

		vfaudit_core_log_debug( "course images course_id={$course_id} images=" . count( $images ) . ' other_files=' . count( $other_files ) );

		return array(
			'images'      => $images,
			'other_files' => count( $other_files ),
		);
	}

	/**
	 * Resolve an <img> tag to a Media Library attachment ID (0 if none):
	 * the wp-image-N class, then the URL, then the original of a sized
	 * variant (image-300x200.jpg), then the "-scaled" original WordPress
	 * stores for big uploads.
	 */
	function vfaudit_core_image_src_to_attachment_id( string $tag, string $src ): int {
		if ( preg_match( '/\bwp-image-(\d+)\b/', $tag, $id_match ) ) {
			return (int) $id_match[1];
		}

		$attachment_id = (int) attachment_url_to_postid( $src );
		if ( $attachment_id > 0 ) {
			return $attachment_id;
		}

		$original = (string) preg_replace( '/-\d+x\d+(?=\.[a-z0-9]+$)/i', '', (string) strtok( $src, '?' ) );
		if ( $original === $src ) {
			return 0;
		}

		$attachment_id = (int) attachment_url_to_postid( $original );
		if ( $attachment_id > 0 ) {
			return $attachment_id;
		}

		return (int) attachment_url_to_postid( (string) preg_replace( '/(\.[a-z0-9]+)$/i', '-scaled$1', $original ) );
	}

	/**
	 * Adds one usage to the image list, creating the image row on first sight.
	 * $attachment_id 0 means an image that is not in the Media Library — it is
	 * keyed by URL instead. A missing/deleted attachment ID is skipped and logged.
	 *
	 * @param array<string,array<string,mixed>> $images Image rows, by reference.
	 */
	function vfaudit_core_add_course_image( array &$images, int $attachment_id, string $src, object $post, string $post_title, string $role ): void {

		if ( $attachment_id > 0 ) {
			$attachment = get_post( $attachment_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				vfaudit_core_log_debug( "course image skipped: attachment_id={$attachment_id} missing (post_id={$post->ID} role={$role})" );
				return;
			}
			$key = (string) $attachment_id;
		} else {
			if ( '' === $src ) {
				return;
			}
			$key = 'ext:' . md5( $src );
		}

		if ( ! isset( $images[ $key ] ) ) {
			if ( $attachment_id > 0 ) {
				$meta     = wp_get_attachment_metadata( $attachment_id );
				$meta     = is_array( $meta ) ? $meta : array();
				$file     = get_attached_file( $attachment_id );
				$filesize = (int) ( $meta['filesize'] ?? 0 );
				if ( $filesize <= 0 && $file && file_exists( $file ) ) {
					$filesize = (int) filesize( $file );
				}

				$images[ $key ] = array(
					'attachment_id' => $attachment_id,
					'url'           => (string) wp_get_attachment_url( $attachment_id ),
					'file'          => $file ? wp_basename( $file ) : '',
					'width'         => (int) ( $meta['width'] ?? 0 ),
					'height'        => (int) ( $meta['height'] ?? 0 ),
					'filesize'      => $filesize,
					'alt'           => vfaudit_core_meta_string( $attachment_id, '_wp_attachment_image_alt' ),
					'is_external'   => false,
					'used_in'       => array(),
				);
			} else {
				$images[ $key ] = array(
					'attachment_id' => 0,
					'url'           => $src,
					'file'          => wp_basename( (string) strtok( $src, '?' ) ),
					'width'         => 0,
					'height'        => 0,
					'filesize'      => 0,
					'alt'           => '',
					'is_external'   => true,
					'used_in'       => array(),
				);
			}
		}

		$usage_key = $post->ID . ':' . $role;
		if ( ! isset( $images[ $key ]['used_in'][ $usage_key ] ) ) {
			$images[ $key ]['used_in'][ $usage_key ] = array(
				'post_id'    => (int) $post->ID,
				'post_title' => $post_title,
				'post_type'  => (string) $post->post_type,
				'role'       => $role,
			);
		}
	}

	/**
	 * Transient key for a course's image list. Includes a version number so
	 * a Media Library change can invalidate every course at once — one
	 * attachment may be used in many courses.
	 */
	function vfaudit_core_course_images_cache_key( int $course_id ): string {
		return 'vfaudit_course_images_' . (int) get_option( 'vfaudit_course_images_cache_ver', 1 ) . '_' . $course_id;
	}

	/**
	 * Cached wrapper for display callers. 5-minute transient.
	 *
	 * @return array{images:array<string,array<string,mixed>>,other_files:int}
	 */
	function vfaudit_core_get_course_images_cached( int $course_id ): array {
		// Never cache (or scan) for anything that is not a real course post.
		if ( ! vfaudit_core_has_adapter() || get_post_type( $course_id ) !== vfaudit_core_adapter_course_post_type() ) {
			return array(
				'images'      => array(),
				'other_files' => 0,
			);
		}

		$key    = vfaudit_core_course_images_cache_key( $course_id );
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$result = vfaudit_core_get_course_images( $course_id );
		set_transient( $key, $result, 5 * MINUTE_IN_SECONDS );
		return $result;
	}
}
