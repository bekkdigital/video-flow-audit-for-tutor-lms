<?php
/**
 * Video Flow Core — LMS adapter registry.
 *
 * The scanner is LMS-agnostic. Everything specific to a particular LMS
 * (post types, course -> lesson/topic hierarchy, curriculum ordering) is
 * supplied by an adapter that the host plugin registers on init.
 *
 * An adapter is a plain associative array — no classes, matching the rest
 * of the Video Flow family:
 *
 *   vfaudit_core_register_lms_adapter( 'tutor', array(
 *       'label'                => 'Tutor LMS',
 *       'course_post_type'     => 'courses',
 *       'content_post_types'   => array( 'lesson', 'topics' ),
 *       'get_content_post_ids' => function ( int $course_id ): array { ... },
 *       'resolve_course_id'    => function ( int $post_id ): int { ... },
 *       // optional:
 *       'get_used_in_label'    => function ( int $post_id, int $course_id ): string { ... },
 *       'list_course_ids'      => function ( int $author_id ): array { ... },
 *       'get_video_field_text' => function ( WP_Post $post ): array { ... },
 *       'get_attachment_ids'   => function ( WP_Post $post ): array { ... },
 *   ) );
 *
 * `get_video_field_text` exposes the LMS's own "lesson video" setting when
 * it is stored somewhere Core cannot know about (e.g. LearnDash keeps it in
 * a serialized `_sfwd-lessons` array). Return the raw stored value(s) — a
 * URL, [embed] shortcode or <iframe> — as strings; Core scans them with the
 * same extractors as post_content. Tutor's `_video` meta is still scanned
 * natively and needs no callback.
 *
 * `get_attachment_ids` returns the Media Library attachment IDs the LMS
 * attaches to a post outside its content (e.g. Tutor's `_tutor_attachments`).
 * The image scanner reports image attachments and counts the rest.
 *
 * @package VideoFlowCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'vfaudit_core_register_lms_adapter' ) ) {

	/**
	 * Register (or replace) an LMS adapter. The most recently registered
	 * adapter is the active one — in practice each site runs exactly one
	 * Video Flow LMS plugin, so there is only ever one.
	 *
	 * @param string               $key     Short adapter key, e.g. 'tutor'.
	 * @param array<string,mixed>   $adapter Adapter definition (see file docblock).
	 */
	function vfaudit_core_register_lms_adapter( string $key, array $adapter ): void {
		$adapter['key'] = $key;

		$required = array( 'course_post_type', 'content_post_types', 'get_content_post_ids', 'resolve_course_id' );
		foreach ( $required as $field ) {
			if ( ! isset( $adapter[ $field ] ) ) {
				vfaudit_core_log( "adapter '{$key}' missing required field '{$field}' — not registered" );
				return;
			}
		}

		$GLOBALS['vfaudit_core_lms_adapter'] = $adapter;
		vfaudit_core_log_debug( "LMS adapter registered: {$key}" );
	}

	/**
	 * @return array<string,mixed>|null The active adapter, or null if none registered.
	 */
	function vfaudit_core_get_adapter(): ?array {
		return $GLOBALS['vfaudit_core_lms_adapter'] ?? null;
	}

	/**
	 * @return bool Whether an LMS adapter is available.
	 */
	function vfaudit_core_has_adapter(): bool {
		return null !== vfaudit_core_get_adapter();
	}

	/**
	 * The course post type for the active LMS ('' if no adapter).
	 */
	function vfaudit_core_adapter_course_post_type(): string {
		$adapter = vfaudit_core_get_adapter();
		return $adapter ? (string) $adapter['course_post_type'] : '';
	}

	/**
	 * Post types that may hold a video, besides the course post itself.
	 *
	 * @return string[]
	 */
	function vfaudit_core_adapter_content_post_types(): array {
		$adapter = vfaudit_core_get_adapter();
		return $adapter ? array_values( (array) $adapter['content_post_types'] ) : array();
	}

	/**
	 * Ordered list of content post IDs (lessons/topics) belonging to a
	 * course's curriculum. Does NOT include the course post itself.
	 *
	 * @return int[]
	 */
	function vfaudit_core_adapter_get_content_post_ids( int $course_id ): array {
		$adapter = vfaudit_core_get_adapter();
		if ( ! $adapter ) {
			return array();
		}
		$ids = call_user_func( $adapter['get_content_post_ids'], $course_id );
		return array_values( array_unique( array_filter( array_map( 'intval', (array) $ids ) ) ) );
	}

	/**
	 * Reverse lookup: the course a given post belongs to (0 if none / unknown).
	 */
	function vfaudit_core_adapter_resolve_course_id( int $post_id ): int {
		$adapter = vfaudit_core_get_adapter();
		if ( ! $adapter ) {
			return 0;
		}
		return (int) call_user_func( $adapter['resolve_course_id'], $post_id );
	}

	/**
	 * "Course Title -> Lesson Title" label for the "Used in" column.
	 * Falls back to a generic implementation when the adapter does not
	 * provide one.
	 */
	function vfaudit_core_adapter_used_in_label( int $post_id, int $course_id ): string {
		$adapter = vfaudit_core_get_adapter();

		if ( $adapter && isset( $adapter['get_used_in_label'] ) ) {
			return (string) call_user_func( $adapter['get_used_in_label'], $post_id, $course_id );
		}

		if ( $post_id === $course_id ) {
			return (string) get_the_title( $course_id );
		}

		$course_title = $course_id ? (string) get_the_title( $course_id ) : '';
		return trim( $course_title . ' → ' . (string) get_the_title( $post_id ), ' →' );
	}

	/**
	 * Raw text of the LMS's own video field(s) for a post, via the adapter's
	 * optional `get_video_field_text` callback. Non-scalar / empty entries
	 * are dropped (the callback may read untrusted post meta).
	 *
	 * @return string[]
	 */
	function vfaudit_core_adapter_video_field_text( object $post ): array {
		$adapter = vfaudit_core_get_adapter();
		if ( ! $adapter || ! isset( $adapter['get_video_field_text'] ) ) {
			return array();
		}

		$out = array();
		foreach ( (array) call_user_func( $adapter['get_video_field_text'], $post ) as $value ) {
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				$out[] = (string) $value;
			}
		}
		return $out;
	}

	/**
	 * Attachment IDs the LMS attaches to a post, via the adapter's optional
	 * `get_attachment_ids` callback. Non-scalar / non-positive entries are
	 * dropped (the callback may read untrusted post meta).
	 *
	 * @return int[]
	 */
	function vfaudit_core_adapter_attachment_ids( object $post ): array {
		$adapter = vfaudit_core_get_adapter();
		if ( ! $adapter || ! isset( $adapter['get_attachment_ids'] ) ) {
			return array();
		}

		$out = array();
		foreach ( (array) call_user_func( $adapter['get_attachment_ids'], $post ) as $value ) {
			if ( is_scalar( $value ) && (int) $value > 0 ) {
				$out[] = (int) $value;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * All course IDs for the active LMS, optionally filtered by author.
	 *
	 * @return int[]
	 */
	function vfaudit_core_adapter_list_course_ids( int $author_id = 0 ): array {
		$adapter = vfaudit_core_get_adapter();
		if ( ! $adapter ) {
			return array();
		}

		if ( isset( $adapter['list_course_ids'] ) ) {
			$ids = call_user_func( $adapter['list_course_ids'], $author_id );
			return array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
		}

		$args = array(
			'post_type'      => (string) $adapter['course_post_type'],
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft', 'private', 'future' ),
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);
		if ( $author_id > 0 ) {
			$args['author'] = $author_id;
		}

		return array_values( array_filter( array_map( 'intval', (array) get_posts( $args ) ) ) );
	}
}
