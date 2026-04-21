<?php
/**
 * template: hulp bij neerleggen
 * Variabelen: $p (publicatie), $config (vzw config), $changes (array van bestuurswijzigingen)
 */

defined( 'ABSPATH' ) || exit;

$config         = EMEVzw\Config::get();
$has_board      = ! empty( EMEVzw\Publications::get_board_changes( $p->id ));
$has_statutes   = ! empty( trim( $p->statute_text ?? '' ) );
$vzw_nr         = $config->vzw_number ?? '';
$court_name     = $config->court ?? 'Griffie Ondernemingsrechtbank';
$court_address  = $config->court_address ?? '';

?>
    <dialog id="vzw-howto-dialog-<?= $p->id?>" class="vzw-help-dialog vzw-howto-dialog">
            <h1><?php esc_html_e( 'Checklist neerlegging bij de griffie', 'eme-vzw-helper' ); ?></h1>

            <!-- ── STAP 1: DOCUMENTEN ── -->
            <div class="vzw-howto-step">
                <div class="vzw-howto-step-title">
                    <span class="vzw-howto-num">1</span>
                    <?php esc_html_e( 'Alle nodige documenten afdrukken', 'eme-vzw-helper' ); ?>
                </div>
                <p class="vzw-howto-note">⚠️ <?php esc_html_e( 'Print de volgende documenten', 'eme-vzw-helper' ); ?> <strong><?php esc_html_e( 'NIET recto-verso', 'eme-vzw-helper' ); ?></strong>.</p>
                <ul class="vzw-howto-list">
                    <li>
                        <span class="vzw-howto-doc">📄 <?php esc_html_e( 'Formulier I & II (PDF)', 'eme-vzw-helper' ); ?></span>
                        <span class="vzw-howto-copies"><?php esc_html_e( '3 ex. Form. I + 2 ex. Form. II — al samengebundeld in de gegenereerde PDF', 'eme-vzw-helper' ); ?></span>
                    </li>
                    <?php if ( $has_board ): ?>
                    <li>
                        <span class="vzw-howto-doc">📝 <?php esc_html_e( 'Verslag Bestuursorgaan / Algemene Vergadering', 'eme-vzw-helper' ); ?></span>
                        <span class="vzw-howto-copies"><?php esc_html_e( 'Bewijs dat de wijzigingen rechtsgeldig beslist werden.', 'eme-vzw-helper' ); ?></span>
                    </li>
                    <li>
                        <span class="vzw-howto-doc">🪪 <?php esc_html_e( 'Kopie identiteitsbewijzen (voor- én achterkant)', 'eme-vzw-helper' ); ?></span>
                        <span class="vzw-howto-copies"><?php esc_html_e( 'Van elke betrokken bestuurder (benoemd of ontslagen).', 'eme-vzw-helper' ); ?></span>
                    </li>
                    <li>
                        <span class="vzw-howto-doc">📋 <?php esc_html_e( 'Verklaring op eer bestuursverbod', 'eme-vzw-helper' ); ?></span>
                        <span class="vzw-howto-copies"><?php esc_html_e( 'Gebruik de knop "Verklaring op eer bestuursverbod" hierboven.', 'eme-vzw-helper' ); ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if ( $has_statutes ): ?>
                    <li>
                        <span class="vzw-howto-doc">📜 <?php esc_html_e( 'Gecoördineerde statuten', 'eme-vzw-helper' ); ?></span>
                        <span class="vzw-howto-copies"><?php esc_html_e( 'Volledige statutentekst met alle wijzigingen verwerkt (via VZW-Helper → Statuten).', 'eme-vzw-helper' ); ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- ── STAP 2: ONDERTEKENEN ── -->
            <div class="vzw-howto-step">
                <div class="vzw-howto-step-title">
                    <span class="vzw-howto-num">2</span>
                    <?php esc_html_e( 'Alle exemplaren ondertekenen', 'eme-vzw-helper' ); ?>
                </div>
                <ul class="vzw-howto-list">
                    <li><strong><?php esc_html_e( 'Form. I – Luik B', 'eme-vzw-helper' ); ?></strong> <?php esc_html_e( '(3 ex.): op de achterzijde van de laatste pagina: naam + handtekening van de vertegenwoordiger(s).', 'eme-vzw-helper' ); ?></li>
                    <li><strong><?php esc_html_e( 'Form. I – Luik C', 'eme-vzw-helper' ); ?></strong> <?php esc_html_e( '(3 ex.): op de voorzijde onder de gedrukte naam: handtekening.', 'eme-vzw-helper' ); ?></li>
                    <?php if ( $has_board ): ?>
                    <li><strong><?php esc_html_e( 'Verslag', 'eme-vzw-helper' ); ?></strong> <?php esc_html_e( ': elke betrokkene parafeert elke pagina; op de laatste pagina volledige naam + handtekening.', 'eme-vzw-helper' ); ?></li>
                    <li><strong><?php esc_html_e( 'Verklaring bestuursverbod', 'eme-vzw-helper' ); ?></strong> <?php esc_html_e( ': onderaan naam, hoedanigheid en handtekening van de vertegenwoordiger(s).', 'eme-vzw-helper' ); ?></li>
                    <?php endif; ?>
                    <?php if ( $has_statutes ): ?>
                    <li><strong><?php esc_html_e( 'Gecoördineerde statuten', 'eme-vzw-helper' ); ?></strong> <?php esc_html_e( ': op de laatste pagina naam + handtekening van de vertegenwoordiger(s).', 'eme-vzw-helper' ); ?></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- ── STAP 3: BETALEN ── -->
            <div class="vzw-howto-step">
                <div class="vzw-howto-step-title">
                    <span class="vzw-howto-num">3</span>
                    <?php esc_html_e( 'Betalen voor de publicatie', 'eme-vzw-helper' ); ?>
                </div>
                <p class="vzw-howto-note">⚠️ <?php esc_html_e( 'Rond 1 maart wijzigt het tarief jaarlijks. Wacht bij twijfel tot na 1 maart.', 'eme-vzw-helper' ); ?></p>
                <table class="vzw-howto-bank">
                    <tr><td><?php esc_html_e( 'Bedrag', 'eme-vzw-helper' ); ?></td>
                        <td><?php if ( ! empty( $pub->bs_payment_amount ) && (float) $pub->bs_payment_amount > 0 ): ?>
                                <strong>€ <?= number_format( (float) $pub->bs_payment_amount, 2, ',', '.' ) ?></strong>
                                <span class="vzw-howto-meta"><?php esc_html_e( '(opgeslagen bij deze publicatie)', 'eme-vzw-helper' ); ?></span>
                            <?php else: ?>
                                <strong>€ 166,25</strong>
                                <span class="vzw-howto-meta"><?php esc_html_e( '(tarief 2026 — controleer op', 'eme-vzw-helper' ); ?> <a href="https://www.ejustice.just.fgov.be/cgi_tsv_pub/page.pl?language=nl&type=p" target="_blank">Belgisch staatsblad Publicatiekosten</a>)</span>
                            <?php endif; ?>
                        </td></tr>
                    <tr><td>IBAN</td><td><code>BE48 6792 0055 0227</code></td></tr>
                    <tr><td>BIC</td><td><code>PCHQBEBB</code></td></tr>
                    <tr><td><?php esc_html_e( 'Op naam van', 'eme-vzw-helper' ); ?></td><td><?php esc_html_e( 'Belgisch Staatsblad, Antwerpsesteenweg 53, 1000 Brussel', 'eme-vzw-helper' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Mededeling', 'eme-vzw-helper' ); ?></td>
                        <td><code>"Ondernemingsnummer <?= esc_html( $vzw_nr ) ?>"</code></td></tr>
                </table>
            </div>
            <!-- ── STAP 4: OPSTUREN ── -->
            <div class="vzw-howto-step">
                <div class="vzw-howto-step-title">
                    <span class="vzw-howto-num">4</span>
                    <?php esc_html_e( 'Alles opsturen naar de griffie', 'eme-vzw-helper' ); ?>
                </div>
                <p><?php esc_html_e( 'Controleer of je deze documenten hebt:', 'eme-vzw-helper' ); ?></p>
                <ul class="vzw-howto-list">
                    <li><?php esc_html_e( '3 exemplaren Formulier I', 'eme-vzw-helper' ); ?></li>
                    <li><?php esc_html_e( '2 exemplaren Formulier II', 'eme-vzw-helper' ); ?></li>
                    <?php if ( $has_statutes ): ?><li><?php esc_html_e( 'Gecoördineerde statuten', 'eme-vzw-helper' ); ?></li><?php endif; ?>
                    <?php if ( $has_board ): ?>
                    <li><?php esc_html_e( 'Verslag Algemene Vergadering', 'eme-vzw-helper' ); ?></li>
                    <li><?php esc_html_e( 'Kopie identiteitsbewijzen betrokken bestuurders', 'eme-vzw-helper' ); ?></li>
                    <li><?php esc_html_e( 'Verklaring op eer bestuursverbod', 'eme-vzw-helper' ); ?></li>
                    <?php endif; ?>
                    <li><?php esc_html_e( 'Betalingsbewijs overschrijving', 'eme-vzw-helper' ); ?></li>
                    <li><?php esc_html_e( 'Aanbevolen: gezegelde geadresseerde omslag (naar jezelf) voor ontvangstbewijs.', 'eme-vzw-helper' ); ?></li>
                </ul>
                <?php if ( $court_address ): ?>
                <p style="margin-top:8px"><?php esc_html_e( 'Stuur op naar:', 'eme-vzw-helper' ); ?><br>
                    <strong><?= esc_html( $court_name ) ?></strong><br>
                    <?= nl2br( esc_html( $court_address ) ) ?>
                </p>
                <?php else: ?>
                <p style="margin-top:8px"><?php esc_html_e( 'Vul het griffieadres in bij', 'eme-vzw-helper' ); ?>
                    <a href="<?= esc_url( admin_url( 'admin.php?page=emevzwhelper-config' ) ) ?>"><?php esc_html_e( 'Instellingen', 'eme-vzw-helper' ); ?></a>
                    <?php esc_html_e( 'om het hier te tonen.', 'eme-vzw-helper' ); ?>
                </p>
                <?php endif; ?>
                <p style="margin-top:8px;font-size:12px;color:#646970">
                    <?php esc_html_e( 'Vragen? Bel gratis het Belgisch Staatsblad:', 'eme-vzw-helper' ); ?>
                    <strong>0800 98 809</strong> —
                    <a href="mailto:info.staatsblad@just.fgov.be">info.staatsblad@just.fgov.be</a>
                </p>
            </div>

            <div class="dialog-footer">
                <button type="button" onclick="this.closest('dialog').close()">✕ <?php esc_html_e( 'Sluiten', 'eme-vzw-helper' ); ?></button>
            </div>
        </dialog>

