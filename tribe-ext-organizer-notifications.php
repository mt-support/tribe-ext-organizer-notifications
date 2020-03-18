<?php
/**
 * Plugin Name:     Event Tickets Extension: Organizer Notifications
 * Description:     This extension sends a notification to organizers when an attendee registers for their event.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Organizer_Notifications
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) {
   return;
}

/**
 * Extension main class, class begins loading on init.
 */
class Tribe__Extension__Organizer_Notifications extends Tribe__Extension {

    /**
     * Setup the Extension's properties.
     */
    public function construct() {

        // Set core plugin requirements.
        $this->add_required_plugin( 'Tribe__Tickets__Main', '4.11.1' );

        // Set the extension's tec.com URL.
        $this->set_url( 'https://theeventscalendar.com/extensions/organizer-notifications/' );
    }

    /**
     * Extension initialization and hooks.
     */
    public function init() {

        // RSVP
        add_action( 'event_tickets_rsvp_tickets_generated', [$this, 'generate_email'],     10, 2 );

        // WooCommerce
        add_action( 'event_ticket_woo_attendee_created',    [$this, 'generate_email'],     10, 2 );

        // Tribe Commerce
        add_action( 'event_tickets_tpp_tickets_generated',  [$this, 'generate_email'],     10, 2 );

        // EDD
        add_action( 'event_ticket_edd_attendee_created',    [$this, 'generate_email'],     10, 2 );
    }

    /**
     * Generate organizer email.
     *
     * @param $other
     * @param $event_id
     * @return string
     */
    public function generate_email( $other = null, $event_id = null ) {

        // Get the organizer email address.
        $to = $this->get_recipient( $event_id );

        // Bail if there's not a valid email for the organizer.
        if ( '' === $to ) return;

        // Get the email subject.
        $subject = $this->get_subject( $event_id );

        // Get the email content.
        $content = $this->get_content( $event_id );

        // Generate notification email.
        wp_mail( $to, $subject, $content, ['Content-type: text/html'] );
   }

    /**
     * Get organizer's email address.
     *
     * @param $post_id
     * @return string
     */
    private function get_recipient( $post_id ) {

        // Get the organizer's email
        $to = html_entity_decode( tribe_get_organizer_email( $post_id ), ENT_COMPAT, 'UTF-8' );

        // Return valid email, or empty string
        return ( is_email( $to ) ) ? $to : '';
    }

    /**
     * Get email subject.
     *
     * @param $post_id
     * @return string
     */
    private function get_subject( $post_id ) {

        // Filter to allow users to modify the email subject.
        $subject = apply_filters('tribe-ext-organizer-notifications-subject', 'Your event %1$s has new attendee(s) - %2$s');

        // Return the subject with event and site names injected.
        return sprintf( __( $subject, 'tribe-extension' ),  get_the_title( $post_id ), get_bloginfo( 'name' ) );
    }

    /**
     * Get link to attendees list.
     *
     * @param $post_id
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
        $output = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( $link_text, 'tribe-extension' ) );

        // Return link markup.
        return apply_filters( 'tribe-ext-organizer-notifications-content', $output );
    }
}