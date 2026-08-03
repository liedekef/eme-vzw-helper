<?php
// phpcs:ignoreFile WordPress.DB.DirectDatabaseQuery.DirectQuery WordPress.DB.DirectDatabaseQuery.NoCaching WordPress.DB.DirectDatabaseQuery.SchemaChange
namespace EMEVzw;

defined( 'ABSPATH' ) || exit;

class Installer {

    const DB_VERSION_OPTION = 'emevzw_db_version';
    const DB_VERSION        = '6';

    public static function activate(): void {
        self::create_tables();
        self::migrate();
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
        //flush_rewrite_rules();
        if ( ! wp_next_scheduled( 'eme_vzw_daily_check' ) ) {
            wp_schedule_event( time(), 'daily', 'eme_vzw_daily_check' );
        }
    }

    /**
     * Run migrations for version upgrades.
     */
    private static function migrate(): void {
        global $wpdb;
        $current_version = (int) get_option( self::DB_VERSION_OPTION, '0' );

        //if ( $current_version < 6 ) {
            // for future needs concerning table migrations
        //}
    }

    public static function deactivate(): void {
        //flush_rewrite_rules();
        $timestamp = wp_next_scheduled( 'eme_vzw_daily_check' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'eme_vzw_daily_check' );
        }
    }

    public static function uninstall(): void {
        if ( get_option( 'emevzw_helper_delete_on_uninstall' ) !== 'yes' ) return;
        self::drop_tables();
        delete_option( self::DB_VERSION_OPTION );
        delete_option( 'emevzw_helper_delete_on_uninstall' );
        delete_option( 'emevzw_helper_settings' );
    }

    public static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        // ── Board members (bestuurders) ──────────────────────────────────
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}emevzw_board (
            id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            first_name          VARCHAR(80)     NOT NULL DEFAULT '',
            last_name           VARCHAR(80)     NOT NULL DEFAULT '',
            birth_place         VARCHAR(100)    NOT NULL DEFAULT '',
            birth_date          DATE                     DEFAULT NULL,
            national_id         VARCHAR(15)     NOT NULL DEFAULT '',
            street              VARCHAR(150)    NOT NULL DEFAULT '',
            house_number        VARCHAR(20)     NOT NULL DEFAULT '',
            house_bus           VARCHAR(20)     NOT NULL DEFAULT '',
            postal_code         VARCHAR(10)     NOT NULL DEFAULT '',
            city                VARCHAR(100)    NOT NULL DEFAULT '',
            country             VARCHAR(50)     NOT NULL DEFAULT 'België',
            legal_capacity      VARCHAR(80)     NOT NULL DEFAULT 'bestuurder',
            function_title      VARCHAR(80)     NOT NULL DEFAULT '',
            email               VARCHAR(150)    NOT NULL DEFAULT '',
            phone               VARCHAR(30)     NOT NULL DEFAULT '',
            appointed_date      DATE                     DEFAULT NULL,
            appointment_av_date DATE                     DEFAULT NULL,
            end_date            DATE                     DEFAULT NULL,
            mandate_duration    SMALLINT        NOT NULL DEFAULT 0,
            no_management_ban   TINYINT(1)      NOT NULL DEFAULT 0,
            is_active           TINYINT(1)      NOT NULL DEFAULT 1,
            is_legal_entity     TINYINT(1)      NOT NULL DEFAULT 0,
            legal_name          VARCHAR(150)    NOT NULL DEFAULT '',
            legal_number        VARCHAR(20)     NOT NULL DEFAULT '',
            legal_representative VARCHAR(150)   NOT NULL DEFAULT '',
            notes               TEXT,
            created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_active (is_active)
        ) $charset;" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // ── Checklist answers (per fiscal year) ──────────────────────────
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}emevzw_checks (
            id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            fiscal_year SMALLINT                 DEFAULT NULL,
            check_key   VARCHAR(80)     NOT NULL,
            status      ENUM('yes','no','na','pending') NOT NULL DEFAULT 'pending',
            sub_answers TEXT,
            checked_by  BIGINT UNSIGNED              DEFAULT NULL,
            checked_at  DATETIME                     DEFAULT NULL,
            notes       TEXT,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_check (fiscal_year, check_key),
            KEY idx_year (fiscal_year)
        ) $charset;" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // ── Publication log (formulier 1, 2, statutenwijziging) ──────────
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}emevzw_publications (
            id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            av_date         DATE            NOT NULL,
            av_location     VARCHAR(255)    NOT NULL DEFAULT '',
            statute_text    TEXT,
            submitted_date  DATE                     DEFAULT NULL,
            published_date  DATE                     DEFAULT NULL,
            bs_payment_ref  VARCHAR(50)     NOT NULL DEFAULT '',
            bs_payment_date DATE                     DEFAULT NULL,
            bs_payment_amount DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
            status          ENUM('draft','submitted','published') NOT NULL DEFAULT 'draft',
            form1_pdf_url   VARCHAR(500)    NOT NULL DEFAULT '',
            form2_pdf_url   VARCHAR(500)    NOT NULL DEFAULT '',
            extract_pdf_url VARCHAR(500)    NOT NULL DEFAULT '',
            notes           TEXT,
            created_by      BIGINT UNSIGNED              DEFAULT NULL,
            created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status)
        ) $charset;" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // ── Publication board members (who changed in this publication) ──
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}emevzw_pub_board (
            id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            publication_id  INT UNSIGNED    NOT NULL,
            board_id        INT UNSIGNED    NOT NULL,
            change_type     ENUM('B','E') NOT NULL DEFAULT 'B',
            effective_date  DATE                     DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_pub (publication_id)
        ) $charset;" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // change_type: B = Benoeming, E = Einde/ontslag
    }

    private static function drop_tables(): void {
        global $wpdb;
        foreach ( [ 'emevzw_pub_board','emevzw_publications','emevzw_checks','emevzw_board' ] as $t ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$t}" ); // phpcs:ignore
        }
    }
}
