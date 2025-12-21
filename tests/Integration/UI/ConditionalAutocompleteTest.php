<?php
/**
 * Tests for the Conditional Autocomplete functionality.
 *
 * @package Automattic\AdCodeManager\Tests\Integration\UI
 */

declare(strict_types=1);

namespace Automattic\AdCodeManager\Tests\Integration\UI;

use Automattic\AdCodeManager\Tests\Integration\TestCase;
use Automattic\AdCodeManager\UI\Conditional_Autocomplete;
use ReflectionClass;
use WP_Term;

/**
 * Tests for the Conditional Autocomplete class.
 */
final class ConditionalAutocompleteTest extends TestCase {

	/**
	 * Instance of the class under test.
	 *
	 * @var Conditional_Autocomplete
	 */
	private Conditional_Autocomplete $autocomplete;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->autocomplete = new Conditional_Autocomplete();
	}

	/**
	 * Test that the run method registers the necessary hooks.
	 *
	 * @covers \Automattic\AdCodeManager\UI\Conditional_Autocomplete::run
	 */
	public function test_run_registers_hooks(): void {
		$this->autocomplete->run();

		self::assertNotFalse(
			has_action( 'admin_enqueue_scripts', array( $this->autocomplete, 'enqueue_scripts' ) )
		);
		self::assertNotFalse(
			has_action( 'wp_ajax_acm_search_terms', array( $this->autocomplete, 'ajax_search_terms' ) )
		);
	}

	/**
	 * Test that scripts are not enqueued on non-ACM pages.
	 *
	 * @covers \Automattic\AdCodeManager\UI\Conditional_Autocomplete::enqueue_scripts
	 */
	public function test_scripts_not_enqueued_on_other_pages(): void {
		$this->autocomplete->enqueue_scripts( 'edit.php' );

		self::assertFalse( wp_script_is( 'acm-conditional-autocomplete', 'enqueued' ) );
	}

	/**
	 * Test that the acm_autocomplete_conditionals filter is applied.
	 *
	 * @covers \Automattic\AdCodeManager\UI\Conditional_Autocomplete
	 */
	public function test_autocomplete_conditionals_filter(): void {
		$filter_called = false;
		$custom_conditionals = array(
			'custom_conditional' => array(
				'type'     => 'taxonomy',
				'taxonomy' => 'custom_tax',
			),
		);

		add_filter(
			'acm_autocomplete_conditionals',
			function ( $conditionals ) use ( &$filter_called, $custom_conditionals ) {
				$filter_called = true;
				return array_merge( $conditionals, $custom_conditionals );
			}
		);

		// Trigger script enqueue to get the conditionals.
		set_current_screen( 'settings_page_ad-code-manager' );
		$this->autocomplete->enqueue_scripts( 'settings_page_ad-code-manager' );

		self::assertTrue( $filter_called );
	}

	/**
	 * Test default autocomplete conditionals include expected entries.
	 *
	 * @covers \Automattic\AdCodeManager\UI\Conditional_Autocomplete
	 */
	public function test_default_autocomplete_conditionals(): void {
		// Use reflection to access the private method.
		$reflection = new ReflectionClass( $this->autocomplete );
		$method = $reflection->getMethod( 'get_autocomplete_conditionals' );
		$method->setAccessible( true );

		$conditionals = $method->invoke( $this->autocomplete );

		// Verify expected conditionals are present.
		self::assertArrayHasKey( 'is_category', $conditionals );
		self::assertArrayHasKey( 'has_category', $conditionals );
		self::assertArrayHasKey( 'is_tag', $conditionals );
		self::assertArrayHasKey( 'has_tag', $conditionals );
		self::assertArrayHasKey( 'is_page', $conditionals );
		self::assertArrayHasKey( 'is_single', $conditionals );

		// Verify structure of a taxonomy conditional.
		self::assertEquals( 'taxonomy', $conditionals['is_category']['type'] );
		self::assertEquals( 'category', $conditionals['is_category']['taxonomy'] );

		// Verify structure of a post_type conditional.
		self::assertEquals( 'post_type', $conditionals['is_page']['type'] );
		self::assertEquals( 'page', $conditionals['is_page']['post_type'] );
	}

	/**
	 * Test taxonomy term search.
	 *
	 * @covers \Automattic\AdCodeManager\UI\Conditional_Autocomplete
	 */
	public function test_search_taxonomy_terms(): void {
		// Create test categories.
		self::factory()->category->create( array( 'name' => 'Technology News' ) );
		self::factory()->category->create( array( 'name' => 'Tech Reviews' ) );
		self::factory()->category->create( array( 'name' => 'Sports' ) );

		// Use reflection to access the private method.
		$reflection = new ReflectionClass( $this->autocomplete );
		$method = $reflection->getMethod( 'search_taxonomy_terms' );
		$method->setAccessible( true );

		$results = $method->invoke( $this->autocomplete, 'Tech', 'category' );

		self::assertCount( 2, $results );
		self::assertArrayHasKey( 'id', $results[0] );
		self::assertArrayHasKey( 'text', $results[0] );
	}

	/**
	 * Test post search.
	 *
	 * @covers \Automattic\AdCodeManager\UI\Conditional_Autocomplete
	 */
	public function test_search_posts(): void {
		// Create test pages.
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'About Us',
				'post_status' => 'publish',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'About Our Team',
				'post_status' => 'publish',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Contact',
				'post_status' => 'publish',
			)
		);

		// Use reflection to access the private method.
		$reflection = new ReflectionClass( $this->autocomplete );
		$method = $reflection->getMethod( 'search_posts' );
		$method->setAccessible( true );

		$results = $method->invoke( $this->autocomplete, 'About', 'page' );

		self::assertCount( 2, $results );
		self::assertArrayHasKey( 'id', $results[0] );
		self::assertArrayHasKey( 'text', $results[0] );
	}

	/**
	 * Test empty search returns empty array.
	 *
	 * @covers \Automattic\AdCodeManager\UI\Conditional_Autocomplete
	 */
	public function test_search_no_results(): void {
		// Use reflection to access the private method.
		$reflection = new ReflectionClass( $this->autocomplete );
		$method = $reflection->getMethod( 'search_taxonomy_terms' );
		$method->setAccessible( true );

		$results = $method->invoke( $this->autocomplete, 'NonexistentTerm', 'category' );

		self::assertIsArray( $results );
		self::assertEmpty( $results );
	}

	/**
	 * Test search for tags returns correct structure.
	 *
	 * @covers \Automattic\AdCodeManager\UI\Conditional_Autocomplete
	 */
	public function test_search_tags(): void {
		// Create test tags.
		self::factory()->tag->create( array( 'name' => 'WordPress Tips' ) );
		self::factory()->tag->create( array( 'name' => 'WordPress Plugins' ) );

		// Use reflection to access the private method.
		$reflection = new ReflectionClass( $this->autocomplete );
		$method = $reflection->getMethod( 'search_taxonomy_terms' );
		$method->setAccessible( true );

		$results = $method->invoke( $this->autocomplete, 'WordPress', 'post_tag' );

		self::assertCount( 2, $results );
		// Results should use slug as ID.
		self::assertStringContainsString( 'wordpress', $results[0]['id'] );
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down(): void {
		unset( $_GET['search'], $_GET['conditional'], $_GET['type'], $_GET['taxonomy'], $_GET['post_type'], $_GET['nonce'] );
		parent::tear_down();
	}
}
