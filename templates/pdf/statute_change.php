<?php
/**
 * Template: Uittreksel Statutenwijziging
 * Variables: $pub, $config, $statute_changes
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
.stat-page{width:190mm;margin:0 auto;font-family:"Times New Roman",Times,serif;font-size:10pt;line-height:1.6;color:#000}
.stat-header{text-align:center;margin-bottom:16pt}
.stat-title{font-size:14pt;font-weight:bold;text-transform:uppercase}
.article-block{margin:12pt 0;padding:8pt;border-left:3pt solid #000}
.article-number{font-weight:bold;font-size:11pt;margin-bottom:4pt}
.old-text{background:#fff0f0;padding:4pt;border:1px solid #ccc;font-size:9pt;margin:3pt 0}
.new-text{background:#f0fff0;padding:4pt;border:1px solid #ccc;font-size:9pt;margin:3pt 0}
.text-label{font-weight:bold;font-size:8.5pt;margin-bottom:2pt}
.signature-block{display:flex;justify-content:space-between;margin-top:30pt}
.sig-col{width:40%;text-align:center}
.sig-line{border-top:1px solid #000;margin-top:20pt;padding-top:2pt;font-size:9pt}
.note-text{font-size:8pt;color:#555;font-style:italic}
</style>

<div class="stat-page">
    <div class="stat-header">
        <div class="stat-title"><?= $vzw_form ?> <?= $vzw_name ?></div>
        <div style="font-size:9.5pt">
            Ondernemingsnummer: <?= $vzw_number ?><br>
            <?= $street ?>, <?= $city ?>
        </div>
    </div>

    <p style="text-align:center;font-weight:bold;font-size:12pt">
        UITTREKSEL – STATUTENWIJZIGING<br>
        Goedgekeurd door de Buitengewone Algemene Vergadering van <?= $av_date ?>
    </p>

    <p>
        Op <?= $av_date ?> heeft de Buitengewone Algemene Vergadering van <?= $vzw_form ?> <?= $vzw_name ?>,
        met maatschappelijke zetel te <?= $street ?>, <?= $city ?>,
        de volgende wijzigingen aan de statuten goedgekeurd, met de vereiste 4/5 meerderheid:
    </p>

    <?php if ( empty( $statute_changes ) ): ?>
    <p class="note-text">Geen artikelwijzigingen opgenomen.</p>
    <?php endif; ?>

    <?php foreach ( $statute_changes as $i => $sc ): ?>
    <div class="article-block">
        <div class="article-number">
            Artikel <?= esc_html( $sc->article_number ) ?><?= $sc->article_title ? ' – ' . esc_html( $sc->article_title ) : '' ?>
        </div>

        <?php if ( ! empty( $sc->old_text ) ): ?>
        <div class="text-label">Oude tekst:</div>
        <div class="old-text"><?= nl2br( esc_html( $sc->old_text ) ) ?></div>
        <?php endif; ?>

        <div class="text-label">Nieuwe tekst:</div>
        <div class="new-text"><?= nl2br( esc_html( $sc->new_text ) ) ?></div>

        <?php if ( ! empty( $sc->change_reason ) ): ?>
        <p class="note-text" style="margin-top:3pt">
            Reden van wijziging: <?= esc_html( $sc->change_reason ) ?>
        </p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <p style="margin-top:12pt">
        De resterende artikelen van de statuten blijven ongewijzigd.
    </p>
    <p>
        Opgemaakt te <?= esc_html( $config->city ?? '' ) ?>, op <?= $today ?>.
    </p>

    <div class="signature-block">
        <div class="sig-col">
            <div class="sig-line">Voorzitter van de vergadering</div>
        </div>
        <div class="sig-col">
            <div class="sig-line">Secretaris</div>
        </div>
    </div>

    <hr style="margin-top:20pt">
    <p class="note-text">
        ✦ Dit uittreksel dient in te worden neergelegd bij de griffie van <?= esc_html( $config->court ?? 'de bevoegde Ondernemingsrechtbank' ) ?>
        samen met Formulier I en II, het betalingsbewijs voor het Belgisch Staatsblad en de nodige bijlagen.<br>
        ✦ Termijn: 30 dagen na goedkeuring door de BV-AV.<br>
        ✦ Bij statutenwijziging zijn volledige statuten in bijlage vereist.
    </p>
</div>
