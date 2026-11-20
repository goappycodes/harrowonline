<?php
/**
 * Plans and prices for Harrow businesses. URL: /pricing/
 * Every text is the page's "Page text" box (Pages > Pricing); the £ amounts come from the billing module, so they
 * always match checkout. Data: hub_pages_pricing() (app/modules/pages).
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-pricing',
	'description' => 'Claim your Harrow Online Hub listing free, or choose Featured or Local Partner for priority placement, Local Offers, events and newsletter features. Monthly or annual billing.',
	'hubnav' => 'pricing',
	'title' => 'Plans and prices for Harrow businesses',   // page title and description: Yoast SEO box, else these
) );
get_header();

$hub_p = hub_pages_pricing();
$hub_t = $hub_p['text'];
$hub_e = function ( $k ) use ( $hub_t ) { return esc_html( $hub_t[ $k ] ); };   // a text field, escaped
$hub_h = function ( $html ) { return wp_kses( hub_html_links( $html ), array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ), 'strong' => array(), 'em' => array(), 'br' => array() ) ); };   // a little HTML (links)
$hub_paid = function ( $key, $btn_class ) use ( $hub_p, $hub_t ) {   // a paid plan's price, note and buttons
	$pl = $hub_p['plans'][ $key ];
	echo '<p class="plan__price"><span class="price-monthly">' . esc_html( $pl['monthly'] ) . '<small>' . esc_html( $hub_t['pricing_per_month'] ) . '</small></span><span class="price-annual">' . esc_html( $pl['annual'] ) . '<small>' . esc_html( $hub_t['pricing_per_year'] ) . "</small></span></p>\n";
	echo '        <p class="plan__note"><span class="price-monthly">' . esc_html( $pl['note_monthly'] ) . '</span><span class="price-annual">' . esc_html( $pl['note_annual'] ) . "</span></p>\n";
	foreach ( array( 'monthly', 'annual' ) as $per ) {
		echo '        <a class="ho-btn ' . $btn_class . ' ho-btn--block price-' . $per . '" href="' . esc_url( hub_url( 'billing/checkout/?plan=' . $key . '&amp;billing=' . $per ) ) . '">' . esc_html( $hub_t[ 'pricing_' . $key . '_button' ] ) . "</a>\n";
	}
};
$hub_ticks = function ( $lead, array $items ) {
	echo '<ul class="ticks">' . ( '' !== $lead ? '<li class="ticks__lead">' . esc_html( $lead ) . '</li>' : '' );
	foreach ( $items as $i ) {
		echo '<li>' . esc_html( $i ) . '</li>';
	}
	echo "</ul>\n";
};
?>
<header class="page-head page-head--center ho-container">
  <p class="eyebrow"><?php echo $hub_e( 'pricing_eyebrow' ); ?></p>
  <h1 class="page-head__title"><?php echo $hub_e( 'pricing_title' ); ?></h1>
  <p class="page-head__lead"><?php echo $hub_e( 'pricing_lead' ); ?></p>
</header>

<div data-billing="monthly">
  <div class="ho-container">
    <div class="center mb-6">
      <div class="billing-toggle" role="group" aria-label="Billing period">
        <button type="button" data-billing-set="monthly" aria-pressed="true"><?php echo $hub_e( 'pricing_monthly_label' ); ?></button>
        <button type="button" data-billing-set="annual" aria-pressed="false"><?php echo $hub_e( 'pricing_annual_label' ); ?> <span class="save"><?php echo $hub_e( 'pricing_annual_save' ); ?></span></button>
      </div>
    </div>

    <div class="plans">
      <section class="plan" aria-labelledby="plan-basic">
        <h2 class="plan__name" id="plan-basic"><?php echo $hub_e( 'pricing_basic_name' ); ?></h2>
        <p class="plan__for"><?php echo $hub_e( 'pricing_basic_for' ); ?></p>
        <p class="plan__price"><?php echo $hub_e( 'pricing_basic_price' ); ?><small><?php echo $hub_e( 'pricing_basic_price_small' ); ?></small></p>
        <p class="plan__note"><?php echo $hub_e( 'pricing_basic_note' ); ?></p>
        <a class="ho-btn ho-btn--outline ho-btn--block" href="<?php echo esc_url( $hub_t['pricing_basic_link'] ); ?>"><?php echo $hub_e( 'pricing_basic_button' ); ?></a>
        <?php $hub_ticks( '', $hub_t['pricing_basic_features'] ); ?>
      </section>

      <section class="plan plan--popular" aria-labelledby="plan-featured">
<?php if ( ! $hub_t['pricing_featured_flag_hide'] ) : ?>
        <span class="plan__flag"><?php echo $hub_e( 'pricing_featured_flag' ); ?></span>
<?php endif; ?>
        <h2 class="plan__name" id="plan-featured"><?php echo $hub_e( 'pricing_featured_name' ); ?></h2>
        <p class="plan__for"><?php echo $hub_e( 'pricing_featured_for' ); ?></p>
        <?php $hub_paid( 'featured', 'ho-btn--primary' ); ?>
        <?php $hub_ticks( $hub_t['pricing_featured_lead'], $hub_t['pricing_featured_features'] ); ?>
      </section>

      <section class="plan plan--partner" aria-labelledby="plan-partner">
        <h2 class="plan__name" id="plan-partner"><?php echo $hub_e( 'pricing_partner_name' ); ?></h2>
        <p class="plan__for"><?php echo $hub_e( 'pricing_partner_for' ); ?></p>
        <?php $hub_paid( 'partner', 'ho-btn--outline' ); ?>
        <?php $hub_ticks( $hub_t['pricing_partner_lead'], $hub_t['pricing_partner_features'] ); ?>
      </section>
    </div>

<?php if ( ! $hub_t['pricing_oneoff_hide'] ) : ?>
    <section class="oneoff" aria-labelledby="oneoff-title">
      <span class="oneoff__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-calendar"/></svg></span>
      <div class="oneoff__main">
        <p class="oneoff__eyebrow"><?php echo $hub_e( 'pricing_oneoff_eyebrow' ); ?></p>
        <h2 class="oneoff__title" id="oneoff-title"><?php echo $hub_e( 'pricing_oneoff_title' ); ?></h2>
        <p class="oneoff__text"><?php echo $hub_e( 'pricing_oneoff_text' ); ?></p>
      </div>
      <div class="oneoff__side">
        <p class="oneoff__price"><?php echo esc_html( $hub_p['event_price'] ); ?><small><?php echo $hub_e( 'pricing_oneoff_per' ); ?></small></p>
        <a class="ho-btn ho-btn--dark oneoff__btn" href="<?php echo esc_url( $hub_t['pricing_oneoff_link'] ); ?>"><svg class="ho-icon"><use href="#i-plus"/></svg><?php echo $hub_e( 'pricing_oneoff_button' ); ?></a>
      </div>
    </section>
<?php endif; ?>

<?php if ( ! $hub_t['pricing_info_hide'] ) : ?>
    <div class="price-note">
      <svg class="ho-icon ho-icon--sm"><use href="#i-info"/></svg>
      <p><strong><?php echo $hub_e( 'pricing_info_title' ); ?></strong> <?php echo $hub_e( 'pricing_info_text' ); ?> <a href="<?php echo esc_url( $hub_t['pricing_info_link'] ); ?>"><?php echo $hub_e( 'pricing_info_link_label' ); ?></a>.</p>
    </div>
<?php endif; ?>
  </div>

<?php if ( ! $hub_t['pricing_labels_hide'] ) : ?>
  <section class="section section--tight" aria-labelledby="fb-labels">
    <div class="ho-container">
    <h2 class="sec-title mb-2" id="fb-labels"><?php echo $hub_e( 'pricing_labels_title' ); ?></h2>
    <p class="sec-sub mb-0"><?php echo $hub_e( 'pricing_labels_sub' ); ?></p>
    <ul class="status-key">
<?php foreach ( array( 'verified' => 'verified', 'checked' => 'check', 'imported' => 'globe' ) as $hub_k => $hub_icon ) : ?>
      <li><span class="status-key__name"><span class="card__tick card__tick--<?php echo esc_attr( $hub_k ); ?>" aria-hidden="true"><svg class="ho-icon"><use href="#i-<?php echo esc_attr( $hub_icon ); ?>"/></svg></span><?php echo $hub_e( 'pricing_label_' . $hub_k ); ?></span><p><?php echo $hub_e( 'pricing_label_' . $hub_k . '_text' ); ?></p></li>
<?php endforeach; ?>
    </ul>
    <p class="help mt-4"><?php echo $hub_e( 'pricing_labels_help' ); ?></p>
    </div>
  </section>
<?php endif; ?>

<?php if ( ! $hub_t['pricing_compare_hide'] ) : ?>
  <section class="section section--tight" aria-labelledby="compare-title">
    <div class="ho-container">
      <details class="compare-fold">
        <summary><span><span class="compare-fold__title" id="compare-title"><?php echo $hub_e( 'pricing_compare_title' ); ?></span><span class="compare-fold__sub"><?php
		echo str_replace( '{period}', '<span class="price-monthly">' . $hub_e( 'pricing_period_monthly' ) . '</span><span class="price-annual">' . $hub_e( 'pricing_period_annual' ) . '</span>', $hub_e( 'pricing_compare_sub' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped parts
		?></span></span><span class="compare-fold__btn"><?php echo $hub_e( 'pricing_compare_button' ); ?></span></summary>
      <div class="compare-wrap">
        <table class="compare">
          <caption class="ho-sr"><?php echo $hub_e( 'pricing_compare_caption' ); ?></caption>
          <thead>
            <tr>
              <th scope="col"><?php echo $hub_e( 'pricing_col_feature' ); ?></th>
              <th scope="col"><?php echo $hub_e( 'pricing_col_unclaimed' ); ?></th>
              <th scope="col"><?php echo $hub_e( 'pricing_col_basic' ); ?></th>
              <th scope="col" class="col-pop"><?php echo $hub_e( 'pricing_col_featured' ); ?></th>
              <th scope="col"><?php echo $hub_e( 'pricing_col_partner' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th scope="row"><?php echo $hub_e( 'pricing_compare_price_label' ); ?></th>
              <td><?php echo $hub_e( 'pricing_compare_free' ); ?></td>
              <td><?php echo $hub_e( 'pricing_compare_free' ); ?></td>
<?php foreach ( array( 'featured' => ' class="col-pop"', 'partner' => '' ) as $hub_k => $hub_cls ) : $hub_pl = $hub_p['plans'][ $hub_k ]; ?>
              <td<?php echo $hub_cls; // phpcs:ignore ?>><span class="price-monthly"><?php echo esc_html( $hub_pl['monthly'] . $hub_t['pricing_per_month'] ); ?></span><span class="price-annual"><?php echo esc_html( $hub_pl['annual'] . $hub_t['pricing_per_year'] ); ?></span></td>
<?php endforeach; ?>
            </tr>
<?php
foreach ( $hub_p['compare'] as $hub_row ) {
	if ( $hub_row['heading'] ) {
		echo '            <tr class="grp"><th scope="colgroup" colspan="5">' . esc_html( $hub_row['feature'] ) . "</th></tr>\n";
		continue;
	}
	echo '            <tr><th scope="row">' . esc_html( $hub_row['feature'] ) . '</th>';
	foreach ( $hub_row['cells'] as $hub_i => $hub_c ) {
		$hub_pop = ! $hub_row['span'] && 2 === $hub_i;   // the Featured column
		$hub_cls = trim( ( 'text' === $hub_c['kind'] ? '' : $hub_c['kind'] ) . ( $hub_pop ? ' col-pop' : '' ) );
		echo '<td' . ( $hub_row['span'] ? ' colspan="4"' : '' ) . ( '' !== $hub_cls ? ' class="' . esc_attr( $hub_cls ) . '"' : '' ) . '>';
		if ( 'yes' === $hub_c['kind'] ) {
			echo '<svg class="ho-icon"><use href="#i-check"/></svg><span class="ho-sr">' . esc_html( $hub_c['sr'] ) . '</span>';
		} elseif ( 'no' === $hub_c['kind'] ) {
			echo '&ndash;<span class="ho-sr">' . esc_html( $hub_c['sr'] ) . '</span>';
		} else {
			echo $hub_h( $hub_c['html'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses
		}
		echo '</td>';
	}
	echo "</tr>\n";
}
?>
          </tbody>
        </table>
      </div>
      </details>
    </div>
  </section>
<?php endif; ?>
</div>

<?php if ( ! $hub_t['pricing_billing_hide'] ) : ?>
<section class="section section--soft" aria-labelledby="billing-title">
  <div class="ho-container">
    <div class="sec-head"><div><h2 class="sec-title" id="billing-title"><?php echo $hub_e( 'pricing_billing_title' ); ?></h2><p class="sec-sub"><?php echo $hub_e( 'pricing_billing_sub' ); ?></p></div></div>
    <div class="grid grid-3">
<?php foreach ( $hub_t['pricing_billing_panels'] as $hub_b ) : ?>
      <div class="panel mt-0">
        <span class="tile__icon mb-4" aria-hidden="true"><svg class="ho-icon"><use href="#i-<?php echo esc_attr( sanitize_key( $hub_b['icon'] ) ); ?>"/></svg></span>
        <h3 class="panel__title"><?php echo esc_html( $hub_b['title'] ); ?></h3>
        <p class="small mb-0"><?php echo $hub_h( $hub_b['text'] ); // phpcs:ignore ?></p>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ( ! $hub_t['pricing_faq_hide'] ) : ?>
<section class="section" aria-labelledby="faq-title">
  <div class="ho-container">
    <h2 class="sec-title mb-5" id="faq-title"><?php echo $hub_e( 'pricing_faq_title' ); ?></h2>
    <div class="faq faq--2col">
<?php foreach ( $hub_t['pricing_faq'] as $hub_q ) : ?>
      <details>
        <summary><?php echo esc_html( $hub_q['question'] ); ?></summary>
        <div><p><?php echo $hub_h( $hub_q['answer'] ); // phpcs:ignore ?></p></div>
      </details>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ( ! $hub_t['pricing_cta_hide'] ) : ?>
<div class="ho-container">
  <div class="cta-band">
    <div><h2><?php echo $hub_e( 'pricing_cta_title' ); ?></h2><p><?php echo $hub_e( 'pricing_cta_text' ); ?></p></div>
    <div class="cta-band__actions">
<?php echo hub_view_cta_buttons( $hub_t['pricing_cta_buttons'], false ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper ?>
    </div>
  </div>
</div>
<?php endif; ?>
<?php get_footer();
