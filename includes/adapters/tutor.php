<?php
/**
 * Tutor LMS adapter for Video Flow Core.
 *
 * Teaches the LMS-agnostic Core scanner about Tutor's course hierarchy:
 *
 *   Course (post_type=courses)
 *   └─ Topic  (post_type=topics, post_parent=course_id)
 *      └─ Lesson (post_type=lesson, post_parent=topic_id)
 *
 * Lessons can also be linked to a course purely by the
 * `_tutor_course_id_for_lesson` meta, with no post_parent chain — both
 * shapes are handled here.
 *
 * @package VideoFlowAuditForTutorLMS
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'vfaudit_core_register_lms_adapter' ) || ! function_exists( 'tutor' ) ) {
			return;
		}

		vfaudit_core_register_lms_adapter(
			'tutor',
			array(
				'label'                => 'Tutor LMS',
				'course_post_type'     => 'courses',
				'content_post_types'   => array( 'lesson', 'topics' ),
				'get_content_post_ids' => 'vfaudit_tutor_content_post_ids',
				'resolve_course_id'    => 'vfaudit_tutor_resolve_course_id',
				'get_used_in_label'    => 'vfaudit_tutor_used_in_label',
				'list_course_ids'      => 'vfaudit_tutor_list_course_ids',
			)
		);
	},
	// After Core's own loader (plugins_loaded priority 3).
	5
);

/**
 * All lesson/topic post IDs that belong to a course's curriculum.
 *
 * Mirrors the discovery the paid plugin's scanner did inline: meta link,
 * direct parent, topic parent — then filtered against Tutor's live
 * curriculum so lessons removed in the course builder (but still carrying
 * stale meta) drop out.
 *
 * @return int[]
 */
function vfaudit_tutor_content_post_ids( int $course_id ): array {
	$statuses = array( 'publish', 'draft', 'private', 'future', 'pending' );
	$ids      = array();

	// Lessons linked by meta.
	$ids = array_merge(
		$ids,
		get_posts(
			array(
				'post_type'      => 'lesson',
				'posts_per_page' => -1,
				'post_status'    => $statuses,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- scoped to one course's lessons.
				'meta_query'     => array(
					array(
						'key'   => '_tutor_course_id_for_lesson',
						'value' => $course_id,
					),
				),
			)
		)
	);

	// Lessons parented directly to the course (no topic layer).
	$ids = array_merge(
		$ids,
		get_posts(
			array(
				'post_type'      => 'lesson',
				'posts_per_page' => -1,
				'post_status'    => $statuses,
				'fields'         => 'ids',
				'post_parent'    => $course_id,
			)
		)
	);

	// Topics of the course, and lessons under those topics.
	$topic_ids = get_posts(
		array(
			'post_type'      => 'topics',
			'posts_per_page' => -1,
			'post_status'    => $statuses,
			'fields'         => 'ids',
			'post_parent'    => $course_id,
		)
	);
	$ids       = array_merge( $ids, $topic_ids );

	if ( ! empty( $topic_ids ) ) {
		$ids = array_merge(
			$ids,
			get_posts(
				array(
					'post_type'       => 'lesson',
					'posts_per_page'  => -1,
					'post_status'     => $statuses,
					'fields'          => 'ids',
					'post_parent__in' => $topic_ids,
				)
			)
		);
	}

	$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );

	// Filter against Tutor's live curriculum (keeps topics + curriculum lessons).
	if ( function_exists( 'tutor_utils' ) ) {
		$contents = tutor_utils()->get_course_contents_by_id( $course_id );
		if ( is_array( $contents ) && ! empty( $contents ) ) {
			$curriculum = array_map( 'intval', wp_list_pluck( (array) $contents, 'ID' ) );
			$ids        = array_values(
				array_filter(
					$ids,
					static function ( $id ) use ( $curriculum ) {
						return in_array( (int) $id, $curriculum, true );
					}
				)
			);
		}
	}

	return $ids;
}

/**
 * The course a lesson/topic belongs to (0 if unknown).
 */
function vfaudit_tutor_resolve_course_id( int $post_id ): int {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return 0;
	}
	if ( 'courses' === $post->post_type ) {
		return $post_id;
	}
	if ( 'lesson' === $post->post_type ) {
		$topic = get_post( (int) $post->post_parent );
		if ( $topic && 'topics' === $topic->post_type ) {
			return (int) $topic->post_parent;
		}
	}
	if ( 'topics' === $post->post_type ) {
		$course = get_post( (int) $post->post_parent );
		if ( $course && 'courses' === $course->post_type ) {
			return (int) $course->ID;
		}
	}
	$meta_cid = (int) get_post_meta( $post_id, '_tutor_course_id_for_lesson', true );
	return $meta_cid > 0 ? $meta_cid : 0;
}

/**
 * "Course Title → Lesson Title" for the "Used in" column.
 */
function vfaudit_tutor_used_in_label( int $post_id, int $course_id ): string {
	if ( $post_id === $course_id ) {
		return (string) get_the_title( $course_id );
	}

	$lesson_course_id = (int) get_post_meta( $post_id, '_tutor_course_id_for_lesson', true );
	if ( ! $lesson_course_id ) {
		$lesson_course_id = $course_id;
	}

	$course_title = $lesson_course_id ? (string) get_the_title( $lesson_course_id ) : '';
	$post_title   = (string) get_the_title( $post_id );

	return '' !== $course_title ? $course_title . ' → ' . $post_title : $post_title;
}

/**
 * All Tutor course IDs, optionally filtered by author.
 *
 * @return int[]
 */
function vfaudit_tutor_list_course_ids( int $author_id = 0 ): array {
	$args = array(
		'post_type'      => 'courses',
		'posts_per_page' => -1,
		'post_status'    => array( 'publish', 'draft', 'private', 'future' ),
		'fields'         => 'ids',
		'orderby'        => 'title',
		'order'          => 'ASC',
	);
	if ( $author_id > 0 ) {
		$args['author'] = $author_id;
	}
	return array_values( array_filter( array_map( 'intval', (array) get_posts( $args ) ) ) );
}
