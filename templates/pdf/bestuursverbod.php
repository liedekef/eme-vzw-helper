<?php
/**
 * PDF template: Bijzondere Verklaring Bestuursverbod (wet 4 mei 2023)
 * Variabelen: $pub (publicatie), $config (vzw config), $changes (array van bestuurswijzigingen)
 * Gerenderd via TCPDF (PdfGenerator) of als HTML-fallback.
 */
defined( 'ABSPATH' ) || exit;

$vzw_name    = esc_html( $config->vzw_name ?? '' );
$vzw_form    = esc_html( $config->vzw_legal_form ?? 'VZW' );
$vzw_number  = esc_html( $config->vzw_number ?? '' );
$street      = esc_html( trim( ( $config->street ?? '' ) . ' ' . ( $config->house_number ?? '' ) ) );
if ( ! empty( $config->house_bus ) ) {
    $street .= esc_html( ' bus ' . $config->house_bus );
}
$postal  = esc_html( trim( ( $config->postal_code ?? '' ) . ' ' . ( $config->city ?? '' ) ) );
$rpr     = esc_html( $config->court ?? '' );

$vertegenwoordigers = EMEVzw\Config::get_legal_representatives();

// Bouw de vertegenwoordiger-string op: "Naam, wettelijke hoedanigheid (interne functie)"
$vertegenwoordiger_str = '';
if ( ! empty( $vertegenwoordigers ) ) {
    $parts = [];
    foreach ( $vertegenwoordigers as $v ) {
        $cap   = EMEVzw\Config::legal_capacity_label( $v->legal_capacity ?? 'bestuurder' );
        $func  = ! empty( $v->function_title )
            ? EMEVzw\Config::function_title_label( $v->function_title )
            : '';
        $label = $v->first_name . ' ' . $v->last_name . ', ' . $cap;
        if ( $func ) $label .= ' (' . $func . ')';
        $parts[] = esc_html( $label );
    }
    $vertegenwoordiger_str = implode( ' en ', $parts );
}

$legal_capacities = EMEVzw\Config::legal_capacities();

// Datum vandaag
$today = date_i18n( 'd/m/Y' );
$city  = esc_html( $config->city ?? '' );

