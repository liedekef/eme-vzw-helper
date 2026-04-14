<?php
/**
 * Template: Formulier I – Aanvraag tot bekendmaking in de Bijlagen bij het Belgisch Staatsblad
 *
 * Variables available:
 *   $pub           object   Publication row
 *   $config        object   VZW config row
 *   $board_changes array    Board member change rows (with B/E/H type)
 *   $subject_text  string   "Benoeming – Ontslag – Herbenoeming"
 *   $benoemingen   array    Board members with type=B
 *   $ontslagen     array    Board members with type=E
 *   $herben        array    Board members with type=H
 */
defined( 'ABSPATH' ) || exit;

$vzw_number   = esc_html( $config->vzw_number ?? '' );
$vzw_name     = esc_html( $config->vzw_name ?? '' );
$vzw_form     = esc_html( $config->vzw_legal_form ?? 'VZW' );
$street       = esc_html( trim( ( $config->street ?? '' ) . ' ' . ( $config->house_number ?? '' ) ) );
$city         = esc_html( trim( ( $config->postal_code ?? '' ) . ' ' . ( $config->city ?? '' ) ) );
$court        = esc_html( $config->court ?? '' );
$av_date      = ! empty( $pub->av_date ) ? esc_html( date_i18n( 'd/m/Y', strtotime( $pub->av_date ) ) ) : '';

$today = esc_html( date_i18n( 'd/m/Y' ) );

