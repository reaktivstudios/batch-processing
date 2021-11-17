<?php
/**
 * Functions to simplify interacting with the Locomotive utility.
 *
 * @package Locomotive
 */

use Rkv\Locomotive\Abstracts\Batch;
use Rkv\Locomotive\Batches\Posts;
use Rkv\Locomotive\Batches\Users;
use Rkv\Locomotive\Batches\Sites;
use Rkv\Locomotive\Batches\Terms;
use Rkv\Locomotive\Batches\Comments;

/**
 * Register a new batch process.
 *
 * @param  array $args Arguments for the batch process.
 * @throws Exception If the batch processor doesn't extend the Batch abstract class.
 */
function register_batch_process( $args ) {
	if ( empty( $args['type'] ) ) {
		$args['type'] = '';
	}

	$batch_processor = get_default_batch_processor_for_type( $args['type'] );

	/**
	 * Filter the batch processor to be used with the batch process being registered.
	 *
	 * @param Batch  $batch_processor The batch processor to use, defaults to null.
	 * @param string $type            Type of data for this batch process.
	 * @param array  $args            Arguments for the batch process.
	 */
	$batch_processor = apply_filters( 'loco_register_batch_process_processor', $batch_processor, $args['type'], $args );

	if ( empty( $batch_processor ) ) {
		throw new Exception( sprintf(
			__( 'Batch processor not found for type "%1$s"', 'locomotive' ),
			$args['type']
		) );
	}

	if ( ! is_subclass_of( $batch_processor, 'Rkv\Locomotive\Abstracts\Batch' ) ) {
		throw new Exception( __( 'Batch processor must extend the Batch abstract class.', 'locomotive' ) );
	}

	$batch_processor->register( $args );
}

/**
 * Returns the default batch processor used for a specific type of data.
 *
 * @param string $type Type of data to get a batch processor for.
 *
 * @return Batch|null The batch processor to use for the specified type, null if not a default type.
 */
function get_default_batch_processor_for_type( $type ) {
	switch ( $type ) {
		case 'post':
			return new Posts();
			break;

		case 'user':
			return new Users();
			break;

		case 'site':
			if ( is_multisite() ) {
				return new Sites();
			}
			break;

		case 'term':
			return new Terms();
			break;

		case 'comment':
			return new Comments();
			break;
	}

	return null;
}

/**
 * Get the batch hooks that have been added and some info about them.
 *
 * @return array
 */
function locomotive_get_all_batches() {
	$batches = get_option( 'loco_batches', array() );

	foreach ( $batches as $k => $batch ) {
		if ( $batch_status = get_option( 'loco_batch_' . $k ) ) {
			$last_run = locomotive_time_ago( $batch_status['timestamp'] );
			$status = $batch_status['status'];
		} else {
			$last_run = 'never';
			$status = 'new';
		}

		$batches[ $k ]['last_run'] = $last_run;
		$batches[ $k ]['status'] = $status;
	}

	return $batches;
}

/**
 * Update the registered batches.
 *
 * @param array $batches Batches you want to register.
 */
function locomotive_update_registered_batches( $batches ) {
	return update_option( 'loco_batches', $batches );
}

/**
 * Template function for showing time ago.
 *
 * @todo Move this to a template functions file.
 *
 * @param  integer $time Timestamp.
 */
function locomotive_time_ago( $time ) {
	return sprintf( _x( '%s ago', 'amount of time that has passed', 'locomotive' ), human_time_diff( $time, current_time( 'timestamp' ) ) );
}

/**
 * Clear all existing batches.
 */
function locomotive_clear_existing_batches() {
	return update_option( 'loco_batches', array() );
}
