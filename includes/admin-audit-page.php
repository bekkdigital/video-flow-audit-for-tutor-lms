<?php
/**
 * The "Video Audit" admin screen — a read-only view of every video in
 * every Tutor LMS course, powered by Video Flow Core's scanner.
 *
 * No migration, no Bunny API, no write actions. From here the user is
 * pointed at the paid "Video Flow for Tutor LMS" to actually migrate.
 *
 * @package VideoFlowAuditForTutorLMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the screen's CSS/JS, only on our page.
 */
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$our_hook = $GLOBALS['vfaudit_page_hook'] ?? ( 'tutor_page_' . VFAUDIT_MENU_SLUG );
		if ( $hook !== $our_hook ) {
			return;
		}
		$base = plugin_dir_url( VFAUDIT_PLUGIN_FILE ) . 'assets/';
		wp_enqueue_style( 'vfaudit-admin', $base . 'audit.css', array(), VFAUDIT_VERSION );
		wp_enqueue_script( 'vfaudit-admin', $base . 'audit.js', array(), VFAUDIT_VERSION, true );
		wp_localize_script(
			'vfaudit-admin',
			'vfAudit',
			array(
				'copied'   => __( 'Copied', 'video-flow-audit-for-tutor-lms' ),
				'copyFull' => __( 'Copy full ID', 'video-flow-audit-for-tutor-lms' ),
			)
		);
	}
);

/**
 * Page router.
 */
function vfaudit_render_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'video-flow-audit-for-tutor-lms' ) );
	}

	if ( ! vfaudit_core_has_adapter() ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Video Audit', 'video-flow-audit-for-tutor-lms' ) . '</h1>';
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Tutor LMS was not detected. This plugin needs Tutor LMS to be active.', 'video-flow-audit-for-tutor-lms' ) . '</p></div></div>';
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view routing, no state change.
	$course_id = isset( $_GET['course_id'] ) ? absint( wp_unslash( $_GET['course_id'] ) ) : 0;

	echo '<div class="wrap vfaudit-wrap">';
	echo '<h1>' . esc_html__( 'Video Audit', 'video-flow-audit-for-tutor-lms' ) . '</h1>';

	if ( $course_id ) {
		vfaudit_render_course_detail( $course_id );
	} else {
		vfaudit_render_course_list();
	}

	vfaudit_render_upgrade_panel();
	echo '</div>';
}

/**
 * List of courses with a detected-video count.
 */
function vfaudit_render_course_list(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter.
	$author_id = isset( $_GET['author_id'] ) ? absint( wp_unslash( $_GET['author_id'] ) ) : 0;

	$rows     = vfaudit_core_get_courses_with_video_counts( $author_id );
	$base_url = admin_url( 'admin.php?page=' . VFAUDIT_MENU_SLUG . '&course_id=' );

	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off distinct-author lookup, no WP API for "authors of a post type".
	$author_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ('publish','draft','private','future')",
			'courses'
		)
	);
	$users      = $author_ids
		? get_users(
			array(
				'include' => array_map( 'intval', $author_ids ),
				'orderby' => 'display_name',
			)
		)
		: array();

	echo '<p class="vfaudit-lead">' . esc_html__( 'Every video detected across your Tutor LMS courses, wherever it is hosted.', 'video-flow-audit-for-tutor-lms' ) . '</p>';

	if ( $users ) {
		echo '<form method="get" class="vfaudit-filter">';
		echo '<input type="hidden" name="page" value="' . esc_attr( VFAUDIT_MENU_SLUG ) . '">';
		echo '<label for="vfaudit-author">' . esc_html__( 'Author', 'video-flow-audit-for-tutor-lms' ) . '</label> ';
		echo '<select id="vfaudit-author" name="author_id">';
		echo '<option value="0">' . esc_html__( 'All authors', 'video-flow-audit-for-tutor-lms' ) . '</option>';
		foreach ( $users as $user ) {
			echo '<option value="' . esc_attr( $user->ID ) . '" ' . selected( $author_id, (int) $user->ID, false ) . '>' . esc_html( $user->display_name ) . '</option>';
		}
		echo '</select> <button type="submit" class="button">' . esc_html__( 'Filter', 'video-flow-audit-for-tutor-lms' ) . '</button>';
		echo '</form>';
	}

	if ( empty( $rows ) ) {
		echo '<div class="vfaudit-empty">' . esc_html__( 'No videos found in any course yet.', 'video-flow-audit-for-tutor-lms' ) . '</div>';
		return;
	}

	echo '<table class="widefat striped vfaudit-table">';
	echo '<thead><tr>';
	echo '<th>' . esc_html__( 'Course', 'video-flow-audit-for-tutor-lms' ) . '</th>';
	echo '<th>' . esc_html__( 'Author', 'video-flow-audit-for-tutor-lms' ) . '</th>';
	echo '<th class="vfaudit-num">' . esc_html__( 'Videos', 'video-flow-audit-for-tutor-lms' ) . '</th>';
	echo '<th></th>';
	echo '</tr></thead><tbody>';

	foreach ( $rows as $row ) {
		$cid   = (int) $row->course_id;
		$title = get_the_title( $cid );
		if ( '' === $title ) {
			/* translators: %d: course ID. */
			$title = sprintf( __( '(Course #%d)', 'video-flow-audit-for-tutor-lms' ), $cid );
		}
		$author_name = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $cid ) );

		echo '<tr>';
		echo '<td><strong>' . esc_html( $title ) . '</strong></td>';
		echo '<td>' . esc_html( $author_name ) . '</td>';
		echo '<td class="vfaudit-num">' . esc_html( number_format_i18n( (int) $row->video_count ) ) . '</td>';
		echo '<td><a class="button button-small" href="' . esc_url( $base_url . $cid ) . '">' . esc_html__( 'View videos', 'video-flow-audit-for-tutor-lms' ) . ' &rarr;</a></td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
}

