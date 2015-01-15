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
		$network = isset( $assoc_args['network'] );
		$prefix  = $network ? $wpdb->base_prefix : $wpdb->prefix;

		// Get table list.
		$tables = self::get_table_list( $args, $prefix, $network );

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

			// Set the current site.
			list( $siteid ) = sscanf( $table, $prefix."%d_ngg_pictures" );
			switch_to_blog( $siteid );

			// Get the pictures with missing slugs.
			$to_be_fixed = $wpdb->get_results(
				"SELECT pid, alttext FROM $table WHERE LENGTH(image_slug) = 0 OR image_slug IS NULL"
			);

			// Fix each missing slug.
			foreach ( $to_be_fixed as $picture ) {
				// Get the next unique slug.
				$slug = nggdb::get_unique_slug( sanitize_title_with_dashes( $picture->alttext ), 'image' );

				// Set the new slug.
				if ( ! $dry_run ) {
					$wpdb->update(
						$table,
						array( 'image_slug' => $slug ),
						array( 'pid' => $picture->pid )
					);
				}
				$fixed++;
			}
			$report[] = array( $table, $fixed );
			$total += $fixed;

			// Clear the image cache.
			if ( ! $dry_run ) {
				C_Photocrati_Cache::flush( 'all' );
			}
			restore_current_blog();
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
	private static function get_table_list( $args, $prefix, $network ) {
		global $wpdb;

		$matching_tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $prefix . '%ngg_pictures' ) );

		return array_values( $matching_tables );
	}
}

WP_CLI::add_command( 'fix-ngg-slugs', 'FixSlugs_Command' );
