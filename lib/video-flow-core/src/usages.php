<?php
/**
 * Video Flow Core — "where is this video used" lookups.
 *
 * Read-only. Used to show, in the audit view, that the same underlying
 * video appears in more than one lesson/course, and to flag a tracked
 * Bunny video that isn't actually embedded anywhere (orphan).
 *
 * @package VideoFlowCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'vfaudit_core_get_video_usages' ) ) {

	/**
	 * Every non-trashed post that has a given Bunny video attached via the
	 * plugin-owned `bunny_video_id` meta.
	 *
	 * @param string $video_id Bunny video GUID.
	 * @return array<int,array{course_id:int,course_title:string,lesson_id:int,lesson_title:string}>
	 */
	function vfaudit_core_get_video_usages( string $video_id ): array {
		global $wpdb;

		if ( '' === $video_id ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off "find posts by meta value" lookup, no WP API for it.
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'bunny_video_id' AND meta_value = %s",
				$video_id
			)
		);

		$course_pt = vfaudit_core_adapter_course_post_type();
		$usages    = array();

		foreach ( $post_ids as $raw_id ) {
			$post_id = (int) $raw_id;
			$status  = get_post_status( $post_id );
			if ( ! $status || 'trash' === $status ) {
				continue;
			}

			$course_id = (int) get_post_meta( $post_id, 'bunny_course_id', true );
			if ( ! $course_id ) {
				$course_id = vfaudit_core_adapter_resolve_course_id( $post_id );
			}
			$is_course_level = ( $post_id === $course_id ) || ( $course_pt && get_post_type( $post_id ) === $course_pt );

			$usages[] = array(
				'course_id'    => $course_id,
				'course_title' => $course_id ? (string) get_the_title( $course_id ) : '',
				'lesson_id'    => $is_course_level ? 0 : $post_id,
				'lesson_title' => $is_course_level ? '' : (string) get_the_title( $post_id ),
			);
		}

		return $usages;
	}

	/**
	 * Whether a video ID actually appears in a post's visible fields
	 * (post_content or the Tutor-style `_video` modal meta) — not merely
	 * in the plugin's own tracking meta.
	 */
	function vfaudit_core_video_is_embedded_in_post( string $video_id, int $post_id ): bool {
		if ( '' === $video_id ) {
			return false;
		}

		$content = (string) get_post_field( 'post_content', $post_id );
		if ( '' !== $content && false !== strpos( $content, $video_id ) ) {
			return true;
		}

		$video_meta = get_post_meta( $post_id, '_video', true );
		if ( is_array( $video_meta ) ) {
			$encoded = wp_json_encode( $video_meta );
			if ( is_string( $encoded ) && false !== strpos( $encoded, $video_id ) ) {
				return true;
			}
		}

		return false;
	}
}
