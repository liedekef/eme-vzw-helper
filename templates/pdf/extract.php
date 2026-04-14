<?php
/**
 * Template: Uittreksel uit de notulen van de Algemene Vergadering
 * Variables: $pub, $config, $board_changes
 */
defined( 'ABSPATH' ) || exit;

$vzw_name   = esc_html( $config->vzw_name ?? '' );
$vzw_form   = esc_html( $config->vzw_legal_form ?? 'VZW' );
$vzw_number = esc_html( $config->vzw_number ?? '' );
$street     = esc_html( trim( ( $config->street ?? '' ) . ' ' . ( $config->house_number ?? '' ) ) );
$city       = esc_html( trim( ( $config->postal_code ?? '' ) . ' ' . ( $config->city ?? '' ) ) );
$av_date    = ! empty( $pub->av_date ) ? esc_html( date_i18n( 'd F Y', strtotime( $pub->av_date ) ) ) : '';
$today      = esc_html( date_i18n( 'd F Y' ) );
?>
<style>
.extract-page{width:190mm;margin:0 auto;font-family:"Times New Roman",Times,serif;font-size:10pt;line-height:1.6;color:#000}
.extract-header{text-align:center;margin-bottom:16pt}
.extract-title{font-size:14pt;font-weight:bold;text-transform:uppercase;margin-bottom:4pt}
.extract-subtitle{font-size:10pt}
.extract-body{text-align:justify}
.signature-block{display:flex;justify-content:space-between;margin-top:30pt}
.sig-col{width:40%;text-align:center}
.sig-line{border-top:1px solid #000;margin-top:20pt;padding-top:2pt;font-size:9pt}
.board-list{list-style:none;padding:0;margin:4pt 0 4pt 12pt}
.board-list li{margin:2pt 0}
</style>

<div class="extract-page">
    <div class="extract-header">
        <div class="extract-title"><?= $vzw_form ?> <?= $vzw_name ?></div>
        <div class="extract-subtitle">
            Ondernemingsnummer: <?= $vzw_number ?><br>
            <?= $street ?>, <?= $city ?>
        </div>
    </div>

    <div class="extract-body">
        <p style="text-align:center;font-weight:bold;font-size:12pt">
            UITTREKSEL UIT DE NOTULEN VAN DE ALGEMENE VERGADERING<br>
            van <?= $av_date ?>
        </p>

        <p>
            Op <?= $av_date ?> vergaderde de Algemene Vergadering van <?= $vzw_form ?> <?= $vzw_name ?>,
            met maatschappelijke zetel te <?= $street ?>, <?= $city ?>.
        </p>

        <?php
        $benoemingen = array_filter( $board_changes, fn($b) => $b->change_type === 'B' );
        $ontslagen   = array_filter( $board_changes, fn($b) => $b->change_type === 'E' );
        $herben      = array_filter( $board_changes, fn($b) => $b->change_type === 'H' );
        ?>

        <?php if ( $benoemingen ): ?>
        <p><strong>BENOEMING BESTUURDER(S)</strong></p>
        <p>
            De Algemene Vergadering heeft, met de vereiste meerderheid van stemmen,
            volgende persoon/personen benoemd als bestuurder, met ingang van <?= $av_date ?>:
        </p>
        <ul class="board-list">
            <?php foreach ( $benoemingen as $m ): ?>
            <li>
                – <strong><?= esc_html( $m->last_name . ' ' . $m->first_name ) ?></strong>,
                wonende te <?= esc_html( trim( $m->street . ' ' . $m->house_number ) ) ?>,
                <?= esc_html( $m->postal_code . ' ' . $m->city ) ?>,
                in de hoedanigheid van <?= esc_html( $m->role ) ?>.
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if ( $herben ): ?>
        <p><strong>HERBENOEMING BESTUURDER(S)</strong></p>
        <p>
            De Algemene Vergadering heeft, met de vereiste meerderheid van stemmen,
            het mandaat verlengd van volgende bestuurder(s), met ingang van <?= $av_date ?>:
        </p>
        <ul class="board-list">
            <?php foreach ( $herben as $m ): ?>
            <li>
                – <strong><?= esc_html( $m->last_name . ' ' . $m->first_name ) ?></strong>,
                wonende te <?= esc_html( trim( $m->street . ' ' . $m->house_number ) ) ?>,
                <?= esc_html( $m->postal_code . ' ' . $m->city ) ?>,
                in de hoedanigheid van <?= esc_html( $m->role ) ?>.
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if ( $ontslagen ): ?>
        <p><strong>ONTSLAG BESTUURDER(S)</strong></p>
        <p>
            De Algemene Vergadering heeft kennisgenomen van het ontslag van / heeft ontslag verleend aan
            volgende bestuurder(s), met ingang van <?= $av_date ?>:
        </p>
        <ul class="board-list">
            <?php foreach ( $ontslagen as $m ): ?>
            <li>
                – <strong><?= esc_html( $m->last_name . ' ' . $m->first_name ) ?></strong>,
                wonende te <?= esc_html( trim( $m->street . ' ' . $m->house_number ) ) ?>,
                <?= esc_html( $m->postal_code . ' ' . $m->city ) ?>,
                in de hoedanigheid van <?= esc_html( $m->role ) ?>.
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <p>
            Opgemaakt te <?= esc_html( $config->city ?? '' ) ?>, op <?= $today ?>.
        </p>
    </div>

    <div class="signature-block">
        <div class="sig-col">
            <div class="sig-line">Voorzitter van de vergadering</div>
        </div>
        <div class="sig-col">
            <div class="sig-line">Secretaris</div>
        </div>
    </div>
</div>