// Helper: format a board member name + address
function eme_vzw_format_board_member( object $m ): string {
    if ( $m->is_legal_entity ) {
        return sprintf(
            '%s (%s), %s, %s, vaste vertegenwoordiger: %s',
            esc_html( $m->legal_name ),
            esc_html( $m->legal_number ),
            esc_html( $m->legal_representative ),
            esc_html( $m->role ),
            esc_html( $m->first_name . ' ' . $m->last_name )
        );
    }
    return sprintf(
        '%s %s, %s %s %s, %s',
        esc_html( $m->last_name ),
        esc_html( $m->first_name ),
        esc_html( $m->street ),
        esc_html( $m->house_number ),
        esc_html( $m->postal_code . ' ' . $m->city ),
        esc_html( $m->role )
    );
}
?>
<style>
.form-page {
    width: 190mm;
    margin: 0 auto;
    font-family: Arial, sans-serif;
    font-size: 9pt;
    line-height: 1.4;
    color: #000;
}
.form-title {
    font-size: 11pt;
    font-weight: bold;
    text-align: center;
    border: 2px solid #000;
    padding: 4pt 8pt;
    margin-bottom: 8pt;
}
.form-subtitle {
    font-size: 9pt;
    text-align: center;
    margin-bottom: 10pt;
}
.luik-header {
    background: #000;
    color: #fff;
    font-weight: bold;
    padding: 2pt 4pt;
    margin: 8pt 0 4pt;
    font-size: 9.5pt;
}
.field-row {
    display: flex;
    align-items: flex-start;
    margin-bottom: 3pt;
    gap: 4pt;
}
.field-label {
    flex: 0 0 55mm;
    font-size: 8pt;
    padding-top: 1pt;
}
.field-value {
    flex: 1;
    border-bottom: 1px solid #000;
    min-height: 12pt;
    padding: 0 2pt;
    font-size: 9pt;
}
.field-value.multiline {
    border: 1px solid #000;
    min-height: 40pt;
    padding: 2pt;
}
.checkbox-row {
    display: flex;
    align-items: center;
    gap: 4pt;
    margin: 2pt 0;
    font-size: 8.5pt;
}
.checkbox-box {
    display: inline-block;
    width: 9pt;
    height: 9pt;
    border: 1pt solid #000;
    flex-shrink: 0;
    text-align: center;
    line-height: 9pt;
    font-size: 8pt;
}
.checkbox-box.checked::after { content: "X"; }
.section-box {
    border: 1px solid #000;
    padding: 4pt;
    margin: 4pt 0;
}
.board-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8.5pt;
    margin: 4pt 0;
}
.board-table th {
    background: #ddd;
    border: 1px solid #000;
    padding: 2pt 3pt;
    text-align: left;
    font-weight: bold;
}
.board-table td {
    border: 1px solid #000;
    padding: 2pt 3pt;
    vertical-align: top;
}
.board-table td.type-cell {
    font-weight: bold;
    font-size: 10pt;
    text-align: center;
    width: 8mm;
}
.signature-area {
    border: 1px solid #000;
    min-height: 30mm;
    margin-top: 4pt;
    padding: 4pt;
}
.page-break { page-break-before: always; }
.note-text { font-size: 7.5pt; color: #333; font-style: italic; }
.important { font-weight: bold; font-size: 8.5pt; }
hr { border: none; border-top: 1px solid #000; margin: 6pt 0; }
</style>

<div class="form-page">

    <!-- ═══════════════════════════════════════════════
         FORMULIER I – LUIK A + B
    ════════════════════════════════════════════════ -->
    <div class="form-title">
        AANVRAAG TOT BEKENDMAKING IN DE BIJLAGEN BIJ HET BELGISCH STAATSBLAD<br>
        Formulier I – Verenigingen en stichtingen (WVV)
    </div>
    <div class="form-subtitle">
        In te vullen in drievoud – In te dienen bij de griffie van de bevoegde ondernemingsrechtbank<br>
        <em>Verander nooit iets aan de lay-out van dit formulier (KB 29 april 2019)</em>
    </div>

    <!-- LUIK A -->
    <div class="luik-header">LUIK A – Identificatie van de rechtspersoon</div>

    <div class="field-row">
        <div class="field-label">Ondernemingsnummer:</div>
        <div class="field-value"><?= $vzw_number ?></div>
    </div>
    <div class="field-row">
        <div class="field-label">Naam / Benaming:</div>
        <div class="field-value"><?= $vzw_name ?></div>
    </div>
    <div class="field-row">
        <div class="field-label">Rechtsvorm:</div>
        <div class="field-value"><?= $vzw_form ?></div>
    </div>
    <div class="field-row">
        <div class="field-label">Adres zetel:</div>
        <div class="field-value"><?= $street ?></div>
    </div>
    <div class="field-row">
        <div class="field-label">Postcode + Gemeente:</div>
        <div class="field-value"><?= $city ?></div>
    </div>
    <div class="field-row">
        <div class="field-label">Bevoegde rechtbank:</div>
        <div class="field-value"><?= $court ?></div>
    </div>

    <!-- LUIK B -->
    <div class="luik-header">LUIK B – Uittreksel uit de akte of uit het stuk</div>
    <p class="note-text">Dit luik bevat het uittreksel dat in het Belgisch Staatsblad zal worden gepubliceerd.</p>

    <div class="field-row">
        <div class="field-label">Datum van de akte/vergadering:</div>
        <div class="field-value"><?= $av_date ?></div>
    </div>

    <div class="field-row">
        <div class="field-label">Onderwerp van de akte:</div>
        <div class="field-value"><?= esc_html( $subject_text ) ?></div>
    </div>

    <p class="important" style="margin-top:6pt">Inhoud van het uittreksel:</p>

    <?php if ( $benoemingen ): ?>
    <p class="important">Benoeming bestuurder(s):</p>
    <p>Werden benoemd als bestuurder, met ingang van <?= $av_date ?>:</p>
    <table class="board-table">
        <tr>
            <th style="width:10mm">RR-nr.</th>
            <th>Naam en voornaam</th>
            <th>Woonplaats</th>
            <th>Hoedanigheid</th>
            <th style="width:22mm">Datum beslissing</th>
        </tr>
        <?php foreach ( $benoemingen as $m ): ?>
        <tr>
            <td><?= esc_html( $m->national_id ) ?></td>
            <td><?= esc_html( $m->last_name . ' ' . $m->first_name ) ?></td>
            <td><?= esc_html( trim( $m->street . ' ' . $m->house_number . ', ' . $m->postal_code . ' ' . $m->city ) ) ?></td>
            <td><?= esc_html( $m->role ) ?></td>
            <td><?= $av_date ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <?php if ( $herben ): ?>
    <p class="important" style="margin-top:4pt">Herbenoeming bestuurder(s):</p>
    <p>Werden herbenoemd als bestuurder, met ingang van <?= $av_date ?>:</p>
    <table class="board-table">
        <tr>
            <th style="width:10mm">RR-nr.</th>
            <th>Naam en voornaam</th>
            <th>Woonplaats</th>
            <th>Hoedanigheid</th>
            <th style="width:22mm">Datum beslissing</th>
        </tr>
        <?php foreach ( $herben as $m ): ?>
        <tr>
            <td><?= esc_html( $m->national_id ) ?></td>
            <td><?= esc_html( $m->last_name . ' ' . $m->first_name ) ?></td>
            <td><?= esc_html( trim( $m->street . ' ' . $m->house_number . ', ' . $m->postal_code . ' ' . $m->city ) ) ?></td>
            <td><?= esc_html( $m->role ) ?></td>
            <td><?= $av_date ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <?php if ( $ontslagen ): ?>
    <p class="important" style="margin-top:4pt">Ontslag bestuurder(s):</p>
    <p>Beëindigden hun mandaat als bestuurder, met ingang van <?= $av_date ?>:</p>
    <table class="board-table">
        <tr>
            <th style="width:10mm">RR-nr.</th>
            <th>Naam en voornaam</th>
            <th>Woonplaats</th>
            <th>Hoedanigheid</th>
            <th style="width:22mm">Datum beslissing</th>
        </tr>
        <?php foreach ( $ontslagen as $m ): ?>
        <tr>
            <td><?= esc_html( $m->national_id ) ?></td>
            <td><?= esc_html( $m->last_name . ' ' . $m->first_name ) ?></td>
            <td><?= esc_html( trim( $m->street . ' ' . $m->house_number . ', ' . $m->postal_code . ' ' . $m->city ) ) ?></td>
            <td><?= esc_html( $m->role ) ?></td>
            <td><?= $av_date ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <div class="section-box" style="margin-top:8pt">
        <p class="important" style="margin:0 0 4pt">
            Vertegenwoordigingsbevoegde bestuurder(s) die dit formulier ondertekenen
            <em class="note-text">(voor- en achterkant)</em>:
        </p>
        <?php
        // List all currently active board members as potential signatories
        $active_board = EMEVzw\Config::get_active_board_members();
        foreach ( $active_board as $m ):
        ?>
        <div class="field-row" style="margin-bottom:2pt">
            <div class="field-label" style="flex:0 0 70mm">
                <?= esc_html( $m->last_name . ' ' . $m->first_name ) ?>
                <span class="note-text">(<?= esc_html( $m->role ) ?>)</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- PAGE BREAK: Signatures on back of last page of Luik B -->
    <div class="page-break"></div>

    <div class="form-title" style="font-size:9pt">
        ACHTERKANT – LUIK B – Handtekeningen
    </div>
    <p class="note-text">
        De handtekeningen staan op de achterkant van het laatste blad van Luik B.
        Onderteken bij voorkeur in blauwe inkt.
    </p>

    <div class="field-row" style="margin-top:8pt">
        <div class="field-label">Datum:</div>
        <div class="field-value"><?= $today ?></div>
        <div class="field-label" style="flex:0 0 30mm;text-align:right">Plaats:</div>
        <div class="field-value"></div>
    </div>

    <?php foreach ( $active_board as $m ): ?>
    <div style="margin-top:10pt">
        <p style="margin:0">
            <strong><?= esc_html( $m->last_name . ' ' . $m->first_name ) ?></strong>
            – <?= esc_html( $m->role ) ?>
        </p>
        <div class="signature-area">
            <p class="note-text" style="margin:0">Handtekening:</p>
        </div>
    </div>
    <?php endforeach; ?>

    <hr>
    <p class="note-text">
        ✦ Voeg bij dit formulier: (1) betalingsbewijs publicatiekosten Belgisch Staatsblad,
        (2) originele getekende notulen AV, (3) kopie ID kaart(en) betrokken bestuurder(s),
        (4) verklaring op eer bestuursverbod (wet 4 mei 2023).<br>
        ✦ Tarieven: zie https://www.ejustice.just.fgov.be – jaarlijks geïndexeerd.<br>
        ✦ Rekeningnummer Belgisch Staatsblad: BE 48 6792 0055 0227 – vermelding: ondernemingsnummer <?= $vzw_number ?>.
    </p>

</div>
