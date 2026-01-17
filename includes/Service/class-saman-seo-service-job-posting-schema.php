<?php
/**
 * Job Posting Schema service for job posting schema optimization.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Job Posting Schema controller.
 */
class Job_Posting_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_job_posting_schema_to_graph' ], 20, 2 );
	}

	/**
	 * Add JobPosting schema to the JSON-LD graph.
	 *
	 * @param array    $graph The existing JSON-LD graph.
	 * @param \WP_Post $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_job_posting_schema_to_graph( $graph, $post ) {
		// This is just a placeholder for the logic to identify a job_posting post.
		if ( 'job_posting' !== get_post_type( $post->ID ) ) {
			return $graph;
		}

		$schema = $this->build_schema( $post );

		if ( ! empty( $schema ) ) {
			$graph[] = $schema;
		}

		return $graph;
	}

	/**
	 * Build JobPosting schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array|null
	 */
	private function build_schema( $post ) {
		$job_title = get_the_title( $post );
		$hiring_organization = get_bloginfo( 'name' );

		// In a real implementation, these would come from post meta.
		$employment_type = 'Full-time';
		$job_location    = 'New York, NY';

		if ( empty( $job_title ) ) {
			return null;
		}

		$schema = [
			'@context'            => 'https://schema.org',
			'@type'               => 'JobPosting',
			'title'               => $job_title,
			'description'         => get_the_excerpt( $post ),
			'hiringOrganization'  => [
				'@type' => 'Organization',
				'name'  => $hiring_organization,
			],
			'employmentType'      => $employment_type,
			'datePosted'          => get_the_date( 'c', $post ),
			'validThrough'        => '', // Should be populated from post meta
			'jobLocation'         => [
				'@type'   => 'Place',
				'address' => [
					'@type'          => 'PostalAddress',
					'addressLocality' => $job_location,
				],
			],
		];

		return $schema;
	}
}
