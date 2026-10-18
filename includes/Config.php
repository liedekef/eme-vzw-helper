<?php
// phpcs:ignoreFile WordPress.DB.DirectDatabaseQuery.DirectQuery WordPress.DB.DirectDatabaseQuery.NoCaching
namespace EMEVzw;

defined( 'ABSPATH' ) || exit;

class Config {

    public static function init(): void {
        add_action( 'wp_ajax_eme_vzw_save_config',         [ __CLASS__, 'ajax_save' ] );
        add_action( 'wp_ajax_eme_vzw_save_board_member',   [ __CLASS__, 'ajax_save_board' ] );
        add_action( 'wp_ajax_eme_vzw_delete_board_member', [ __CLASS__, 'ajax_delete_board' ] );
        add_action( 'wp_ajax_eme_vzw_purge_board_member',  [ __CLASS__, 'ajax_purge_board' ] );
        add_action( 'wp_ajax_eme_vzw_snapselect_searchemeperson', 'eme_ajax_chooseperson_snapselect' );
    }

    private const OPTION_NAME = 'emevzw_helper_settings';

    /**
     * Officiële wettelijke hoedanigheden (wet 4 mei 2023 + WVV).
     * Sleutel = waarde opgeslagen in DB, label = vertaalde weergavenaam.
     */
    public static function legal_capacities(): array {
        return [
            'bestuurder'                                             => __( 'Bestuurder', 'eme-vzw-helper' ),
            'zaakvoerder'                                            => __( 'Zaakvoerder', 'eme-vzw-helper' ),
            'vaste vertegenwoordiger rechtspersoon'                  => __( 'Vaste vertegenwoordiger rechtspersoon', 'eme-vzw-helper' ),
            'plaatsvervangend vaste vertegenwoordiger rechtspersoon' => __( 'Plaatsvervangend vaste vertegenwoordiger rechtspersoon', 'eme-vzw-helper' ),
            'lid van de toezichtraad'                                => __( 'Lid van de toezichtraad', 'eme-vzw-helper' ),
            'lid van het directiecomité'                             => __( 'Lid van het directiecomité', 'eme-vzw-helper' ),
            'lid van de directieraad'                                => __( 'Lid van de directieraad', 'eme-vzw-helper' ),
            'vereffenaar'                                            => __( 'Vereffenaar', 'eme-vzw-helper' ),
            'wettelijk vertegenwoordiger'                            => __( 'Wettelijk vertegenwoordiger', 'eme-vzw-helper' ),
        ];
    }

    /**
     * Interne functies (optioneel, voor vertegenwoordiging en weergave).
     * Sleutel = waarde opgeslagen in DB, label = vertaalde weergavenaam.
     */
    public static function function_titles(): array {
        return [
            ''                     => __( '— Geen —', 'eme-vzw-helper' ),
            'voorzitter'           => __( 'Voorzitter', 'eme-vzw-helper' ),
            'ondervoorzitter'      => __( 'Ondervoorzitter', 'eme-vzw-helper' ),
            'secretaris'           => __( 'Secretaris', 'eme-vzw-helper' ),
            'penningmeester'       => __( 'Penningmeester', 'eme-vzw-helper' ),
            'dagelijks bestuurder' => __( 'Dagelijks bestuurder', 'eme-vzw-helper' ),
        ];
    }

    /**
     * Vertaal een DB-sleutel naar het gelokaliseerde label.
     * Geeft de sleutel zelf terug als er geen match is (veilige fallback).
     */
    public static function legal_capacity_label( string $key ): string {
        return self::legal_capacities()[ $key ] ?? $key;
    }

    public static function function_title_label( string $key ): string {
        return self::function_titles()[ $key ] ?? $key;
    }

    public static function get(): object {
        $defaults = [
            'vzw_name'             => '',
            'vzw_shortname'       => '',
            'vzw_number'         => '',
            'vzw_legal_form'     => 'Vereniging Zonder Winstoogmerk (VZW)',
            'vzw_legal_short_form' => 'VZW',
            'street'             => '',
            'house_number'       => '',
            'house_bus'         => '',
            'postal_code'       => '',
            'city'              => '',
            'country'          => 'België',
            'website'          => '',
            'email'            => '',
            'court'            => '',
            'court_address'    => '',
            'fiscal_year_start_mm' => 1,
            'fiscal_year_start_dd' => 1,
            'fiscal_year_end_mm'   => 12,
            'fiscal_year_end_dd' => 31,
            'currency'         => 'EUR',
            'ubo_last_confirmed'  => null,
            'rpb_last_confirmed'  => null,
            'board_mandate_end'=> null,
            'notification_email' => '',
            'vat_liable'         => 0,
        ];
        return (object) wp_parse_args( get_option( self::OPTION_NAME, [] ), $defaults );
    }

