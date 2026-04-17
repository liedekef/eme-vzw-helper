<?php
namespace EMEVzw;

defined( 'ABSPATH' ) || exit;

class Installer {

    const DB_VERSION_OPTION = 'emevzw_db_version';
    const DB_VERSION        = '3';

    public static function activate(): void {
        self::create_tables();
        self::migrate();
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
        //flush_rewrite_rules();
    }

    /**
     * Run migrations for version upgrades.
     */
    private static function migrate(): void {
        global $wpdb;
        $current_version = (int) get_option( self::DB_VERSION_OPTION, '0' );

        if ( $current_version && $current_version < 3 ) {
            $tables = [
                "{$wpdb->prefix}emevzw_board",
                "{$wpdb->prefix}emevzw_publications",
                "{$wpdb->prefix}emevzw_patrimony",
            ];
            foreach ( $tables as $table ) {
                $wpdb->query( "ALTER TABLE {$table} DROP INDEX idx_config" );
                $wpdb->query( "ALTER TABLE {$table} DROP COLUMN config_id" );
            }

            // emevzw_checks: make fiscal_year nullable, update unique key
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}emevzw_checks DROP COLUMN config_id" );
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}emevzw_checks MODIFY fiscal_year SMALLINT DEFAULT NULL" );
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}emevzw_checks DROP INDEX idx_config" );
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}emevzw_checks DROP INDEX uniq_check" );
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}emevzw_checks ADD UNIQUE KEY uniq_check (fiscal_year, check_key)" );

            // emevzw_patrimony: fix unique key (already had fiscal_year NOT NULL)
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}emevzw_patrimony DROP INDEX uniq_year" );
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}emevzw_patrimony ADD UNIQUE KEY uniq_year (fiscal_year)" );
        }
    }

    public static function deactivate(): void {
        //flush_rewrite_rules();
    }

    public static function uninstall(): void {
        if ( get_option( 'eme_vzw_delete_on_uninstall' ) !== 'yes' ) return;
        self::drop_tables();
        delete_option( self::DB_VERSION_OPTION );
        delete_option( 'emevzw_config' );
    }

    public static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        // ── VZW Configuration (one row per VZW) ─────────────────────────
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}emevzw_config (
            id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            vzw_name                VARCHAR(200)    NOT NULL DEFAULT '',
            vzw_shortname           VARCHAR(100)    NOT NULL DEFAULT '',
            vzw_number              VARCHAR(12)     NOT NULL DEFAULT '',
            vzw_legal_form          VARCHAR(50)     NOT NULL DEFAULT 'VZW',
            street                  VARCHAR(150)    NOT NULL DEFAULT '',
            house_number            VARCHAR(20)     NOT NULL DEFAULT '',
            house_bus               VARCHAR(20)     NOT NULL DEFAULT '',
            postal_code             VARCHAR(10)     NOT NULL DEFAULT '',
            city                    VARCHAR(100)    NOT NULL DEFAULT '',
            country                 VARCHAR(50)     NOT NULL DEFAULT 'België',
            court                   VARCHAR(150)    NOT NULL DEFAULT '',
            fiscal_year_start_mm    TINYINT         NOT NULL DEFAULT 1,
            fiscal_year_start_dd    TINYINT         NOT NULL DEFAULT 1,
            fiscal_year_end_mm      TINYINT         NOT NULL DEFAULT 12,
            fiscal_year_end_dd      TINYINT         NOT NULL DEFAULT 31,
            vat_liable              TINYINT(1)      NOT NULL DEFAULT 0,
            is_micro_vzw            TINYINT(1)      NOT NULL DEFAULT 1,
            ubo_last_confirmed      DATE                     DEFAULT NULL,
            statutes_last_published DATE                     DEFAULT NULL,
            board_mandate_end       DATE                     DEFAULT NULL,
            notification_email      VARCHAR(150)    NOT NULL DEFAULT '',
            created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

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
            role                VARCHAR(50)     NOT NULL DEFAULT 'bestuurder',
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
        ) $charset;" );

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
        ) $charset;" );

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
        ) $charset;" );

        // ── Staat van het Vermogen (Bijlage C – per boekjaar) ───────────
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}emevzw_patrimony (
            id                          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            fiscal_year                 SMALLINT        NOT NULL,

            -- Sectie 1: Waarderingsregels (art. 6)
            depr_machines_new_years     TINYINT         NOT NULL DEFAULT 5,
            depr_machines_used_years    TINYINT         NOT NULL DEFAULT 2,
            depr_computers_new_years    TINYINT         NOT NULL DEFAULT 5,
            depr_computers_used_years   TINYINT         NOT NULL DEFAULT 2,
            depr_furniture_new_years    TINYINT         NOT NULL DEFAULT 5,
            depr_furniture_used_years   TINYINT         NOT NULL DEFAULT 2,
            depr_other_new_years        TINYINT         NOT NULL DEFAULT 5,
            depr_other_used_years       TINYINT         NOT NULL DEFAULT 2,
            depr_method                 VARCHAR(12)     NOT NULL DEFAULT 'linear',
            cash_valuation_note         TEXT,

            -- Sectie 2: Aanpassing waarderingsregels (art. 7)
            valuation_changes           TEXT,

            -- Sectie 3: Bijkomende inlichtingen (art. 14)
            additional_info             TEXT,

            -- Sectie 4: Bezittingen
            asset_real_estate           DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            asset_machines              DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            asset_movables              DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            asset_stocks                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            asset_receivables           DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            asset_investments           DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            asset_cash                  DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            asset_other                 DECIMAL(12,2)   NOT NULL DEFAULT 0.00,

            -- Sectie 4: Schulden
            liab_financial              DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            liab_suppliers              DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            liab_members                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            liab_fiscal_social          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            liab_other                  DECIMAL(12,2)   NOT NULL DEFAULT 0.00,

            -- Sectie 4: Rechten
            right_subsidies             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            right_donations             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            right_other                 DECIMAL(12,2)   NOT NULL DEFAULT 0.00,

            -- Sectie 4: Verplichtingen
            oblig_mortgages             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            oblig_guarantees            DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
            oblig_other                 DECIMAL(12,2)   NOT NULL DEFAULT 0.00,

            -- Sectie 5
            non_numeric_obligations     TEXT,

            status                      ENUM('draft','final') NOT NULL DEFAULT 'draft',
            notes                       TEXT,
            created_at                  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at                  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_year (fiscal_year)
        ) $charset;" );

        // ── Publication board members (who changed in this publication) ──
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}emevzw_pub_board (
            id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            publication_id  INT UNSIGNED    NOT NULL,
            board_id        INT UNSIGNED    NOT NULL,
            change_type     ENUM('B','E') NOT NULL DEFAULT 'B',
            effective_date  DATE                     DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_pub (publication_id)
        ) $charset;" );
        // change_type: B = Benoeming, E = Einde/ontslag
    }

    private static function drop_tables(): void {
        global $wpdb;
        foreach ( [ 'emevzw_pub_board','emevzw_publications','emevzw_checks','emevzw_patrimony','emevzw_board','emevzw_config' ] as $t ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$t}" ); // phpcs:ignore
        }
    }
}
