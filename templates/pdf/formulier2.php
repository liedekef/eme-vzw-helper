<?php
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
/**
 * Template: Formulier II – Luiken A en C
 * KBO-update voor bestuurswijziging
 *
 * Variables:
 *   $pub, $config, $board_changes
 */
defined( 'ABSPATH' ) || exit;

$vzw_number = esc_html( $config->vzw_number ?? '' );
$vzw_name   = esc_html( $config->vzw_name ?? '' );
$vzw_form   = esc_html( $config->vzw_legal_form ?? 'VZW' );
$street     = esc_html( trim( ( $config->street ?? '' ) . ' ' . ( $config->house_number ?? '' ) ) );
$city       = esc_html( trim( ( $config->postal_code ?? '' ) . ' ' . ( $config->city ?? '' ) ) );
$court      = esc_html( $config->court ?? '' );
$av_date    = ! empty( $pub->av_date ) ? esc_html( date_i18n( 'd/m/Y', strtotime( $pub->av_date ) ) ) : '';
$today      = esc_html( date_i18n( 'd/m/Y' ) );
?>
<style>
.form-page{width:190mm;margin:0 auto;font-family:Arial,sans-serif;font-size:9pt;line-height:1.4;color:#000}
.form-title{font-size:11pt;font-weight:bold;text-align:center;border:2px solid #000;padding:4pt 8pt;margin-bottom:8pt}
.luik-header{background:#000;color:#fff;font-weight:bold;padding:2pt 4pt;margin:8pt 0 4pt;font-size:9.5pt}
.field-row{display:flex;align-items:flex-start;margin-bottom:3pt;gap:4pt}
.field-label{flex:0 0 55mm;font-size:8pt;padding-top:1pt}
.field-value{flex:1;border-bottom:1px solid #000;min-height:12pt;padding:0 2pt;font-size:9pt}
.board-table{width:100%;border-collapse:collapse;font-size:8.5pt;margin:4pt 0}
.board-table th{background:#ddd;border:1px solid #000;padding:2pt 3pt;text-align:left;font-weight:bold}
.board-table td{border:1px solid #000;padding:2pt 3pt;vertical-align:top}
.note-text{font-size:7.5pt;color:#333;font-style:italic}
.signature-area{border:1px solid #000;min-height:20mm;margin-top:4pt;padding:4pt}
hr{border:none;border-top:1px solid #000;margin:6pt 0}
</style>

<div class="form-page">
    <div class="form-title">
        FORMULIER II – Bijwerking Kruispuntbank van Ondernemingen (KBO)<br>
        Wijziging samenstelling bestuursorgaan – VZW / IVZW / Stichting
    </div>
    <p class="note-text" style="text-align:center">In te vullen in tweevoud – In te dienen bij de griffie van de bevoegde ondernemingsrechtbank</p>

    <!-- LUIK A -->
    <div class="luik-header">LUIK A – Identificatie van de rechtspersoon (punten 1 en 2a)</div>

    <p><strong>Punt 1 – Identificatiegegevens:</strong></p>
    <div class="field-row">
        <div class="field-label">Ondernemingsnummer:</div>
        <div class="field-value"><?php echo esc_html( $vzw_number ); ?></div>
    </div>
    <div class="field-row">
        <div class="field-label">Naam / Benaming:</div>
        <div class="field-value"><?php echo esc_html( $vzw_name ); ?></div>
    </div>
    <div class="field-row">
        <div class="field-label">Rechtsvorm:</div>
        <div class="field-value"><?php echo esc_html( $vzw_form ); ?></div>
    </div>
    <div class="field-row">
        <div class="field-label">Adres zetel:</div>
        <div class="field-value"><?php echo esc_html( $street ); ?></div>
    </div>
    <div class="field-row">
        <div class="field-label">Postcode + Gemeente:</div>
        <div class="field-value"><?php echo esc_html( $city ); ?></div>
    </div>

    <p style="margin-top:4pt"><strong>Punt 2a – Datum van de akte:</strong></p>
    <div class="field-row">
        <div class="field-label">Datum:</div>
        <div class="field-value"><?php echo esc_html( $av_date ); ?></div>
    </div>

    <!-- LUIK C -->
    <div class="luik-header">LUIK C – Wijziging samenstelling bestuursorgaan (punten 1, 2 en 3)</div>
    <p class="note-text">
        Nieuwe/herbenoemde bestuurders: voorafgaand met <strong>B</strong> (Benoeming)<br>
        Bestuurders die mandaat beëindigen: voorafgaand met <strong>E</strong> (Einde/Ontslag)<br>
        Herbenoemingen worden <em>niet</em> opgenomen in dit formulier (wel in Formulier I).
    </p>

    <p><strong>Punt 1 – Wijzigingen:</strong></p>
    <table class="board-table">
        <tr>
            <th style="width:8mm">B/E</th>
            <th style="width:12mm">RR-nr.</th>
            <th>Naam en voornaam</th>
            <th>Hoedanigheid</th>
            <th style="width:22mm">Datum beslissing AV</th>
        </tr>
        <?php foreach ( $board_changes as $m ): ?>
        <?php if ( $m->change_type === 'H' ) continue; // Herbenoemingen niet in Formulier II ?>
        <tr>
            <td style="text-align:center;font-weight:bold"><?php echo esc_html( $m->change_type ); ?></td>
            <td><?php echo esc_html( $m->national_id ); ?></td>
            <td><?php echo esc_html( $m->last_name . ' ' . $m->first_name ); ?></td>
            <td><?php echo esc_html( $m->role ); ?></td>
            <td><?php echo esc_html( $av_date ); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php
        // Empty rows for manual completion
        $rows_filled = count( array_filter( $board_changes, fn($b) => $b->change_type !== 'H' ) );
        for ( $i = $rows_filled; $i < 5; $i++ ):
        ?>
        <tr>
            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
        </tr>
        <?php endfor; ?>
    </table>

    <p class="note-text" style="margin-top:4pt">
        Indien meer dan 5 wijzigingen: druk dit blad nogmaals af en vul aan.
    </p>

    <p style="margin-top:6pt"><strong>Punt 2 – Verklaring bestuursverbod (wet 4 mei 2023):</strong></p>
    <p style="font-size:8.5pt">
        De ondergetekende verklaart het Centraal register van bestuursverboden te hebben nagekeken
        en bevestigt dat geen van de hierboven genoemde bestuurders (aangeduid met B) een actueel
        bestuursverbod heeft opgelegd gekregen door een rechtscollege van een lidstaat van de EER.
    </p>

    <p style="margin-top:6pt"><strong>Punt 3 – Handtekening:</strong></p>
    <div class="field-row">
        <div class="field-label">Datum:</div>
        <div class="field-value"><?php echo esc_html( $today ); ?></div>
        <div class="field-label" style="flex:0 0 30mm;text-align:right">Plaats:</div>
        <div class="field-value"></div>
    </div>
    <div class="field-row" style="margin-top:4pt">
        <div class="field-label">Naam ondertekenaar:</div>
        <div class="field-value"></div>
    </div>
    <div class="field-row">
        <div class="field-label">Hoedanigheid:</div>
        <div class="field-value"></div>
    </div>

    <div class="signature-area">
        <p class="note-text" style="margin:0">Handtekening:</p>
    </div>

    <hr>
    <p class="note-text">
        ✦ Griffie: <?php echo esc_html( $court ); ?><br>
        ✦ Te behandelen samen met Formulier I, betalingsbewijs en notulen.<br>
        ✦ Termijn: in te dienen binnen 30 dagen na de beslissing.
    </p>
</div>
