<?php
defined( 'ABSPATH' ) || exit;
$config = EMEVzw\Config::get();
$emevzw_helper_delete_on_uninstall = get_option('emevzw_helper_delete_on_uninstall');
?>
<div class="wrap vzw-wrap">
<h1><?php esc_html_e( 'Instellingen VZW', 'eme-vzw-helper' ); ?></h1>

<div class="vzw-form-box">
<h2><?php esc_html_e( 'Identificatie', 'eme-vzw-helper' ); ?></h2>
<form id="vzw-config-form">

<div class="vzw-row">
    <div class="vzw-field" style="flex:2">
        <label><?php esc_html_e( 'Naam VZW', 'eme-vzw-helper' ); ?> *</label>
        <input type="text" name="vzw_name" required value="<?= esc_attr( $config->vzw_name ?? '' ) ?>">
    </div>
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Afkorting naam', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="vzw_shortname" value="<?= esc_attr( $config->vzw_shortname ?? '' ) ?>">
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'Ondernemingsnummer', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="vzw_number" value="<?= esc_attr( $config->vzw_number ?? '' ) ?>" placeholder="0000.000.000">
    </div>
    <div class="vzw-field" style="max-width:100px">
        <label><?php esc_html_e( 'Rechtsvorm', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="vzw_legal_form" value="<?= esc_attr( $config->vzw_legal_form ?? 'VZW' ) ?>">
    </div>
</div>

<div class="vzw-row">
    <div class="vzw-field" style="flex:2">
        <label><?php esc_html_e( 'Straat', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="street" value="<?= esc_attr( $config->street ?? '' ) ?>">
    </div>
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Nr.', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="house_number" value="<?= esc_attr( $config->house_number ?? '' ) ?>">
    </div>
    <div class="vzw-field" style="max-width:50px">
        <label><?php esc_html_e( 'Bus', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="house_bus" value="<?= esc_attr( $config->house_bus ?? '' ) ?>">
    </div>
    <div class="vzw-field" style="max-width:70px">
        <label><?php esc_html_e( 'Postcode', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="postal_code" value="<?= esc_attr( $config->postal_code ?? '' ) ?>">
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'Gemeente', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="city" value="<?= esc_attr( $config->city ?? '' ) ?>">
    </div>
</div>

<div class="vzw-row">
    <div class="vzw-field" style="flex:2">
        <label><?php esc_html_e( 'Website', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="website" value="<?= esc_attr( $config->website ?? '' ) ?>">
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'E-mail', 'eme-vzw-helper' ); ?></label>
        <input type="email" name="email" value="<?= esc_attr( $config->email ?? '' ) ?>">
    </div>
</div>

<div class="vzw-row">
    <div class="vzw-field">
        <label>
            <input type="checkbox" name="vat_liable" value="1" <?= checked( 1, (int) ( $config->vat_liable ?? 0 ), false ) ?>>
            <?php esc_html_e( 'De vereniging is BTW-plichtig', 'eme-vzw-helper' ); ?>
        </label>
        <span class="description"><?php esc_html_e( 'Vink aan als de VZW onderworpen is aan de BTW-aangifte. Hierdoor verschijnen BTW-deadlines in het dashboard.', 'eme-vzw-helper' ); ?></span>
    </div>
</div>

<div class="vzw-row">
    <div class="vzw-field" style="flex:2">
        <label>
            <?php esc_html_e( 'Bevoegde Ondernemingsrechtbank', 'eme-vzw-helper' ); ?>
            <button type="button" class="vzw-help-btn" data-id="vzw-help-rpr">?</button>
        </label>
        <input type="text" name="court" value="<?= esc_attr( $config->court ?? '' ) ?>" placeholder="Ondernemingsrechtbank">
        <p class="description"><?php esc_html_e( 'Voorbeeld: "RPR Antwerpen, afdeling Mechelen". Klik op het help-icoontje voor de correcte vereiste vermelding.', 'eme-vzw-helper' ); ?></p>
    </div>
    <div class="vzw-field" style="flex:2">
        <label><?php esc_html_e( 'Adres rechtbank', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="court_address" value="<?= esc_attr( $config->court_address ?? '' ) ?>" placeholder="Straat, postcode, stad">
    </div>
</div>
<dialog id="vzw-help-rpr" class="vzw-help-dialog">
    <h4><?php esc_html_e( 'Wat betekent RPR?', 'eme-vzw-helper' ); ?></h4>
    <p><?php esc_html_e( 'RPR is de afkorting voor rechtspersonenregister. De plaats die na RPR weergegeven wordt, geeft aan onder welk gerechtelijk arrondissement een rechtspersoon valt of maw welke ondernemingsrechtbank of rechtbank van koophandel bevoegd is.', 'eme-vzw-helper' ); ?></p>
    <p><?php esc_html_e( 'Artikel 2:20 van het Wetboek van vennootschappen en verenigingen (WVV) verplicht rechtspersonen om op al hun uitgaande correspondentie (zoals facturen) het woord rechtspersonenregister of RPR, gevolgd door de rechtbank van de zetel van de rechtspersoon, te vermelden.', 'eme-vzw-helper' ); ?></p>
    <h4><?php esc_html_e( 'Welk RPR moet ik op mijn factuur vermelden?', 'eme-vzw-helper' ); ?></h4>
<p>
De indeling van deze rechtbanken van koophandel is de volgende:<br>
<b>Antwerpen</b>, met afdelingen Antwerpen, Hasselt, Mechelen, Tongeren en Turnhout<br>
<b>Brussel</b><br>
<b>Gent</b>, met afdelingen Brugge, Dendermonde, Gent, Ieper, Kortrijk, Oostende, Oudenaarde en Veurne<br>
<b>Leuven</b><br>
<b>Nijvel</b><br>
<b>Bergen-Charleroi</b>, met afdelingen Bergen, Charleroi en Doornik<br>
<b>Luik</b>, met afdelingen Arlon, Dinant, Hoei, Luik, Marche-en-Famenne, Namen, Neufchâteau en Verviers<br>
<b>Eupen</b>
</p>
    <h4><?php esc_html_e( 'Officiële vermelding', 'eme-vzw-helper' ); ?></h4>
<p>
Voorbeeld: Waar vroeger voor bijvoorbeeld Dendermonde "RPR Dendermonde" vermeld moest worden, moet nu "RPR Gent, Afdeling Dendermonde" staan
</p>
    <div class="dialog-footer"><button type="button" commandfor="vzw-help-rpr" command="close"><?php esc_html_e( 'Sluiten', 'eme-vzw-helper' ); ?></button></div>
</dialog>

<h3><?php esc_html_e( 'Boekjaar en valuta', 'eme-vzw-helper' ); ?></h3>
<div class="vzw-row">
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Start dag', 'eme-vzw-helper' ); ?></label>
        <input type="number" name="fiscal_year_start_dd" value="<?= (int) ( $config->fiscal_year_start_dd ?? 1 ) ?>" min="1" max="31">
    </div>
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Start maand', 'eme-vzw-helper' ); ?></label>
        <input type="number" name="fiscal_year_start_mm" value="<?= (int) ( $config->fiscal_year_start_mm ?? 1 ) ?>" min="1" max="12">
    </div>
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Einde dag', 'eme-vzw-helper' ); ?></label>
        <input type="number" name="fiscal_year_end_dd" value="<?= (int) ( $config->fiscal_year_end_dd ?? 31 ) ?>" min="1" max="31">
    </div>
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Einde maand', 'eme-vzw-helper' ); ?></label>
        <input type="number" name="fiscal_year_end_mm" value="<?= (int) ( $config->fiscal_year_end_mm ?? 12 ) ?>" min="1" max="12">
    </div>
</div>
<div class="vzw-row">
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Valuta', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="currency" value="<?= esc_attr( $config->currency ) ?>" maxlength="3" style="width:60px">
        <p class="description"><?php esc_html_e( 'ISO-valutacode, bv. EUR', 'eme-vzw-helper' ); ?></p>
    </div>
</div>

<h3><?php esc_html_e( 'Deadlines', 'eme-vzw-helper' ); ?></h3>
<div class="vzw-row">
    <div class="vzw-field">
        <label><?php esc_html_e( 'Einde bestuursmandaat', 'eme-vzw-helper' ); ?></label>
        <input type="date" name="board_mandate_end" value="<?= esc_attr( $config->board_mandate_end ?? '' ) ?>">
        <span class="description"><?php esc_html_e( 'Datum waarop het huidig bestuur zijn mandaat verliest. Herinnering 30 dagen op voorhand.', 'eme-vzw-helper' ); ?></span>
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'UBO-register laatste bevestiging', 'eme-vzw-helper' ); ?></label>
        <input type="date" name="ubo_last_confirmed" value="<?= esc_attr( $config->ubo_last_confirmed ?? '' ) ?>">
        <span class="description"><?php esc_html_e( 'Jaarlijkse bevestiging vereist. Herinnering bij > 11 maanden.', 'eme-vzw-helper' ); ?></span>
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'E-mail voor herinneringen', 'eme-vzw-helper' ); ?></label>
        <input type="email" name="notification_email" value="<?= esc_attr( $config->notification_email ?? '' ) ?>">
        <span class="description"><?php esc_html_e( 'Herinneringen worden max 1 keer per week verstuurd.', 'eme-vzw-helper' ); ?></span>
    </div>
</div>

<div class="vzw-row" style="border: 1px solid #d63638; padding: 10px;">
    <div class="vzw-field">
    <h2 style="color:#d63638"><?php esc_html_e( 'Gevaarzone', 'eme-vzw-helper' ); ?></h2>
    <label><?php esc_html_e( 'Data verwijderen bij deactivatie', 'eme-vzw-helper' ); ?></label>
        <select name="delete_on_uninstall">
            <option value="no"  <?= selected( 'no',  $emevzw_helper_delete_on_uninstall, false ) ?>><?php esc_html_e( 'Nee – data bewaren (aanbevolen)', 'eme-vzw-helper' ); ?></option>
            <option value="yes" <?= selected( 'yes', $emevzw_helper_delete_on_uninstall, false ) ?>><?php esc_html_e( 'Ja – ALLE data verwijderen bij uninstall', 'eme-vzw-helper' ); ?></option>
        </select>
        <p class="description" style="color:#d63638"><?php esc_html_e( 'LET OP: "Ja" verwijdert permanent alle instellingen en tabellen!', 'eme-vzw-helper' ); ?></p>
    </div>
</div>

<p><button type="submit" class="button button-primary submit-btn"><?php esc_html_e( 'Instellingen opslaan', 'eme-vzw-helper' ); ?></button></p>
</form>

</div>

<div class="vzw-form-box" style="background:#f9f9ff">
<h2><?php esc_html_e( 'Nuttige links', 'eme-vzw-helper' ); ?></h2>
<ul style="margin:0;padding-left:20px;font-size:13px;line-height:1.8">
    <li><a href="https://kbo.economie.fgov.be/" target="_blank"><?php esc_html_e( 'Kruispuntbank van Ondernemingen (KBO)', 'eme-vzw-helper' ); ?></a></li>
    <li><a href="https://justitie.belgium.be/nl/zoek_een_rechtbank_of_parket" target="_blank"><?php esc_html_e( 'Zoek een ondernemingsrechtbank in je buurt', 'eme-vzw-helper' ); ?></a></li>
    <li><a href="https://www.ejustice.just.fgov.be/cgi_tsv_pub/page.pl?language=nl&type=formu" target="_blank"><?php esc_html_e( 'Belgisch Staatsblad - Richtlijnen publicatieformulieren', 'eme-vzw-helper' ); ?></a></li>
    <li><a href="https://www.ejustice.just.fgov.be/cgi_tsv_pub/page.pl?language=nl&type=p" target="_blank"><?php esc_html_e( 'Belgisch Staatsblad – publicatiekosten', 'eme-vzw-helper' ); ?></a></li>
    <li><a href="https://financien.belgium.be/nl/E-services/Ubo-register" target="_blank"><?php esc_html_e( 'UBO-register uitleg', 'eme-vzw-helper' ); ?></a></li>
    <li><a href="https://www.minfin.fgov.be/ubo/" target="_blank"><?php esc_html_e( 'Toegang tot UBO-register', 'eme-vzw-helper' ); ?></a></li>
    <li><a href="https://financien.belgium.be/nl/vzws/aangifte/patrimoniumtaks" target="_blank"><?php esc_html_e( 'Patrimoniumtaks – FOD Financiën', 'eme-vzw-helper' ); ?></a></li>
    <li><a href="https://eservices.minfin.fgov.be/taxonweb/" target="_blank"><?php esc_html_e( 'Tax-on-Web – Rechtspersonenbelasting', 'eme-vzw-helper' ); ?></a></li>
    <li><a href="https://justban.just.fgov.be/" target="_blank"><?php esc_html_e( 'Centraal Register van Bestuursverboden', 'eme-vzw-helper' ); ?></a></li>
</ul>
</div>