    public static function get_active_board_members(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM `{$wpdb->prefix}emevzw_board`
             WHERE is_active = 1
             ORDER BY last_name"
        );
    }

    /**
     * Alle bestuurders, ongeacht is_active, gesorteerd:
     * actieve leden eerst, daarna inactieve, beide alfabetisch op achternaam.
     */
    public static function get_all_board_members(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM `{$wpdb->prefix}emevzw_board`
             ORDER BY is_active DESC, last_name"
        );
    }

    public static function get_board_member( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}emevzw_board` WHERE id = %d",
            $id
        ) );
    }

    /**
     * Actieve bestuurders met functie secretaris/voorzitter
     */
    public static function get_legal_representatives(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM `{$wpdb->prefix}emevzw_board`
             WHERE is_active = 1
             AND function_title IN ('voorzitter','president','chair','secretaris','secretary')
             ORDER BY last_name"
        );
    }

    public static function save( array $data ): int|\WP_Error {
        if ( ! empty( $_POST['delete_on_uninstall'] ) ) {
            update_option( 'emevzw_helper_delete_on_uninstall', sanitize_key( $_POST['delete_on_uninstall'] ) );
        }

        $currency = strtoupper( substr( eme_sanitize_request( $data['currency'] ?? 'EUR' ), 0, 3 ) );
        if ( ! self::isValidCurrencyCode( $currency ) ) {
            $currency = 'EUR';
        }

        $row = [
            'vzw_name'             => eme_sanitize_request( $data['vzw_name'] ?? '' ),
            'vzw_shortname'        => eme_sanitize_request( $data['vzw_shortname'] ?? '' ),
            'vzw_number'           => eme_sanitize_request( $data['vzw_number'] ?? '' ),
            'vzw_legal_form'       => eme_sanitize_request( $data['vzw_legal_form'] ?? 'Vereniging Zonder Winstoogmerk (VZW)' ),
            'vzw_legal_short_form' => eme_sanitize_request( $data['vzw_legal_short_form'] ?? 'VZW' ),
            'street'               => eme_sanitize_request( $data['street'] ?? '' ),
            'house_number'         => eme_sanitize_request( $data['house_number'] ?? '' ),
            'house_bus'            => eme_sanitize_request( $data['house_bus'] ?? '' ),
            'postal_code'          => eme_sanitize_request( $data['postal_code'] ?? '' ),
            'city'                 => eme_sanitize_request( $data['city'] ?? '' ),
            'country'              => eme_sanitize_request( $data['country'] ?? 'België' ),
            'website'              => eme_sanitize_request( $data['website'] ?? '' ),
            'email'                => eme_sanitize_request( $data['email'] ?? '' ),
            'court'                => eme_sanitize_request( $data['court'] ?? '' ),
            'court_address'       => eme_sanitize_request( $data['court_address'] ?? '' ),
            'currency'             => $currency,
            'fiscal_year_start_mm' => max( 1, min( 12, (int) ( $data['fiscal_year_start_mm'] ?? 1 ) ) ),
            'fiscal_year_start_dd' => max( 1, min( 31, (int) ( $data['fiscal_year_start_dd'] ?? 1 ) ) ),
            'fiscal_year_end_mm'   => max( 1, min( 12, (int) ( $data['fiscal_year_end_mm'] ?? 12 ) ) ),
            'fiscal_year_end_dd'   => max( 1, min( 31, (int) ( $data['fiscal_year_end_dd'] ?? 31 ) ) ),
            'ubo_last_confirmed'   => ! empty( $data['ubo_last_confirmed'] ) ? eme_sanitize_request( $data['ubo_last_confirmed'] ) : null,
            'rpb_last_confirmed'   => ! empty( $data['rpb_last_confirmed'] ) ? eme_sanitize_request( $data['rpb_last_confirmed'] ) : null,
            'board_mandate_end'    => ! empty( $data['board_mandate_end'] ) ? eme_sanitize_request( $data['board_mandate_end'] ) : null,
            'notification_email'   => eme_sanitize_email( $data['notification_email'] ?? '' ),
            'vat_liable'           => (int) (bool) ( $data['vat_liable'] ?? 0 ),
        ];

        update_option( self::OPTION_NAME, $row );
        return 1;
    }

    public static function save_board_member( array $data ): int|\WP_Error {
        global $wpdb;
        $row = [

            'first_name'           => eme_sanitize_request( $data['first_name'] ?? '' ),
            'last_name'            => eme_sanitize_request( $data['last_name'] ?? '' ),
            'national_id'          => eme_sanitize_request( $data['national_id'] ?? '' ),
            'birth_place'          => eme_sanitize_request( $data['birth_place'] ?? '' ),
            'birth_date'           => ! empty( $data['birth_date'] ) ? eme_sanitize_request( $data['birth_date'] ) : null,
            'street'               => eme_sanitize_request( $data['street'] ?? '' ),
            'house_number'         => eme_sanitize_request( $data['house_number'] ?? '' ),
            'house_bus'            => eme_sanitize_request( $data['house_bus'] ?? '' ),
            'postal_code'          => eme_sanitize_request( $data['postal_code'] ?? '' ),
            'city'                 => eme_sanitize_request( $data['city'] ?? '' ),
            'country'              => eme_sanitize_request( $data['country'] ?? 'België' ),
            'legal_capacity'       => eme_sanitize_request( $data['legal_capacity'] ?? 'bestuurder' ),
            'function_title'       => eme_sanitize_request( $data['function_title'] ?? '' ),
            'email'                => sanitize_email( $data['email'] ?? '' ),
            'phone'                => eme_sanitize_request( $data['phone'] ?? '' ),
            'appointed_date'       => ! empty( $data['appointed_date'] ) ? eme_sanitize_request( $data['appointed_date'] ) : null,
            'appointment_av_date'  => ! empty( $data['appointment_av_date'] ) ? eme_sanitize_request( $data['appointment_av_date'] ) : null,
            'end_date'             => ! empty( $data['end_date'] ) ? eme_sanitize_request( $data['end_date'] ) : null,
            'mandate_duration'     => (int) ( $data['mandate_duration'] ?? 0 ),
            'no_management_ban'    => (int) (bool) ( $data['no_management_ban'] ?? 0 ),
            'is_active'            => (int) (bool) ( $data['is_active'] ?? 1 ),
            'is_legal_entity'      => (int) (bool) ( $data['is_legal_entity'] ?? 0 ),
            'legal_name'           => eme_sanitize_request( $data['legal_name'] ?? '' ),
            'legal_number'         => eme_sanitize_request( $data['legal_number'] ?? '' ),
            'legal_representative' => eme_sanitize_request( $data['legal_representative'] ?? '' ),
            'notes'                => eme_sanitize_request( $data['notes'] ?? '' ),
        ];
        $id = isset( $data['id'] ) ? (int) $data['id'] : 0;
        if ( $id > 0 ) {
            $wpdb->update( $wpdb->prefix . 'emevzw_board', $row, [ 'id' => $id ], null, [ '%d' ] );
            return $id;
        }
        $wpdb->insert( $wpdb->prefix . 'emevzw_board', $row );
        return (int) $wpdb->insert_id;
    }

    public static function ajax_save(): void {
        check_ajax_referer( 'eme_vzw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        $r = self::save( $_POST );
        is_wp_error( $r ) ? wp_send_json_error( $r->get_error_message() ) : wp_send_json_success( [ 'id' => $r ] );
    }

    public static function ajax_save_board(): void {
        check_ajax_referer( 'eme_vzw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        $r = self::save_board_member( $_POST );
        is_wp_error( $r ) ? wp_send_json_error( $r->get_error_message() ) : wp_send_json_success( [ 'id' => $r ] );
    }

    public static function ajax_delete_board(): void {
        check_ajax_referer( 'eme_vzw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        global $wpdb;
        // Soft delete: is_active = 0, mandaat leegmaken
        $wpdb->update(
            $wpdb->prefix . 'emevzw_board',
            [ 'is_active' => 0 ],
            [ 'id' => (int) eme_sanitize_request( $_POST['id'] ?? 0 ) ] //phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        );
        wp_send_json_success();
    }

    public static function ajax_purge_board(): void {
        check_ajax_referer( 'eme_vzw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        global $wpdb;
        // Hard delete: enkel toegestaan als de bestuurder reeds inactief is
        $id = (int) eme_sanitize_request( $_POST['id'] ?? 0 ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $member = $wpdb->get_row( $wpdb->prepare(
            "SELECT is_active FROM `{$wpdb->prefix}emevzw_board` WHERE id = %d", $id
        ) );
        if ( ! $member || (int) $member->is_active === 1 ) {
            wp_send_json_error( 'Kan enkel inactieve bestuurders definitief verwijderen.' );
        }
        $wpdb->delete( $wpdb->prefix . 'emevzw_board', [ 'id' => $id ], [ '%d' ] );
        wp_send_json_success();
    }

    private static function isValidCurrencyCode($currency) {
        $currencies = \ResourceBundle::create('curr', 'ICUDATA-curr', false);
        if ($currencies === null) {
            return false;
        }

        $currencyInfo = $currencies->get('Currencies');
        return $currencyInfo !== null && $currencyInfo->get($currency) !== null;
    }

}
