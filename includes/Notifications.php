<?php
namespace EMEVzw;

defined( 'ABSPATH' ) || exit;

class Notifications {

    public static function init(): void {
        add_action( 'eme_vzw_daily_check', [ __CLASS__, 'run_checks' ] );
    }

    public static function run_checks(): void {
        $config = Config::get();
        if ( ! $config || ! $config->notification_email ) return;

        $today    = new \DateTime( current_time( 'Y-m-d' ) );
        $warnings = [];
        $sent_log = get_option( 'emevzw_notification_sent', [] ); // ['key' => timestamp]

        $week_ago = time() - ( 7 * DAY_IN_SECONDS );

        // Board mandate end warning (30 dagen)
        if ( $config->board_mandate_end ) {
            $end  = new \DateTime( $config->board_mandate_end );
            $diff = $today->diff( $end );
            if ( $end >= $today && $diff->days <= 30 ) {
                if ( empty( $sent_log['board_mandate'] ) || $sent_log['board_mandate'] < $week_ago ) {
                    $warnings['board_mandate'] = sprintf(
                        // translators: %1$s is the end date of the mandate, %2$d the number of days left.
                        __( 'Bestuursmandaat loopt af op %1$s (nog %2$d dagen).', 'eme-vzw-helper' ),
                        date_i18n( 'd/m/Y', strtotime( $config->board_mandate_end ) ),
                        $diff->days
                    );
                }
            }
        }

        // UBO jaarlijkse bevestiging
        if ( $config->ubo_last_confirmed ) {
            $last = new \DateTime( $config->ubo_last_confirmed );
            $diff = $last->diff( $today );
            if ( $diff->days >= 335 ) {
                if ( empty( $sent_log['ubo'] ) || $sent_log['ubo'] < $week_ago ) {
                    $warnings['ubo'] = __( 'UBO-register: jaarlijkse bevestiging vereist. Laatste bevestiging was meer dan 11 maanden geleden.', 'eme-vzw-helper' );
                }
            }
        }

        // Patrimoniumtaks (14 dagen voor 31 maart)
        $march31 = new \DateTime( $today->format( 'Y' ) . '-03-31' );
        if ( $today <= $march31 ) {
            $diff = $today->diff( $march31 );
            if ( $diff->days <= 14 ) {
                if ( empty( $sent_log['patrimony'] ) || $sent_log['patrimony'] < $week_ago ) {
                    $warnings['patrimony'] = __( 'Aangifte Patrimoniumtaks: deadline is 31 maart.', 'eme-vzw-helper' );
                }
            }
        }

        if ( empty( $warnings ) ) return;

        wp_mail(
            $config->notification_email,
            // translators: %s is the name of the VZW.
            sprintf( __( '[%s] VZW Compliance – Actie vereist', 'eme-vzw-helper' ), $config->vzw_name ),
            implode( "\n\n", $warnings ) . "\n\n" . admin_url( 'admin.php?page=emevzwhelper' )
        );

        // Log de verzonden warnings
        foreach ( array_keys( $warnings ) as $key ) {
            $sent_log[ $key ] = time();
        }
        update_option( 'emevzw_notification_sent', $sent_log );
    }
}
