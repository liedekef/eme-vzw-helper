<?php
namespace EMEVzw;

defined( 'ABSPATH' ) || exit;

/**
 * Wettelijke publicaties:
 *  - Formulier I (luik A + B) : publicatie Belgisch Staatsblad
 *  - Formulier II (luik A + C): KBO-update
 *  - Statutenwijziging: volledig document voor griffie
 *
 * PDF generation uses dompdf (from EME) or falls back to HTML-print.
 * The layout reproduces the official Belgian government forms exactly
 * as required by the griffie (KB 29 april 2019).
 */
class Publications {

    public static function init(): void {
        add_action( 'wp_ajax_eme_vzw_save_publication',        [ __CLASS__, 'ajax_save' ] );
        add_action( 'wp_ajax_eme_vzw_delete_publication',      [ __CLASS__, 'ajax_delete' ] );
        // Serve generated PDFs
        add_action( 'wp_ajax_eme_vzw_download_pdf',            [ __CLASS__, 'ajax_download_pdf' ] );
        add_action( 'wp_ajax_eme_vzw_download_bestuursverbod', [ __CLASS__, 'ajax_download_bestuursverbod' ] );
        add_action( 'wp_ajax_eme_vzw_howto_send',              [ __CLASS__, 'ajax_howto_send' ] );
    }

    /* ------------------------------------------------------------------
     * DATA CRUD
     * ------------------------------------------------------------------ */