// legal_capacity is de correcte wettelijke benaming — gebruik de gelokaliseerde label via Config.
// Toon optioneel ook de interne functie tussen haakjes.
$format_hoedanigheid = function( object $c ): string {
    $cap   = EMEVzw\Config::legal_capacity_label( $c->legal_capacity ?? 'bestuurder' );
    $func  = ! empty( $c->function_title )
        ? EMEVzw\Config::function_title_label( $c->function_title )
        : '';
    return esc_html( $func ? $cap . ' (' . $func . ')' : $cap );
};
?>
<style>
  * { box-sizing: border-box; }
  body { font-family: Helvetica, Arial, sans-serif;}

  .doc-title {
      font-size: 11pt; font-weight: bold; text-align: center;
      border-top: 2px solid #000; border-bottom: 2px solid #000;
      padding: 5pt 0; margin-bottom: 10pt; text-transform: uppercase;
      letter-spacing: 0.5pt;
  }
  .doc-subtitle {
      font-size: 9pt; text-align: center; font-style: italic;
      margin-bottom: 14pt; color: #333;
  }

  .entity-block { border: 1px solid #aaa; padding: 8pt 10pt; margin-bottom: 12pt; }
  .entity-block table { width: 100%; border-collapse: collapse; font-size: 9pt; }
  .entity-block td { padding: 2pt 4pt; vertical-align: top; }
  .entity-block td.label { font-weight: bold; width: 38%; color: #222; white-space: nowrap; }
  .entity-block td.value { border-bottom: 1px dotted #bbb; }

  .section-title { font-size: 9.5pt; font-weight: bold; margin: 12pt 0 6pt; }

  .persons-table { width: 100%; border-collapse: collapse; margin: 6pt 0 12pt; font-size: 9pt; }
  .persons-table th { background: #e8e8e8; padding: 4pt 6pt; border: 1px solid #999; text-align: left; font-size: 8.5pt; }
  .persons-table td { padding: 4pt 6pt; border: 1px solid #ccc; vertical-align: top; }

  .declaration-text { font-size: 9pt; line-height: 1.6; text-align: justify; margin-bottom: 10pt; }

  .verboden-list { font-size: 8.5pt; line-height: 1.5; padding-left: 15pt; margin: 8pt 0; }
  .verboden-list li { margin-bottom: 6pt; }

  .signature-block { margin-top: 20pt; }
  .signature-table { width: 100%; border-collapse: collapse; }
  .signature-table td { vertical-align: bottom; padding: 0 12pt 0 0; width: 50%; }
  .signature-table td:last-child { padding-right: 0; }
  .sig-label { font-size: 8.5pt; color: #555; margin-bottom: 3pt; }
  .sig-line { border-bottom: 1px solid #000; height: 30pt; margin-bottom: 3pt; }
  .sig-identity { font-size: 8pt; color: #666; font-style: italic; }

  .place-date { font-size: 9pt; margin-bottom: 12pt; }
  .place-date .field { border-bottom: 1px solid #000; display: inline-block; min-width: 100pt; }

  .footer-note {
      margin-top: 14pt; font-size: 7.5pt; color: #666;
      border-top: 1px solid #ccc; padding-top: 5pt;
  }
  .footnote-ref { font-size: 7pt; vertical-align: super; }
</style>

<div class="page">

    <!-- TITEL -->
    <div class="doc-title">
        Bijzondere verklaring
    </div>
    <div class="doc-subtitle">
        in toepassing van de wet betreffende het Centraal register van bestuursverboden van 4 mei 2023
    </div>

    <!-- ENTITEIT -->
    <div class="entity-block">
        <table>
            <tr>
                <td class="label">Naam :</td>
                <td class="value"><?= $vzw_name ?></td>
            </tr>
            <tr>
                <td class="label">Rechtsvorm :</td>
                <td class="value"><?= $vzw_form ?></td>
            </tr>
            <tr>
                <td class="label">Ondernemingsnr :</td>
                <td class="value"><?= $vzw_number ?></td>
            </tr>
            <tr>
                <td class="label">Zetel :</td>
                <td class="value"><?= $street ?>, <?= $postal ?></td>
            </tr>
            <tr>
                <td class="label">RPR :</td>
                <td class="value"><?= $rpr ?></td>
            </tr>
            <tr>
                <td class="label">Vertegenwoordigd door<span class="footnote-ref">(1)</span> :</td>
                <td class="value"><?= $vertegenwoordiger_str ?></td>
            </tr>
        </table>
    </div>

    <!-- VERKLARING TEKST -->
    <p class="declaration-text">
        Verklaart/verklaren hierbij, in toepassing van <strong>artikel 13</strong> van de wet betreffende
        het Centraal register van bestuursverboden van <strong>4 mei 2023</strong>, dat tegen de hierna
        vermelde persoon/personen
    </p>

    <!-- PERSONEN TABEL -->
    <table class="persons-table">
        <thead>
            <tr>
                <th>Naam – voornaam – rijksregisternummer</th>
                <th>Hoedanigheid<span class="footnote-ref">(2)</span></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $changes ) ): ?>
                <?php foreach ( $changes as $c ): ?>
                <tr>
                    <td>
                        <?= esc_html( $c->last_name . ' ' . $c->first_name ) ?>
                        <?php if ( ! empty( $c->national_id ) ): ?>
                            – <?= esc_html( $c->national_id ) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $format_hoedanigheid( $c ) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="2" style="color:#999;font-style:italic">Geen bestuurswijzigingen gevonden.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- VERBOD TEKST -->
    <p class="declaration-text">
        <strong>geen veroordeling werd uitgesproken</strong> door een rechtscollege van een lidstaat van
        de Europese Economische Ruimte tot een verbod dat soortgelijk is aan één van de hierna vermelde
        verboden zoals opgenomen in artikel 6 van de wet van 4 mei 2023 bedoelde verboden:
    </p>

    <ol class="verboden-list">
        <li>
            het verbod een werkzaamheid of een functie uit te oefenen overeenkomstig de artikelen 1,
            1bis en 2 van het koninklijk besluit nr.&nbsp;22 van 24 oktober 1934 betreffende het
            rechterlijk verbod aan bepaalde veroordeelden en gefailleerden om bepaalde functies, beroepen
            of werkzaamheden uit te oefenen;
        </li>
        <li>
            het verbod een werkzaamheid of een functie uit te oefenen overeenkomstig artikel 3quater van
            het koninklijk besluit nr.&nbsp;22 van 24 oktober 1934 betreffende het rechterlijk verbod aan
            bepaalde veroordeelden en gefailleerden om bepaalde functies, beroepen of werkzaamheden uit te
            oefenen, en het verbod aan gefailleerden of daarmee gelijkgestelde personen om bepaalde
            functies, beroepen of werkzaamheden uit te oefenen overeenkomstig artikel&nbsp;XX.229 van het
            Wetboek van economisch recht.
        </li>
    </ol>

    <!-- DATUM & HANDTEKENING -->
    <p class="declaration-text" style="margin-top:12pt;">
        Aldus opgemaakt en getekend te
        <span class="field"><?= $city ?></span>
        &nbsp;&nbsp;op&nbsp;&nbsp;
        <span class="field"><?= $today ?></span>
    </p>

    <!-- HANDTEKENINGEN -->
    <div class="signature-block">
        <table class="signature-table">
            <?php if ( ! empty( $vertegenwoordigers ) ): ?>
                <?php foreach ( array_chunk( $vertegenwoordigers, 2 ) as $vertegenwoordigers_per2 ): ?>
                <tr>
                    <?php foreach ( $vertegenwoordigers_per2 as $v ): ?>
                    <td>
                        <div class="sig-identity">
                            <?= esc_html( $v->first_name . ' ' . $v->last_name ) ?><br>
                            <em><?= esc_html( EMEVzw\Config::legal_capacity_label( $v->legal_capacity ?? 'bestuurder' ) ) ?><?= ! empty( $v->function_title ) ? ' · ' . esc_html( EMEVzw\Config::function_title_label( $v->function_title ) ) : '' ?></em>
                        </div>
                        <div class="sig-label">Handtekening</div>
                        <div class="sig-line"></div>
                    </td>
                    <?php endforeach; ?>
                    <?php if ( count( $vertegenwoordigers_per2 ) === 1 ): ?>
                    <td></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td>
                        <div class="sig-label">Handtekening (identiteit/hoedanigheid vertegenwoordiger)</div>
                        <div class="sig-line"></div>
                    </td>
                    <td></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- VOETNOTEN -->
    <div class="footer-note">
        <p><span class="footnote-ref">(1)</span> Identiteitsgegevens + hoedanigheid van de extern bevoegde vertegenwoordigers</p>
        <p><span class="footnote-ref">(2)</span>
            <?= join(', ', array_values($legal_capacities)); ?>
        </p>
    </div>

</div>
