<?php
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
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
    <div class="vzw-field" style="flex:2;min-width:260px">
        <label><?php esc_html_e( 'Naam VZW', 'eme-vzw-helper' ); ?> *</label>
        <input type="text" name="vzw_name" required value="<?php echo esc_attr( $config->vzw_name ?? '' ); ?>">
    </div>
    <div class="vzw-field" style="max-width:150px">
        <label><?php esc_html_e( 'Afkorting naam', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="vzw_shortname" value="<?php echo esc_attr( $config->vzw_shortname ?? '' ); ?>">
    </div>
    <div class="vzw-field" style="max-width:160px">
        <label><?php esc_html_e( 'Ondernemingsnummer', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="vzw_number" value="<?php echo esc_attr( $config->vzw_number ?? '' ); ?>" placeholder="0000.000.000">
    </div>
    <div class="vzw-field" style="max-width:275px">
        <label>
            <?php esc_html_e( 'Rechtsvorm', 'eme-vzw-helper' ); ?>
            <button type="button" class="vzw-help-btn" command="show-modal" commandfor="vzw-help-legal-form">?</button>
        </label>
        <input type="text" name="vzw_legal_form" value="<?php echo esc_attr( $config->vzw_legal_form ?? '' ); ?>">
    </div>
    <div class="vzw-field" style="max-width:180px">
        <label>
            <?php esc_html_e( 'Rechtsvorm (afkorting)', 'eme-vzw-helper' ); ?>
            <button type="button" class="vzw-help-btn" command="show-modal" commandfor="vzw-help-legal-form">?</button>
        </label>
        <input type="text" name="vzw_legal_short_form" value="<?php echo esc_attr( $config->vzw_legal_short_form ?? '' ); ?>">
    </div>
</div>
<dialog id="vzw-help-legal-form" class="vzw-help-dialog">
    <h4><?php esc_html_e( 'Rechtsvorm', 'eme-vzw-helper' ); ?></h4>
    <p><?php esc_html_e( 'De wettelijke vermelding voor de rechtsvorm van een VZW is "Vereniging zonder winstoogmerk (VZW)".', 'eme-vzw-helper' ); ?></p>
    <p><?php esc_html_e( 'De wettelijke afkorting hiervan is "VZW".', 'eme-vzw-helper' ); ?></p>
    <p><?php esc_html_e( 'Verander dit enkel indien noodzakelijk.', 'eme-vzw-helper' ); ?></p>
    <div class="dialog-footer"><button type="button" commandfor="vzw-help-legal-form" command="close"><?php esc_html_e( 'Sluiten', 'eme-vzw-helper' ); ?></button></div>
</dialog>

<div class="vzw-row">
    <div class="vzw-field" style="flex:2">
        <label><?php esc_html_e( 'Straat', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="street" value="<?php echo esc_attr( $config->street ?? '' ); ?>">
    </div>
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Nr.', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="house_number" value="<?php echo esc_attr( $config->house_number ?? '' ); ?>">
    </div>
    <div class="vzw-field" style="max-width:50px">
        <label><?php esc_html_e( 'Bus', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="house_bus" value="<?php echo esc_attr( $config->house_bus ?? '' ); ?>">
    </div>
    <div class="vzw-field" style="max-width:70px">
        <label><?php esc_html_e( 'Postcode', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="postal_code" value="<?php echo esc_attr( $config->postal_code ?? '' ); ?>">
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'Gemeente', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="city" value="<?php echo esc_attr( $config->city ?? '' ); ?>">
    </div>
</div>

<div class="vzw-row">
    <div class="vzw-field" style="flex:2">
        <label><?php esc_html_e( 'Website', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="website" value="<?php echo esc_attr( $config->website ?? '' ); ?>">
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'E-mail', 'eme-vzw-helper' ); ?></label>
        <input type="email" name="email" value="<?php echo esc_attr( $config->email ?? '' ); ?>">
    </div>
</div>

<div class="vzw-row">
    <div class="vzw-field">
        <label>
            <input type="checkbox" name="vat_liable" value="1" <?php echo checked( 1, (int) ( $config->vat_liable ?? 0 ), false ); ?>>
            <?php esc_html_e( 'De vereniging is BTW-plichtig', 'eme-vzw-helper' ); ?>
        </label>
        <span class="description"><?php esc_html_e( 'Vink aan als de VZW onderworpen is aan de BTW-aangifte. Hierdoor verschijnen BTW-deadlines in het dashboard.', 'eme-vzw-helper' ); ?></span>
    </div>
</div>

<div class="vzw-row">
    <div class="vzw-field" style="flex:2">
        <label>
            <?php esc_html_e( 'Bevoegde Ondernemingsrechtbank', 'eme-vzw-helper' ); ?>
            <button type="button" class="vzw-help-btn" command="show-modal" commandfor="vzw-help-rpr">?</button>
        </label>
        <input type="text" name="court" value="<?php echo esc_attr( $config->court ?? '' ); ?>" placeholder="Ondernemingsrechtbank">
        <p class="description"><?php esc_html_e( 'Voorbeeld: "RPR Antwerpen, afdeling Mechelen". Klik op het help-icoontje voor de correcte vereiste vermelding.', 'eme-vzw-helper' ); ?></p>
    </div>
    <div class="vzw-field" style="flex:2">
        <label><?php esc_html_e( 'Adres rechtbank', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="court_address" value="<?php echo esc_attr( $config->court_address ?? '' ); ?>" placeholder="Straat, postcode, stad">
    </div>
</div>
<dialog id="vzw-help-rpr" class="vzw-help-dialog">
    <h4><?php esc_html_e( 'Wat betekent RPR?', 'eme-vzw-helper' ); ?></h4>
    <p><?php esc_html_e( 'RPR is de afkorting voor rechtspersonenregister. De plaats die na RPR weergegeven wordt, geeft aan onder welk gerechtelijk arrondissement een rechtspersoon valt of maw welke ondernemingsrechtbank of rechtbank van koophandel bevoegd is.', 'eme-vzw-helper' ); ?></p>
    <p><?php esc_html_e( 'Artikel 2:20 van het Wetboek van vennootschappen en verenigingen (WVV) verplicht rechtspersonen om op al hun uitgaande correspondentie (zoals facturen) het woord rechtspersonenregister of RPR, gevolgd door de rechtbank van de zetel van de rechtspersoon, te vermelden.', 'eme-vzw-helper' ); ?></p>
    <h4><?php esc_html_e( 'Welk RPR moet ik op gebruiken?', 'eme-vzw-helper' ); ?></h4>
<p>
De indeling van deze rechtbanken van koophandel is de volgende:<br>
</p>
<ul style='list-style: unset; padding-left: 10px'>
<li><b>Antwerpen</b>, met afdelingen Antwerpen, Hasselt, Mechelen, Tongeren en Turnhout</li>
<li><b>Brussel</b></li>
<li><b>Gent</b>, met afdelingen Brugge, Dendermonde, Gent, Ieper, Kortrijk, Oostende, Oudenaarde en Veurne</li>
<li><b>Leuven</b></li>
<li><b>Nijvel</b></li>
<li><b>Bergen-Charleroi</b>, met afdelingen Bergen, Charleroi en Doornik</li>
<li><b>Luik</b>, met afdelingen Arlon, Dinant, Hoei, Luik, Marche-en-Famenne, Namen, Neufchâteau en Verviers</li>
<li><b>Eupen</b></li>
</ul>
</p>
    <h4><?php esc_html_e( 'Officiële vermelding', 'eme-vzw-helper' ); ?></h4>
<p>
Gbruik "RPR" gevolgt door de locatie, met eventueel de afdeling. Een paar voorbeelden:<br>
<ul style='list-style: unset; padding-left: 10px'>
<li>RPR Gent, Afdeling Dendermonde</li>
<li>RPR Leuven</li>
</ul>
</p>
    <div class="dialog-footer"><button type="button" commandfor="vzw-help-rpr" command="close"><?php esc_html_e( 'Sluiten', 'eme-vzw-helper' ); ?></button></div>
</dialog>

<h3><?php esc_html_e( 'Boekjaar en valuta', 'eme-vzw-helper' ); ?></h3>
<div class="vzw-row">
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Start dag', 'eme-vzw-helper' ); ?></label>
        <input type="number" name="fiscal_year_start_dd" value="<?php echo (int) ( $config->fiscal_year_start_dd ?? 1 ); ?>" min="1" max="31">
    </div>
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Start maand', 'eme-vzw-helper' ); ?></label>
        <input type="number" name="fiscal_year_start_mm" value="<?php echo (int) ( $config->fiscal_year_start_mm ?? 1 ); ?>" min="1" max="12">
    </div>
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Einde dag', 'eme-vzw-helper' ); ?></label>
        <input type="number" name="fiscal_year_end_dd" value="<?php echo (int) ( $config->fiscal_year_end_dd ?? 31 ); ?>" min="1" max="31">
    </div>
    <div class="vzw-field" style="max-width:80px">
        <label><?php esc_html_e( 'Einde maand', 'eme-vzw-helper' ); ?></label>
        <input type="number" name="fiscal_year_end_mm" value="<?php echo (int) ( $config->fiscal_year_end_mm ?? 12 ); ?>" min="1" max="12">
    </div>
</div>
<div class="vzw-row">
    <div class="vzw-field" style="width:160px">
        <label><?php esc_html_e( 'Valuta', 'eme-vzw-helper' ); ?></label>
        <input type="text" readonly="readonly" name="currency" value="<?php echo esc_attr( $config->currency ?? 'EUR' ); ?>" maxlength="3" style="width:60px">
        <p class="description"><?php esc_html_e( 'Valutacode. Enkel EUR is toegelaten.', 'eme-vzw-helper' ); ?></p>
    </div>
</div>

<h3><?php esc_html_e( 'Deadlines', 'eme-vzw-helper' ); ?></h3>
<div class="vzw-row">
    <div class="vzw-field">
        <label><?php esc_html_e( 'Einde bestuursmandaat', 'eme-vzw-helper' ); ?></label>
        <input type="date" name="board_mandate_end" value="<?php echo esc_attr( $config->board_mandate_end ?? '' ); ?>">
        <span class="description"><?php esc_html_e( 'Standaard einde van het mandaat voor bestuurders zonder eigen einddatum. Herinnering 90 dagen op voorhand, en wekelijks na het verlopen.', 'eme-vzw-helper' ); ?></span>
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'UBO-register laatste bevestiging', 'eme-vzw-helper' ); ?></label>
        <input type="date" name="ubo_last_confirmed" value="<?php echo esc_attr( $config->ubo_last_confirmed ?? '' ); ?>">
        <span class="description"><?php esc_html_e( 'Jaarlijkse bevestiging vereist. Herinnering bij > 11 maanden.', 'eme-vzw-helper' ); ?></span>
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'RPB – datum laatste aangifte', 'eme-vzw-helper' ); ?></label>
        <input type="date" name="rpb_last_confirmed" value="<?php echo esc_attr( $config->rpb_last_confirmed ?? '' ); ?>">
        <span class="description"><?php printf( //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            // translators: %s is the link to the official filing deadlines page.
            esc_html__( 'Datum waarop de aangifte rechtspersonenbelasting werd ingediend. De volgende deadline wordt automatisch berekend op het einde van 7 maanden na afsluiting vorig boekjaar. Deze termijn kan afwijken, zie %s', 'eme-vzw-helper' ),
            "<a href='https://financien.belgium.be/nl/E-services/biztax/indieningstermijnen-aangiften'>" . esc_html__( 'Indieningstermijnen aangiften', 'eme-vzw-helper' ) . "</a>"
        ); ?></span>
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'E-mail voor herinneringen', 'eme-vzw-helper' ); ?></label>
        <input type="email" name="notification_email" value="<?php echo esc_attr( $config->notification_email ?? '' ); ?>">
        <span class="description"><?php esc_html_e( 'Herinneringen worden max 1 keer per week verstuurd.', 'eme-vzw-helper' ); ?></span>
    </div>
</div>

<div class="vzw-row" style="border: 1px solid #d63638; padding: 10px;">
    <div class="vzw-field">
    <h2 style="color:#d63638"><?php esc_html_e( 'Gevaarzone', 'eme-vzw-helper' ); ?></h2>
    <label><?php esc_html_e( 'Data verwijderen bij deactivatie', 'eme-vzw-helper' ); ?></label>
        <select name="delete_on_uninstall">
            <option value="no"  <?php echo selected( 'no',  $emevzw_helper_delete_on_uninstall, false ); ?>><?php esc_html_e( 'Nee – data bewaren (aanbevolen)', 'eme-vzw-helper' ); ?></option>
            <option value="yes" <?php echo selected( 'yes', $emevzw_helper_delete_on_uninstall, false ); ?>><?php esc_html_e( 'Ja – ALLE data verwijderen bij uninstall', 'eme-vzw-helper' ); ?></option>
        </select>
        <p class="description" style="color:#d63638"><?php esc_html_e( 'LET OP: "Ja" verwijdert permanent alle instellingen en tabellen!', 'eme-vzw-helper' ); ?></p>
    </div>
</div>

<p><button type="submit" class="button button-primary submit-btn"><?php esc_html_e( 'Instellingen opslaan', 'eme-vzw-helper' ); ?></button></p>
</form>

</div>

<?php require(EME_VZW_DIR . 'templates/usefull_links.php'); ?>

</div>
