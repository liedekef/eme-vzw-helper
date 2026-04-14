<?php
defined( 'ABSPATH' ) || exit;

$config      = EMEVzw\Config::get();
$cur_year    = (int) ( $config->current_fiscal_year ?? gmdate( 'Y' ) );
$edit_year   = isset( $_GET['fiscal_year'] ) ? (int) $_GET['fiscal_year'] : $cur_year;
$p           = EMEVzw\Patrimony::get( $edit_year );
$all_records = EMEVzw\Patrimony::get_all();

// Helper: waarde tonen met decimalen
$val = fn( string $field ) => $p ? number_format( (float) $p->$field, 2, ',', '.' ) : '0,00';
$txt = fn( string $field ) => $p ? esc_attr( $p->$field ) : '';
$int = fn( string $field, int $default ) => $p ? (int) $p->$field : $default;
?>
<div class="wrap vzw-wrap">
<h1><?php esc_html_e( 'Staat van het Vermogen', 'eme-vzw-helper' ); echo " Jaar ".esc_html($edit_year);?></h1>
<p class="description">
    <?php esc_html_e( 'Bijlage C bij de enkelvoudige boekhouding – verplicht voor micro-VZW\'s (KB 29 april 2019, art. 6, 7 en 14).', 'eme-vzw-helper' ); ?>
</p>

<!-- Boekjaar selector -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
    <label><strong><?php esc_html_e( 'Boekjaar:', 'eme-vzw-helper' ); ?></strong>
        <select id="vzw-patrimony-year">
            <?php foreach ( range( (int) gmdate( 'Y' ), (int) gmdate( 'Y' ) - 10 ) as $y ): ?>
            <option value="<?= $y ?>" <?= selected( $y, $edit_year, false ) ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php if ( $p ): ?>
    <a href="<?= esc_url( admin_url( 'admin-ajax.php?action=eme_vzw_download_patrimony&fiscal_year=' . $edit_year . '&nonce=' . wp_create_nonce( 'eme_vzw_nonce' ) ) ) ?>"
       class="button button-primary" target="_blank">
        📄 <?php esc_html_e( 'Download PDF', 'eme-vzw-helper' ); ?>
    </a>
    <span class="status-badge status-<?= esc_attr( $p->status ?? 'draft' ) ?>">
        <?= $p->status === 'final' ? esc_html__( 'Definitief', 'eme-vzw-helper' ) : esc_html__( 'Concept', 'eme-vzw-helper' ) ?>
    </span>
    <?php endif; ?>
</div>

<form id="vzw-patrimony-form">
<input type="hidden" name="fiscal_year" value="<?= $edit_year ?>">
<input type="hidden" name="config_id" value="1">

<!-- ══════════════════════════════════════════════════════════════════
     SECTIE 1 – WAARDERINGSREGELS (art. 6)
     ══════════════════════════════════════════════════════════════════ -->
<div class="vzw-form-box">
<h2><?php esc_html_e( '1. Waarderingsregels (art. 6)', 'eme-vzw-helper' ); ?></h2>
<p class="description"><?php esc_html_e( 'Afschrijvingsperiodes per categorie. Standaardwaarden zijn vooringevuld conform de gebruikelijke VZW-praktijk.', 'eme-vzw-helper' ); ?></p>

