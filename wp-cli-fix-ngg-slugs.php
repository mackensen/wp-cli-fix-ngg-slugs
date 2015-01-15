<?php
/**
 * Generate missing slugs in the wp_ngg_pictures table.
 *
 * @author mackensen (Charles Fulton)
 */
class FixSlugs_Command extends WP_CLI_Command {
	function __invoke( $args, $assoc_args ) {
		global $wpdb;

		$total   = 0;
		$report  = array();
		$dry_run = isset( $assoc_args['dry-run'] );

		$tables = self::get_table_list( $args, isset( $assoc_args['network'] ) );

		foreach ( $tables as $table ) {
			// See if there's anything to do here.
			$count_missing_slugs = $wpdb->get_var(
				"SELECT COUNT(*) FROM $table WHERE LENGTH(image_slug) = 0 OR image_slug IS NULL"
			);
			$fixed = 0;

			// Skip if all slugs set.
			if ( 0 == $count_missing_slugs ) {
				$report[] = array( $table, $fixed );
				continue;
			}

			// Get the existing slugs to avoid collisions.
			$slugs = $wpdb->get_col(
				"SELECT image_slug FROM $table WHERE LENGTH(image_slug) > 0 AND image_slug IS NOT NULL"
			);

			// Get the pictures with missing slugs.
			$to_be_fixed = $wpdb->get_results(
				"SELECT pid,filename FROM $table WHERE LENGTH(image_slug) = 0 OR image_slug IS NULL"
			);

			// Fix each missing slug.
			foreach ( $to_be_fixed as $picture ) {
				// Try to use the base filename as the slug.
				$fileparts = pathinfo( $picture->filename );
				$base      = sanitize_file_name( $fileparts['filename'] );
				$slug      = $base;
				$counter   = 2;

				// Increment if there's a collision.
				while ( in_array( $slug, $slugs ) ) {
					$slug = sprintf( '%s-%d', $base, $counter );
					$counter++;
				}

				// Set the new slug.
				if ( ! $dry_run ) {
					$wpdb->update(
						$table,
						array( 'image_slug' => $slug ),
						array( 'pid' => $picture->pid )
					);
				}
				$slugs[] = $slug;
				$fixed++;
			}
			$report[] = array( $table, $fixed );
			$total += $fixed;
		}

		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$table = new \cli\Table();
			$table->setHeaders( array( 'Table', 'Fixed' ) );
			$table->setRows( $report );
			$table->display();

			if ( ! $dry_run ) {
				WP_CLI::success( "Fixed $total slugs." );
			}
		}
	}

	// Adapted from wp-cli search-replace.
	private static function get_table_list( $args, $network ) {
		global $wpdb;

		$prefix = $network ? $wpdb->base_prefix : $wpdb->prefix;
		$matching_tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $prefix . '%ngg_pictures' ) );

		return array_values( $matching_tables );
	}
}

WP_CLI::add_command( 'fix-ngg-slugs', 'FixSlugs_Command' );
