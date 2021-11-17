<?php

namespace Rkv\Locomotive\Tests;

use WP_UnitTestCase;
use Exception;
use Rkv\Locomotive\Abstracts\Batch;
use Rkv\Locomotive\Batches\Posts;
use Rkv\Locomotive\Loader;

class BatchFunctionTest extends WP_UnitTestCase {

	public function tearDown() {
		parent::tearDown();

		locomotive_clear_existing_batches();

		// Manually dequeue the CSS and JS files.
		wp_dequeue_style( 'batch-process-styles' );
		wp_dequeue_script( 'batch-js' );
	}

	/**
	 * Test successful batch registers.
	 */
	public function test_successful_register_batch() {
		$this->register_successful_batch( '1' );

		$all_batches = locomotive_get_all_batches();
		$this->assertCount( 1, $all_batches );

		register_batch_process( array(
			'name'     => 'My Test Batch process',
			'type'     => 'user',
			'callback' => __NAMESPACE__ . '\\my_callback_function',
			'args'     => array(
				'number' => 10,
			),
		) );

		$all_batches = locomotive_get_all_batches();
		$this->assertCount( 2, $all_batches );
	}

	/**
	 * Test that an Exception is thrown if a Batch Processor isn't found for a specified type.
	 */
	public function test_register_batch_process_throws_exception_if_no_batch_processor_for_type() {
		$this->setExpectedException( Exception::class );

		register_batch_process( array(
			'name'     => 'My Failed Test Batch process',
			'type'     => 'foo',
			'callback' => __NAMESPACE__ . '\\my_callback_function',
		) );
	}

	/**
	 * Test that the batch processor filter works properly.
	 */
	public function test_batch_processor_filter_works() {
		// Create a mock Batch object, set the expectation that register will be called
		$mock_batch_processor = $this->getMockBuilder( Batch::class )->getMock();
		$mock_batch_processor->expects( $this->once() )
			->method( 'register' );

		add_filter( 'loco_register_batch_process_processor', function ( $batch_processor, $type ) use ( $mock_batch_processor ) {
			if ( 'foo' !== $type ) {
				return $batch_processor;
			}

			return $mock_batch_processor;
		}, 10, 2 );

		register_batch_process( array(
			'name'     => 'My Test Batch process',
			'type'     => 'foo',
			'callback' => __NAMESPACE__ . '\\my_callback_function',
		) );
	}

	/**
	 * Test that an Exception is thrown if a non-Batch Batch Processor is set in the filter.
	 */
	public function test_register_batch_process_throws_exception_if_batch_processor_is_not_a_batch() {
		// Create a mock Batch object, set the expectation that register will be called
		$mock_batch_processor = $this->getMockBuilder( \stdClass::class )->getMock();

		add_filter( 'loco_register_batch_process_processor', function ( $batch_processor, $type ) use ( $mock_batch_processor ) {
			if ( 'foo' !== $type ) {
				return $batch_processor;
			}

			return $mock_batch_processor;
		}, 10, 2 );

		$this->setExpectedException( Exception::class );

		register_batch_process( array(
			'name'     => 'My Test Batch process',
			'type'     => 'foo',
			'callback' => __NAMESPACE__ . '\\my_callback_function',
		) );
	}

	/**
	 * Test that we can get all batches with proper data.
	 */
	public function test_all_batches() {
		$this->register_successful_batch( 'my-batch' );
		$batches = locomotive_get_all_batches();

		$this->assertNotNull( $batches['my-batch']['last_run'] );
		$this->assertNotNull( $batches['my-batch']['status'] );

		$post_batch = new Posts();
		$post_batch->register( array(
			'name'     => 'Hey there',
			'type'     => 'post',
			'callback' => __NAMESPACE__ . '\\my_callback_function',
			'args'     => array(
				'posts_per_page' => 10,
				'post_type'      => 'post',
			),
		) );

		$post_batch->run( 1 );
		$batches = locomotive_get_all_batches();

		$this->assertTrue( ( 'no results found' === $batches['hey-there']['status'] ) );
	}

	/**
	 * Test that we can clear existing batches.
	 */
	public function test_clear_batches() {
		$this->register_successful_batch( 'hello' );
		$batches = locomotive_get_all_batches();
		$this->assertCount( 1, $batches );

		locomotive_clear_existing_batches();
		$batches = locomotive_get_all_batches();
		$this->assertCount( 0, $batches );
	}

	/**
	 * Test locomotive_time_ago() returns what we expect;
	 */
	public function test_time_ago() {
		$time = current_time( 'timestamp' );
		$time_ago = locomotive_time_ago( $time );

		$this->assertEquals( '1 min ago', $time_ago );
	}

	/**
	 * Confirm our CSS and JS assets are loading inside our settings page.
	 */
	public function test_asset_loading() {

		// Check that the items are not enquened before we start.
		$this->assertFalse( $this->are_batch_assets_enqueued() );

		// Call our loader class on the locomotive settings page.
		$this->load_admin_enqueue_hook( 'tools_page_locomotive' );

		// Check that the items are enquened.
		$this->assertTrue( $this->are_batch_assets_enqueued() );
	}

	/**
	 * Confirm our CSS and JS assets are not loading outside our settings page.
	 */
	public function test_asset_not_loading() {

		// Check that the items are not enquened before we start.
		$this->assertFalse( $this->are_batch_assets_enqueued() );

		// Call our loader class on the general options page.
		$this->load_admin_enqueue_hook( 'options-general.php' );

		// Check that our assets aren't enquened.
		$this->assertFalse( $this->are_batch_assets_enqueued() );
	}

	/**
	 * Test the time calculation by confirming a string is returned when passing a timestamp.
	 */
	public function test_elapsed_time() {
		$this->assertInternalType( 'string', locomotive_time_ago( 1472852621 ) );
	}

	/**
	 * Test the get_default_batch_processor_for_type function returns null if a non default type is provided.
	 */
	public function test_get_default_batch_processor_for_type_returns_null_for_non_default_type() {
		$this->assertNull( get_default_batch_processor_for_type( 'foo' ) );
	}

	/**
	 * Helper function to register a successful batch.
	 *
	 * @param string $slug Slug of test batch.
	 */
	private function register_successful_batch( $slug = 'test-batch' ) {
		register_batch_process( array(
			'name'     => 'My Test Batch process',
			'slug'     => $slug,
			'type'     => 'post',
			'callback' => __NAMESPACE__ . '\\my_callback_function',
			'args'     => array(
				'posts_per_page' => 10,
				'post_type'      => 'post',
			),
		) );
	}

	/**
	 * Helper function to call our script loader function.
	 *
	 * @param string $hook The hook that is passed to admin_enqueue_scripts.
	 */
	private function load_admin_enqueue_hook( $hook = '' ) {

		// Call our loader class.
		$this->admin_page = new Loader;

		// Set the admin hook to the requested page.
		$this->admin_page->scripts( $hook );
	}

	/**
	 * Helper function to check if the scripts and styles are loaded.
	 *
	 * @return bool Whether or not the style and script is enqueued.
	 */
	private function are_batch_assets_enqueued() {

		// Run the check on both the style and script enqueue.
		if ( false !== wp_style_is( 'batch-process-styles', 'enqueued' ) && false !== wp_script_is( 'batch-js', 'enqueued' ) ) {
			return true;
		} else {
			return false;
		}
	}
}