<table class="widefat striped" style="margin-bottom:12px">
    <thead><tr>
        <th><?php esc_html_e( 'Categorie', 'eme-vzw-helper' ); ?></th>
        <th><?php esc_html_e( 'Nieuw (jaren)', 'eme-vzw-helper' ); ?></th>
        <th><?php esc_html_e( 'Tweedehands (jaren)', 'eme-vzw-helper' ); ?></th>
    </tr></thead>
    <tbody>
    <?php
    $categories = [
        [ 'machines',   __( 'Machines en elektrische toestellen', 'eme-vzw-helper' ), 5, 2 ],
        [ 'computers',  __( 'Computers en informaticamateriaal',  'eme-vzw-helper' ), 5, 2 ],
        [ 'furniture',  __( 'Meubilair',                          'eme-vzw-helper' ), 5, 2 ],
        [ 'other',      __( 'Andere roerende goederen',           'eme-vzw-helper' ), 5, 2 ],
    ];
    foreach ( $categories as [ $key, $label, $def_new, $def_used ] ): ?>
    <tr>
        <td><?= esc_html( $label ) ?></td>
        <td><input type="number" name="depr_<?= $key ?>_new_years" min="1" max="50"
                   value="<?= $int( 'depr_' . $key . '_new_years', $def_new ) ?>" style="width:60px"></td>
        <td><input type="number" name="depr_<?= $key ?>_used_years" min="1" max="50"
                   value="<?= $int( 'depr_' . $key . '_used_years', $def_used ) ?>" style="width:60px"></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="vzw-row">
    <div class="vzw-field">
        <label><?php esc_html_e( 'Afschrijvingsmethode', 'eme-vzw-helper' ); ?></label>
        <select name="depr_method">
            <option value="linear"     <?= selected( $p->depr_method ?? 'linear', 'linear', false ) ?>><?php esc_html_e( 'Lineair', 'eme-vzw-helper' ); ?></option>
            <option value="degressive" <?= selected( $p->depr_method ?? 'linear', 'degressive', false ) ?>><?php esc_html_e( 'Degressief', 'eme-vzw-helper' ); ?></option>
        </select>
    </div>
    <div class="vzw-field" style="flex:3">
        <label><?php esc_html_e( 'Toestand van liquiditeiten', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="cash_valuation_note"
               value="<?= $p ? esc_attr( $p->cash_valuation_note ) : esc_attr__( 'Toestand van bankrekeningen en kas op 31 december van het betreffende jaar.', 'eme-vzw-helper' ) ?>"
               style="width:100%">
        <span class="description"><?php esc_html_e( 'Standaard: eindejaarsstand bankrekeningen en kas.', 'eme-vzw-helper' ); ?></span>
    </div>
</div>
<div class="vzw-row">
    <div class="vzw-field" style="flex:1">
        <label><?php esc_html_e( 'Moment van afschrijven', 'eme-vzw-helper' ); ?></label>
        <p class="description" style="margin-top:4px">
            <?php esc_html_e( 'Het jaar van aankoop wordt de volledige aankoopwaarde genoteerd. Pas vanaf het jaar daarna worden de afschrijvingen toegepast.', 'eme-vzw-helper' ); ?>
        </p>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     SECTIE 2 – AANPASSING WAARDERINGSREGELS (art. 7)
     ══════════════════════════════════════════════════════════════════ -->
<div class="vzw-form-box">
<h2><?php esc_html_e( '2. Aanpassing van de waarderingsregels (art. 7)', 'eme-vzw-helper' ); ?></h2>
<p class="description"><?php esc_html_e( 'Enkel invullen indien de waarderingsregels t.o.v. vorig jaar gewijzigd werden. Laat leeg voor "Nihil".', 'eme-vzw-helper' ); ?></p>
<textarea name="valuation_changes" rows="3" placeholder="<?php esc_attr_e( 'Nihil', 'eme-vzw-helper' ); ?>"
          style="width:100%"><?= $p ? esc_textarea( $p->valuation_changes ) : '' ?></textarea>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     SECTIE 3 – BIJKOMENDE INLICHTINGEN (art. 14)
     ══════════════════════════════════════════════════════════════════ -->
<div class="vzw-form-box">
<h2><?php esc_html_e( '3. Bijkomende inlichtingen (art. 14)', 'eme-vzw-helper' ); ?></h2>
<p class="description"><?php esc_html_e( 'Bijkomende toelichting bij het vermogen. Laat leeg voor "Nihil".', 'eme-vzw-helper' ); ?></p>
<textarea name="additional_info" rows="3" placeholder="<?php esc_attr_e( 'Nihil', 'eme-vzw-helper' ); ?>"
          style="width:100%"><?= $p ? esc_textarea( $p->additional_info ) : '' ?></textarea>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     SECTIE 4 – GENORMALISEERD SCHEMA (art. 14)
     ══════════════════════════════════════════════════════════════════ -->
<div class="vzw-form-box">
<h2><?php esc_html_e( '4. Genormaliseerd schema van de Staat van het Vermogen (art. 14)', 'eme-vzw-helper' ); ?></h2>
<p class="description"><?php esc_html_e( 'Bedragen in €. Gebruik een punt of komma als decimaalteken.', 'eme-vzw-helper' ); ?></p>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

    <!-- BEZITTINGEN -->
    <div>
        <h3 style="border-bottom:2px solid #000;padding-bottom:4px"><?php esc_html_e( 'BEZITTINGEN', 'eme-vzw-helper' ); ?></h3>
        <?php
        $assets = [
            [ 'asset_real_estate', __( 'Onroerende goederen',                  'eme-vzw-helper' ) ],
            [ 'asset_machines',    __( 'Machines',                             'eme-vzw-helper' ) ],
            [ 'asset_movables',    __( 'Roerende goederen en rollend materiaal','eme-vzw-helper' ) ],
            [ 'asset_stocks',      __( 'Stocks en voorraden',                  'eme-vzw-helper' ) ],
            [ 'asset_receivables', __( 'Schuldvorderingen',                    'eme-vzw-helper' ) ],
            [ 'asset_investments', __( 'Geldbeleggingen',                      'eme-vzw-helper' ) ],
            [ 'asset_cash',        __( 'Liquiditeiten',                        'eme-vzw-helper' ) ],
            [ 'asset_other',       __( 'Andere activa',                        'eme-vzw-helper' ) ],
        ];
        foreach ( $assets as [ $name, $label ] ): ?>
        <div class="vzw-patrimony-row">
            <label><?= esc_html( $label ) ?></label>
            <input type="text" name="<?= esc_attr( $name ) ?>" value="<?= $val( $name ) ?>"
                   class="vzw-amount" data-section="assets" style="width:120px;text-align:right">
        </div>
        <?php endforeach; ?>
        <div class="vzw-patrimony-total">
            <strong><?php esc_html_e( 'Totaal bezittingen', 'eme-vzw-helper' ); ?></strong>
            <strong id="total-assets">—</strong>
        </div>

        <h3 style="border-bottom:2px solid #000;padding-bottom:4px;margin-top:20px"><?php esc_html_e( 'RECHTEN', 'eme-vzw-helper' ); ?></h3>
        <?php
        $rights = [
            [ 'right_subsidies', __( 'Beloofde subsidies',   'eme-vzw-helper' ) ],
            [ 'right_donations', __( 'Beloofde schenkingen', 'eme-vzw-helper' ) ],
            [ 'right_other',     __( 'Andere rechten',       'eme-vzw-helper' ) ],
        ];
        foreach ( $rights as [ $name, $label ] ): ?>
        <div class="vzw-patrimony-row">
            <label><?= esc_html( $label ) ?></label>
            <input type="text" name="<?= esc_attr( $name ) ?>" value="<?= $val( $name ) ?>"
                   class="vzw-amount" data-section="rights" style="width:120px;text-align:right">
        </div>
        <?php endforeach; ?>
        <div class="vzw-patrimony-total">
            <strong><?php esc_html_e( 'Totaal rechten', 'eme-vzw-helper' ); ?></strong>
            <strong id="total-rights">—</strong>
        </div>
    </div>

    <!-- SCHULDEN / VERPLICHTINGEN -->
    <div>
        <h3 style="border-bottom:2px solid #000;padding-bottom:4px"><?php esc_html_e( 'SCHULDEN', 'eme-vzw-helper' ); ?></h3>
        <?php
        $liabs = [
            [ 'liab_financial',    __( 'Financiële schulden',                         'eme-vzw-helper' ) ],
            [ 'liab_suppliers',    __( 'Schulden ten aanzien van leveranciers',        'eme-vzw-helper' ) ],
            [ 'liab_members',      __( 'Schulden ten aanzien van leden',              'eme-vzw-helper' ) ],
            [ 'liab_fiscal_social',__( 'Fiscale, salariële en sociale schulden',      'eme-vzw-helper' ) ],
            [ 'liab_other',        __( 'Andere schulden',                             'eme-vzw-helper' ) ],
        ];
        foreach ( $liabs as [ $name, $label ] ): ?>
        <div class="vzw-patrimony-row">
            <label><?= esc_html( $label ) ?></label>
            <input type="text" name="<?= esc_attr( $name ) ?>" value="<?= $val( $name ) ?>"
                   class="vzw-amount" data-section="liab" style="width:120px;text-align:right">
        </div>
        <?php endforeach; ?>
        <div class="vzw-patrimony-total">
            <strong><?php esc_html_e( 'Totaal schulden', 'eme-vzw-helper' ); ?></strong>
            <strong id="total-liab">—</strong>
        </div>

        <h3 style="border-bottom:2px solid #000;padding-bottom:4px;margin-top:20px"><?php esc_html_e( 'VERPLICHTINGEN', 'eme-vzw-helper' ); ?></h3>
        <?php
        $obligs = [
            [ 'oblig_mortgages',  __( 'Hypotheken en hypotheekbeloften', 'eme-vzw-helper' ) ],
            [ 'oblig_guarantees', __( 'Gegeven waarborgen',              'eme-vzw-helper' ) ],
            [ 'oblig_other',      __( 'Andere verbintenissen',           'eme-vzw-helper' ) ],
        ];
        foreach ( $obligs as [ $name, $label ] ): ?>
        <div class="vzw-patrimony-row">
            <label><?= esc_html( $label ) ?></label>
            <input type="text" name="<?= esc_attr( $name ) ?>" value="<?= $val( $name ) ?>"
                   class="vzw-amount" data-section="oblig" style="width:120px;text-align:right">
        </div>
        <?php endforeach; ?>
        <div class="vzw-patrimony-total">
            <strong><?php esc_html_e( 'Totaal verplichtingen', 'eme-vzw-helper' ); ?></strong>
            <strong id="total-oblig">—</strong>
        </div>

        <div class="vzw-patrimony-total" style="margin-top:16px;border-top:2px solid #000;padding-top:8px;background:#f0f6ff">
            <strong><?php esc_html_e( 'Netto vermogen (bezittingen − schulden)', 'eme-vzw-helper' ); ?></strong>
            <strong id="total-net">—</strong>
        </div>
    </div>

</div><!-- /grid -->
</div>

<!-- ══════════════════════════════════════════════════════════════════
     SECTIE 5 – RECHTEN/VERPLICHTINGEN NIET IN CIJFERS
     ══════════════════════════════════════════════════════════════════ -->
<div class="vzw-form-box">
<h2><?php esc_html_e( '5. Rechten en verplichtingen die niet in cijfers kunnen worden weergegeven (art. 14)', 'eme-vzw-helper' ); ?></h2>
<p class="description"><?php esc_html_e( 'Bv. borgtochten, lopende geschillen, erfpacht, enz. Laat leeg voor "Nihil".', 'eme-vzw-helper' ); ?></p>
<textarea name="non_numeric_obligations" rows="3" placeholder="<?php esc_attr_e( 'Nihil', 'eme-vzw-helper' ); ?>"
          style="width:100%"><?= $p ? esc_textarea( $p->non_numeric_obligations ) : '' ?></textarea>
</div>

<!-- Opties -->
<div class="vzw-form-box">
<div class="vzw-row">
    <div class="vzw-field">
        <label><?php esc_html_e( 'Status', 'eme-vzw-helper' ); ?></label>
        <select name="status">
            <option value="draft" <?= selected( $p->status ?? 'draft', 'draft', false ) ?>><?php esc_html_e( 'Concept', 'eme-vzw-helper' ); ?></option>
            <option value="final" <?= selected( $p->status ?? 'draft', 'final', false ) ?>><?php esc_html_e( 'Definitief', 'eme-vzw-helper' ); ?></option>
        </select>
    </div>
    <div class="vzw-field" style="flex:3">
        <label><?php esc_html_e( 'Interne notities', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="notes" value="<?= $p ? esc_attr( $p->notes ) : '' ?>" style="width:100%">
    </div>
</div>
<p>
    <button type="submit" class="button button-primary submit-btn"><?php esc_html_e( 'Opslaan', 'eme-vzw-helper' ); ?></button>
    <?php if ( $p ): ?>
    <button type="button" class="button vzw-delete-patrimony" data-id="<?= (int) $p->id ?>"
            style="margin-left:8px;color:#d63638;border-color:#d63638">
        <?php esc_html_e( 'Verwijder dit boekjaar', 'eme-vzw-helper' ); ?>
    </button>
    <?php endif; ?>
</p>
</div>

</form>

<!-- ══════════════════════════════════════════════════════════════════
     OVERZICHT ALLE BOEKJAREN
     ══════════════════════════════════════════════════════════════════ -->
<?php if ( $all_records ): ?>
<h2 style="margin-top:28px"><?php esc_html_e( 'Overzicht per boekjaar', 'eme-vzw-helper' ); ?></h2>
<table class="vzw-pub-table widefat">
    <thead><tr>
        <th><?php esc_html_e( 'Boekjaar', 'eme-vzw-helper' ); ?></th>
        <th><?php esc_html_e( 'Totaal bezittingen', 'eme-vzw-helper' ); ?></th>
        <th><?php esc_html_e( 'Totaal schulden', 'eme-vzw-helper' ); ?></th>
        <th><?php esc_html_e( 'Netto vermogen', 'eme-vzw-helper' ); ?></th>
        <th><?php esc_html_e( 'Status', 'eme-vzw-helper' ); ?></th>
        <th><?php esc_html_e( 'Acties', 'eme-vzw-helper' ); ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ( $all_records as $rec ):
        $t = EMEVzw\Patrimony::totals( $rec );
        $fmt = fn( float $v ) => number_format( $v, 2, ',', '.' ) . ' €';
    ?>
    <tr>
        <td><strong><?= (int) $rec->fiscal_year ?></strong></td>
        <td><?= esc_html( $fmt( $t['total_assets'] ) ) ?></td>
        <td><?= esc_html( $fmt( $t['total_liab'] ) ) ?></td>
        <td style="<?= $t['net_assets'] < 0 ? 'color:#d63638;font-weight:600' : '' ?>">
            <?= esc_html( $fmt( $t['net_assets'] ) ) ?>
        </td>
        <td><span class="status-badge status-<?= esc_attr( $rec->status ) ?>">
            <?= $rec->status === 'final' ? esc_html__( 'Definitief', 'eme-vzw-helper' ) : esc_html__( 'Concept', 'eme-vzw-helper' ) ?>
        </span></td>
        <td>
            <a href="<?= esc_url( admin_url( 'admin.php?page=eme-vzw-helper-patrimony&fiscal_year=' . (int) $rec->fiscal_year ) ) ?>"
               class="button button-small"><?php esc_html_e( 'Bewerk', 'eme-vzw-helper' ); ?></a>
            <a href="<?= esc_url( admin_url( 'admin-ajax.php?action=eme_vzw_download_patrimony&fiscal_year=' . (int) $rec->fiscal_year . '&nonce=' . wp_create_nonce( 'eme_vzw_nonce' ) ) ) ?>"
               class="button button-small" target="_blank">PDF</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

</div><!-- /wrap -->

<style>
.vzw-patrimony-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    border-bottom: 1px solid #f0f0f1;
}
.vzw-patrimony-row label { flex: 1; font-size: 13px; }
.vzw-patrimony-total {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    margin-top: 4px;
    border-top: 2px solid #000;
    font-size: 13px;
}
</style>

<script>
(function() {
    // Live berekening totalen
    function parseBE(val) {
        return parseFloat(val.replace(/\./g, '').replace(',', '.')) || 0;
    }
    function fmtBE(val) {
        return val.toLocaleString('nl-BE', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
    }

    function recalc() {
        let assets = 0, liab = 0, rights = 0, oblig = 0;
        document.querySelectorAll('.vzw-amount').forEach(function(el) {
            const v = parseBE(el.value);
            const s = el.dataset.section;
            if (s === 'assets') assets += v;
            if (s === 'liab')   liab   += v;
            if (s === 'rights') rights += v;
            if (s === 'oblig')  oblig  += v;
        });
        document.getElementById('total-assets').textContent = fmtBE(assets);
        document.getElementById('total-liab').textContent   = fmtBE(liab);
        document.getElementById('total-rights').textContent = fmtBE(rights);
        document.getElementById('total-oblig').textContent  = fmtBE(oblig);
        const net = assets - liab;
        const netEl = document.getElementById('total-net');
        netEl.textContent = fmtBE(net);
        netEl.style.color = net < 0 ? '#d63638' : '#00a32a';
    }

    document.querySelectorAll('.vzw-amount').forEach(function(el) {
        el.addEventListener('input', recalc);
    });
    recalc();

    // Boekjaar laden
    document.getElementById('vzw-patrimony-year')?.addEventListener('change', function() {
        window.location = `${window.location.pathname}?page=eme-vzw-helper-patrimony&fiscal_year=${this.value}`;
    });

    // Opslaan
    const form = document.getElementById('vzw-patrimony-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('.submit-btn');
            btn.disabled = true;
            btn.textContent = emeVzw.i18n.saving;
            const data = Object.fromEntries(new FormData(this).entries());
            data.nonce = emeVzw.nonce;
            data.action = 'eme_vzw_save_patrimony';
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams(data)
            })
            .then(r => r.json())
            .then(function(res) {
                if (res.success) {
                    btn.textContent = emeVzw.i18n.saved;
                    setTimeout(() => { btn.textContent = '<?= esc_js( __( 'Opslaan', 'eme-vzw-helper' ) ) ?>'; btn.disabled = false; }, 1800);
                } else {
                    alert(res.data || '<?= esc_js( __( 'Fout bij opslaan.', 'eme-vzw-helper' ) ) ?>');
                    btn.disabled = false;
                    btn.textContent = '<?= esc_js( __( 'Opslaan', 'eme-vzw-helper' ) ) ?>';
                }
            });
        });
    }

    // Verwijder boekjaar
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.vzw-delete-patrimony');
        if (!btn) return;
        if (!confirm('<?= esc_js( __( 'Dit boekjaar definitief verwijderen?', 'eme-vzw-helper' ) ) ?>')) return;
        fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action: 'eme_vzw_delete_patrimony', id: btn.dataset.id, nonce: emeVzw.nonce })
        })
        .then(r => r.json())
        .then(res => { if (res.success) location.href = '<?= esc_js( admin_url( 'admin.php?page=eme-vzw-helper-patrimony' ) ) ?>'; });
    });
})();
</script>
