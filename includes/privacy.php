<?php

/*
 * Code to support GDPR compliances, WordPress version 4.9.6+
 * @since 1.1
 */


/**
 * Return the default suggested privacy policy content.
 *
 * @return string The default policy content.
 */
function wp_zapier_get_default_privacy_content() {

	$content = '<h2>' . __( 'What data do you store', 'wp-zapier' ) . '</h2>';

	$content .= '<p>' . __( 'No personal data is stored. (You may skip this section in your privacy policy).', 'wp-zapier' ) . '</p>';

	$content .= '<h2>' . __( 'Where do we send your data.', 'wp-zapier' ) . '</h2>';

	$content .= '<p>' . __( 'Please adjust this text below accordingly, this is a rough guideline. Change the text to suit your needs. This is up to site owners to comply with GDPR / their countries privacy laws.', 'wp-zapier' ) . '</p>';

	$content .= '<p>' . __( sprintf( 'Personal information such as first name, last name, email address and [[list custom user meta fields here]] are sent through to Zapier %s to integrate and be processed with the following third-party services: [[list third party services and links to their privacy policy here]]. Please note that we do not ever send your password through to any of these services listed above.', '[<a href="https://zapier.com/help/data-privacy/" target="_blank" rel="noopener nofollow">Zapier Privacy Policy</a>]'), 'wp-zapier') . '</p>';

	$content = apply_filters( 'wp_zapier_get_default_privacy_content', $content );

	return $content;
}

/**
 * Add the suggested privacy policy text to the policy postbox.
 */
function wp_zapier_add_suggested_privacy_content() {

	if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
		$content = wp_zapier_get_default_privacy_content();
		wp_add_privacy_policy_content( __( 'WP Zapier', 'wp-zapier' ), $content );
	}
	
}
add_action( 'admin_init', 'wp_zapier_add_suggested_privacy_content', 20 );