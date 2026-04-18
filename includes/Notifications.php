<?php
namespace EMEVzw;

defined( 'ABSPATH' ) || exit;

class Notifications {

    public static function init(): void {
        add_action( 'eme_vzw_daily_check', [ __CLASS__, 'run_checks' ] );
        if ( ! wp_next_scheduled( 'eme_vzw_daily_check' ) ) {
            wp_schedule_event( time(), 'daily', 'eme_vzw_daily_check' );
        }
    }

    public static function run_checks(): void {
        $config = Config::get();
        if ( ! $config || ! $config->notification_email ) return;

        $today    = new \DateTime( current_time( 'Y-m-d' ) );
        $warnings = [];

        // Board mandate end warning (30 days)
        if ( $config->board_mandate_end ) {
            $end  = new \DateTime( $config->board_mandate_end );
            $diff = $today->diff( $end );
            if ( $end >= $today && $diff->days <= 30 ) {
                $warnings[] = sprintf(
                    __( 'Bestuursmandaat loopt af op %s (nog %d dagen).', 'eme-vzw-helper' ),
                    date_i18n( 'd/m/Y', strtotime( $config->board_mandate_end ) ),
                    $diff->days
                );
            }
        }

        // UBO annual confirmation (warn if > 11 months ago)
        if ( $config->ubo_last_confirmed ) {
            $last = new \DateTime( $config->ubo_last_confirmed );
            $diff = $last->diff( $today );
            if ( $diff->days >= 335 ) {
                $warnings[] = __( 'UBO-register: jaarlijkse bevestiging vereist. Laatste bevestiging was meer dan 11 maanden geleden.', 'eme-vzw-helper' );
            }
        }

        // Patrimonium tax (30 March reminder)
        $march31 = new \DateTime( $today->format( 'Y' ) . '-03-31' );
        if ( $today <= $march31 ) {
            $diff = $today->diff( $march31 );
            if ( $diff->days <= 14 ) {
                $warnings[] = __( 'Aangifte Patrimoniumtaks: deadline is 31 maart.', 'eme-vzw-helper' );
            }
        }

        if ( empty( $warnings ) ) return;

        wp_mail(
            $config->notification_email,
            sprintf( __( '[%s] VZW Compliance – Actie vereist', 'eme-vzw-helper' ), $config->vzw_name ),
            implode( "\n\n", $warnings ) . "\n\n" . admin_url( 'admin.php?page=emevzwhelper' )
        );
    }
}
