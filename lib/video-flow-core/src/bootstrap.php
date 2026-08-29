<?php
/**
 * Video Flow Core — bootstrap.
 *
 * Required exactly once by the winning copy (see ../video-flow-core.php).
 * Pulls in every vfaudit_core_* function file. Each file guards its own
 * definitions with function_exists() so a stray double-load can never
 * fatal.
 *
 * @package VideoFlowCore
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/log.php';
require_once __DIR__ . '/adapter.php';
require_once __DIR__ . '/providers.php';
require_once __DIR__ . '/format.php';
require_once __DIR__ . '/usages.php';
require_once __DIR__ . '/scanner.php';
require_once __DIR__ . '/courses.php';