/**
 * Video breakdown for one course.
 */
function vfaudit_render_course_detail( int $course_id ): void {
	$course_title = get_the_title( $course_id );
	if ( '' === $course_title ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Course not found.', 'video-flow-audit-for-tutor-lms' ) . '</p></div>';
		return;
	}

	$videos    = vfaudit_core_get_course_videos_cached( $course_id );
	$back_url  = admin_url( 'admin.php?page=' . VFAUDIT_MENU_SLUG );
	$providers = array_count_values( array_column( $videos, 'provider' ) );

	echo '<p><a class="vfaudit-back" href="' . esc_url( $back_url ) . '">&larr; ' . esc_html__( 'All courses', 'video-flow-audit-for-tutor-lms' ) . '</a></p>';
	echo '<h2 class="vfaudit-course-name">' . esc_html( $course_title ) . '</h2>';

	echo '<p class="vfaudit-meta">';
	printf(
		/* translators: %s: number of videos. */
		esc_html( _n( '%s video connected to this course', '%s videos connected to this course', count( $videos ), 'video-flow-audit-for-tutor-lms' ) ),
		esc_html( number_format_i18n( count( $videos ) ) )
	);
	foreach ( array(
		'vimeo'   => 'Vimeo',
		'youtube' => 'YouTube',
		'bunny'   => 'Bunny',
		'local'   => 'Local',
	) as $key => $label ) {
		if ( ! empty( $providers[ $key ] ) ) {
			echo ' <span class="vfaudit-tag vfaudit-tag-' . esc_attr( $key ) . '">' . esc_html( $providers[ $key ] . ' ' . $label ) . '</span>';
		}
	}
	echo '</p>';

	if ( empty( $videos ) ) {
		echo '<div class="vfaudit-empty">' . esc_html__( 'No videos connected to this course yet.', 'video-flow-audit-for-tutor-lms' ) . '</div>';
		return;
	}

	echo '<table class="widefat striped vfaudit-table vfaudit-detail">';
	echo '<thead><tr>';
	echo '<th>' . esc_html__( 'Lesson', 'video-flow-audit-for-tutor-lms' ) . '</th>';
	echo '<th>' . esc_html__( 'Source', 'video-flow-audit-for-tutor-lms' ) . '</th>';
	echo '<th>' . esc_html__( 'Title / filename', 'video-flow-audit-for-tutor-lms' ) . '</th>';
	echo '<th>' . esc_html__( 'Video ID', 'video-flow-audit-for-tutor-lms' ) . '</th>';
	echo '<th class="vfaudit-num">' . esc_html__( 'Duration', 'video-flow-audit-for-tutor-lms' ) . '</th>';
	echo '<th>' . esc_html__( 'Notes', 'video-flow-audit-for-tutor-lms' ) . '</th>';
	echo '</tr></thead><tbody>';

	foreach ( $videos as $v ) {
		$lesson_label = ( ( $v['post_type'] ?? '' ) === 'courses' )
			? __( 'Course (Basic video)', 'video-flow-audit-for-tutor-lms' )
			: (string) ( $v['post_title'] ?? get_the_title( (int) ( $v['post_id'] ?? 0 ) ) );

		$duration = (int) ( $v['duration'] ?? 0 );
		$dur_cell = $duration > 0 ? vfaudit_core_format_duration( $duration ) : '—';

		$video_id = (string) ( $v['video_id'] ?? '' );
		$short    = strlen( $video_id ) > 44 ? substr( $video_id, 0, 44 ) . '…' : $video_id;

		$notes = array();
		if ( ( $v['placed'] ?? null ) === false ) {
			$notes[] = '<span class="vfaudit-note-orphan" title="' . esc_attr__( 'Tracked against this lesson but not embedded in any field — likely an interrupted upload.', 'video-flow-audit-for-tutor-lms' ) . '">' . esc_html__( 'Not embedded', 'video-flow-audit-for-tutor-lms' ) . '</span>';
		}
		if ( ! empty( $v['from_oembed'] ) ) {
			$notes[] = esc_html__( 'From oEmbed cache', 'video-flow-audit-for-tutor-lms' );
		}
		$extra_usages = count( vfaudit_core_get_video_usages( $video_id ) );
		if ( $extra_usages > 1 ) {
			/* translators: %d: number of places the video is used. */
			$notes[] = esc_html( sprintf( _n( 'Used in %d place', 'Used in %d places', $extra_usages, 'video-flow-audit-for-tutor-lms' ), $extra_usages ) );
		}

		echo '<tr>';
		echo '<td>' . esc_html( $lesson_label ) . '</td>';
		echo '<td>' . wp_kses_post( vfaudit_source_badge( (string) ( $v['provider'] ?? 'bunny' ), (bool) ( $v['managed'] ?? false ) ) ) . '</td>';
		echo '<td>' . esc_html( (string) ( $v['filename'] ?? $v['title'] ?? '—' ) ) . '</td>';
		echo '<td class="vfaudit-id"><code>' . esc_html( $short ) . '</code>';
		if ( '' !== $video_id ) {
			echo ' <button type="button" class="button-link vfaudit-copy" data-id="' . esc_attr( $video_id ) . '">' . esc_html__( 'Copy', 'video-flow-audit-for-tutor-lms' ) . '</button>';
		}
		echo '</td>';
		echo '<td class="vfaudit-num">' . esc_html( $dur_cell ) . '</td>';
		echo '<td>' . wp_kses_post( implode( ' · ', $notes ) ) . '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
}

/**
 * A source badge, e.g. "Vimeo" / "Bunny" / "External Bunny".
 */
function vfaudit_source_badge( string $provider, bool $managed ): string {
	$labels = array(
		'vimeo'   => __( 'Vimeo', 'video-flow-audit-for-tutor-lms' ),
		'youtube' => __( 'YouTube', 'video-flow-audit-for-tutor-lms' ),
		'local'   => __( 'Local', 'video-flow-audit-for-tutor-lms' ),
		'bunny'   => $managed ? __( 'Bunny', 'video-flow-audit-for-tutor-lms' ) : __( 'External Bunny', 'video-flow-audit-for-tutor-lms' ),
	);
	$label  = $labels[ $provider ] ?? ucfirst( $provider );
	return '<span class="vfaudit-badge vfaudit-badge-' . esc_attr( $provider ) . '">' . esc_html( $label ) . '</span>';
}

/**
 * The single, non-nagging upgrade call-out.
 */
function vfaudit_render_upgrade_panel(): void {
	$features = array(
		__( 'Upload videos to Bunny Stream directly from the Tutor course builder — large, resumable uploads.', 'video-flow-audit-for-tutor-lms' ),
		__( 'Migrate existing Vimeo and self-hosted videos to Bunny Stream, one at a time or in bulk.', 'video-flow-audit-for-tutor-lms' ),
		__( 'Every migrated video keeps its exact place in the lesson; embeds are rewritten for you.', 'video-flow-audit-for-tutor-lms' ),
		__( 'A full Video Manager: transcode status, durations, per-video view counts, and orphaned-video detection.', 'video-flow-audit-for-tutor-lms' ),
		__( 'Library-wide views and average watch time for the last 7, 30, or 90 days.', 'video-flow-audit-for-tutor-lms' ),
		__( 'Custom video thumbnails, and automatic Tutor lesson completion when a learner finishes watching.', 'video-flow-audit-for-tutor-lms' ),
	);

	echo '<div class="vfaudit-upgrade">';
	echo '<h2>' . esc_html__( 'Do more with Video Flow for Tutor LMS', 'video-flow-audit-for-tutor-lms' ) . '</h2>';
	echo '<p>' . esc_html__( 'Video Flow Audit shows you where every video lives. Video Flow for Tutor LMS moves it to Bunny Stream and manages it from there — without breaking a single lesson embed.', 'video-flow-audit-for-tutor-lms' ) . '</p>';
	echo '<ul class="vfaudit-upgrade-list">';
	foreach ( $features as $feature ) {
		echo '<li>' . esc_html( $feature ) . '</li>';
	}
	echo '</ul>';
	echo '<p><a class="button button-primary" href="https://wpvideoflow.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'See all features at wpvideoflow.com', 'video-flow-audit-for-tutor-lms' ) . '</a></p>';
	echo '</div>';
}