    public static function get_all(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM `{$wpdb->prefix}emevzw_publications`
             ORDER BY av_date DESC"
        );
    }

    public static function get( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}emevzw_publications` WHERE id = %d",
            $id
        ) );
    }

    public static function get_board_changes( int $pub_id ): array {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT pb.*, b.first_name, b.last_name, b.national_id, b.birth_place, b.birth_date,
                    b.street, b.house_number, b.house_bus, b.postal_code, b.city, b.country,
                    b.legal_capacity, b.function_title, b.is_legal_entity, b.legal_name, b.legal_number, b.legal_representative
             FROM `{$wpdb->prefix}emevzw_pub_board` pb
             JOIN  `{$wpdb->prefix}emevzw_board` b ON b.id = pb.board_id
             WHERE pb.publication_id = %d
             ORDER BY pb.change_type, b.last_name",
            $pub_id
        ) );
    }

    public static function get_statute_changes( int $pub_id ): array {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}emevzw_statute_changes`
             WHERE publication_id = %d ORDER BY sort_order",
            $pub_id
        ) );
    }

    public static function save( array $data ): int|\WP_Error {
        global $wpdb;
        $allowed_statuses = [ 'draft', 'submitted', 'published' ];

        $row = [

            'av_date'           => eme_sanitize_request( $data['av_date'] ?? '' ),
            'av_location'       => eme_sanitize_request( $data['av_location'] ?? '' ),
            'statute_text'      => eme_sanitize_request( $data['statute_text'] ?? '' ),
            'submitted_date'    => ! empty( $data['submitted_date'] ) ? eme_sanitize_request( $data['submitted_date'] ) : null,
            'published_date'    => ! empty( $data['published_date'] ) ? eme_sanitize_request( $data['published_date'] ) : null,
            'bs_payment_ref'    => eme_sanitize_request( $data['bs_payment_ref'] ?? '' ),
            'bs_payment_date'   => ! empty( $data['bs_payment_date'] ) ? eme_sanitize_request( $data['bs_payment_date'] ) : null,
            'bs_payment_amount' => (float) ( $data['bs_payment_amount'] ?? 0 ),
            'status'            => in_array( $data['status'] ?? 'draft', $allowed_statuses, true ) ? $data['status'] : 'draft',
            'notes'             => eme_sanitize_request( $data['notes'] ?? '' ),
            'created_by'        => get_current_user_id(),
        ];

        $id = isset( $data['id'] ) ? (int) $data['id'] : 0;
        if ( $id > 0 ) {
            unset( $row['created_by'] );
            $wpdb->update( $wpdb->prefix . 'emevzw_publications', $row, [ 'id' => $id ], null, [ '%d' ] );
        } else {
            $wpdb->insert( $wpdb->prefix . 'emevzw_publications', $row );
            $id = (int) $wpdb->insert_id;
        }

        // Board changes
        $wpdb->delete( $wpdb->prefix . 'emevzw_pub_board', [ 'publication_id' => $id ], [ '%d' ] );
        if ( isset( $data['board_changes'] ) && is_array( $data['board_changes'] ) ) {
            $wpdb->delete( $wpdb->prefix . 'emevzw_pub_board', [ 'publication_id' => $id ], [ '%d' ] );
            foreach ( $data['board_changes'] as $bc ) {
                $wpdb->insert( $wpdb->prefix . 'emevzw_pub_board', [
                    'publication_id' => $id,
                    'board_id'       => (int) $bc['board_id'],
                    'change_type'    => in_array( $bc['change_type'] ?? 'B', [ 'B', 'E', 'H' ], true ) ? $bc['change_type'] : 'B',
                    'effective_date' => ! empty( $bc['effective_date'] ) ? eme_sanitize_request( $bc['effective_date'] ) : null,
                ] );
            }
        }
        return $id;
    }

    /* ------------------------------------------------------------------
     * AJAX
     * ------------------------------------------------------------------ */

    public static function ajax_save(): void {
        check_ajax_referer( 'eme_vzw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        $r    = self::save( $_POST );
        is_wp_error( $r ) ? wp_send_json_error( $r->get_error_message() ) : wp_send_json_success( [ 'id' => $r ] );
    }

    public static function ajax_delete(): void {
        check_ajax_referer( 'eme_vzw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        global $wpdb;
        $id = (int) ( $_POST['id'] ?? 0 );
        $wpdb->delete( $wpdb->prefix . 'emevzw_pub_board',        [ 'publication_id' => $id ], [ '%d' ] );
        $wpdb->delete( $wpdb->prefix . 'emevzw_statute_changes',  [ 'publication_id' => $id ], [ '%d' ] );
        $wpdb->delete( $wpdb->prefix . 'emevzw_publications',     [ 'id' => $id ], [ '%d' ] );
        wp_send_json_success();
    }

    /**
     * Generate the howto
     */
    public static function ajax_howto_send(): void {
        check_ajax_referer( 'eme_vzw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        $pub_id = (int) ( $_POST['pub_id'] ?? 0 );
        ob_start();
        include EME_VZW_DIR . 'templates/publication-help.php';
        $data = ob_get_clean();
        wp_send_json_success( $data );
    }

    /**
     * Generate and stream a PDF.
     */
    public static function ajax_download_pdf(): void {
        check_ajax_referer( 'eme_vzw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        $pub_id = (int) ( $_GET['pub_id'] ?? 0 );
        $debug  = isset( $_GET['debug'] );

        if ( $pub_id <= 0 ) {
            wp_die( esc_html__( 'Ongeldige publicatie-ID.', 'eme-vzw-helper' ), 400 );
        }

        try {
            PdfGenerator::generate_form( $pub_id, 'I', $debug );
            exit;
        } catch ( \Throwable $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'EME VZW PDF Error: ' . $e->getMessage() );
            }
            wp_die( esc_html__( 'Fout bij genereren formulier: ' . $e->getMessage(), 'eme-vzw-helper' ) );
        }
    }

    /**
     * Genereer en stream de Verklaring Bestuursverbod PDF.
     */
    public static function ajax_download_bestuursverbod(): void {
        check_ajax_referer( 'eme_vzw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        $pub_id = (int) ( $_GET['pub_id'] ?? 0 );
        if ( $pub_id <= 0 ) {
            wp_die( esc_html__( 'Ongeldige publicatie-ID.', 'eme-vzw-helper' ), 400 );
        }

        try {
            PdfGenerator::generate_bestuursverbod( $pub_id, 'I' );
            exit;
        } catch ( \Throwable $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'EME VZW Bestuursverbod PDF Error: ' . $e->getMessage() );
            }
            wp_die( esc_html__( 'Fout bij genereren verklaring: ' . $e->getMessage(), 'eme-vzw-helper' ) );
        }
    }
}
