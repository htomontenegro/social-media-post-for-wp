<?php
/**
 * Removes all plugin-owned post meta when the plugin is deleted from the admin.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$meta_keys = [
	'_smp_description',
	'_smp_url',
	'_smp_platform',
	'_smp_media_type',
	'_smp_media_source',
	'_smp_media_attachment_id',
	'_smp_media_url',
	'_smp_author_name',
	'_smp_author_bio',
	'_smp_author_handle',
];

foreach ( $meta_keys as $key ) {
	$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => $key ] );
}
