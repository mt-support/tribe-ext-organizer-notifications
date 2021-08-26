<?php
/**
 * Plugin Name:       Event Tickets Extension: Organizer Notifications
 * Description:       This extension sends a notification to organizers when an attendee registers for their event.
 * Version:           TBD
 * Plugin URI:        https://theeventscalendar.com/extensions/organizer-notifications/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-organizer-notifications
 * Extension Class:   Tribe__Extension__Organizer_Notifications
 * Author:            The Events Calendar
 * Author URI:        https://evnt.is/1971
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tec-labs-organizer-notifications
 */

// Do not load unless Tribe Common is fully loaded.
if ( class_exists( 'Tribe__Extension' ) ) {
	/**
	 * Extension main class, class begins loading on init.
	 */
	class Tribe__Extension__Organizer_Notifications extends Tribe__Extension {

		/**
		 * Setup the Extension's properties.
		 */
		public function construct() {
			$this->add_required_plugin( 'Tribe__Tickets__Main', '4.11.1' );
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			// RSVP
			add_action( 'event_tickets_rsvp_tickets_generated', [ $this, 'generate_email' ], 10, 2 );

			// WooCommerce
			add_action( 'event_ticket_woo_attendee_created', [ $this, 'generate_email' ], 10, 2 );

			// Tribe Commerce
			add_action( 'event_tickets_tpp_tickets_generated', [ $this, 'generate_email' ], 10, 2 );

			// EDD
			add_action( 'event_ticket_edd_attendee_created', [ $this, 'generate_email' ], 10, 2 );
		}

		/**
		 * Generate organizer email.
		 *
		 * @param $other
		 * @param $event_id
		 */
		public function generate_email( $other = null, $event_id = null ) {

			// Get the organizer email address.
			$to = $this->get_recipient( $event_id );

			// Bail if there's not a valid email for the organizer.
			if ( '' === $to ) {
				return;
			}

			// Get the email subject.
			$subject = $this->get_subject( $event_id );

			// Get the email content.
			$content = $this->get_content( $event_id );

			// Generate notification email.
			wp_mail( $to, $subject, $content, [ 'Content-type: text/html' ] );
		}

		/**
		 * Get all organizers' email addresses.
		 *
		 * @param $post_id
		 *
		 * @return array
		 */
		private function get_recipient( $post_id ) {

			// Get all organizers associated to the post.
			$organizer_ids = tribe_get_organizer_ids( $post_id );

			$to = [];

			// Get the email for each organizer.
			foreach ( $organizer_ids as $organizer_id ) {
				$organizer_email = tribe_get_organizer_email( $organizer_id, false );

				// Make sure it's a valid email.
				if ( is_email( $organizer_email ) ) {
					$to[] = $organizer_email;
				}
			}

			if ( empty( $to ) ) {
				return [];
			}

			return $to;
		}

		/**
		 * Get email subject.
		 *
		 * @param $post_id
		 *
		 * @return string
		 */
		private function get_subject( $post_id ) {

			// Filter to allow users to modify the email subject.
			$subject = apply_filters( 'tribe-ext-organizer-notifications-subject', 'Your event %1$s has new attendee(s) - %2$s' );

			// Return the subject with event and site names injected.
			return sprintf( __( $subject, 'tec-labs-organizer-notifications' ), get_the_title( $post_id ), get_bloginfo( 'name' ) );
		}

		/**
		 * Get link to attendees list.
		 *
		 * @param $post_id
		 *
		 * @return string
		 */
		private function get_content( $post_id ) {

			// The url to the attendee page.
			$url = admin_url( 'edit.php?post_type=tribe_events&page=tickets-attendees&event_id=' . $post_id );

			// Default link text
			$default_link_text = "View the event's attendee list";

			// Filter to allow users to modify the link text.
			$link_text = apply_filters( 'tribe-ext-organizer-notifications-link-text', $default_link_text );

			// Define the link markup.
			$output = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( $link_text, 'tec-labs-organizer-notifications' ) );

			// Return link markup.
			return apply_filters( 'tribe-ext-organizer-notifications-content', $output );
		}
	} // class
} // class_exists
