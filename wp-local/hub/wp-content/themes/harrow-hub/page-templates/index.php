<?php
/**
 * Harrow Online Hub: local businesses, offers and what's on in Harrow (converted from src/pages/index.html).
 * URL: /
 *
 * Everything comes from the home module (app/modules/home): its text, links and switches are the homepage Page's
 * fields (wp-admin > Pages > Home, "Homepage" box); the events, offers, businesses, categories and areas come from
 * their modules.
 * Edited by hand (tools/build_theme.py leaves it alone).
 */
defined( 'ABSPATH' ) || exit;

$hub_home = hub_home_page();
$hub_s    = $hub_home['settings'];
$hub_e    = function ( $k ) use ( $hub_s ) {   // a setting, escaped for HTML
	return esc_html( $hub_s[ $k ] );
};
$hub_u    = function ( $k ) use ( $hub_s ) {   // a link setting, escaped for an attribute
	return esc_url( $hub_s[ $k ] );
};

hub_set_page( array(
	'body'        => 'page-home',
	'description' => $hub_s['home_seo_description'],
	'nav'         => 'hub',
	'title'       => $hub_s['home_seo_title'],
) );
get_header();
?>
<!-- Section order (client's ChatGPT mock-up, Nov 2026): the first screen is a simple choice. One search, four big
     buttons (businesses, What's On, classes, offers). Then this
     weekend: two events, one offer and the newsletter. Then more offers, the numbers, featured businesses, categories and areas. -->
<section class="hero hero--choose">
  <div class="ho-container hero__grid">
   <div class="hero__main">
    <p class="eyebrow" style="color:#fff;opacity:.8"><?php echo $hub_e( 'home_hero_eyebrow' ); ?></p>
    <h1 class="hero__title"><?php echo $hub_e( 'home_hero_title' ); ?><?php if ( '' !== $hub_s['home_hero_title_em'] ) : ?> <em><?php echo str_replace( ' ', '&nbsp;', $hub_e( 'home_hero_title_em' ) ); ?></em><?php endif; ?></h1>
    <p class="hero__lead"><?php echo $hub_e( 'home_hero_lead' ); ?></p>

    <form class="searchbar" action="<?php echo esc_url( hub_url( 'search/' ) ); ?>" method="get" role="search">
      <div class="searchbar__field">
        <svg class="ho-icon"><use href="#i-search"/></svg>
        <label class="ho-sr" for="hero-q">What are you looking for?</label>
        <input id="hero-q" type="search" name="q" placeholder="<?php echo esc_attr( $hub_s['home_search_placeholder'] ); ?>" autocomplete="off">
      </div>
      <div class="searchbar__field">
        <svg class="ho-icon"><use href="#i-pin"/></svg>
        <label class="ho-sr" for="hero-area">Area</label>
        <select id="hero-area" name="area"><option value="">All of Harrow</option><?php foreach ( $hub_home['areas'] as $hub_a ) : ?><option value="<?php echo esc_attr( $hub_a['slug'] ); ?>"><?php echo esc_html( $hub_a['name'] ); ?></option><?php endforeach; ?></select>
      </div>
      <button class="ho-btn ho-btn--primary" type="submit"><?php echo $hub_e( 'home_search_button' ); ?></button>
    </form>

    <div class="popular popular--one">
      <span><?php echo $hub_e( 'home_popular_label' ); ?></span>
<?php foreach ( $hub_s['home_popular'] as $hub_p ) : ?>
      <a href="<?php echo esc_url( $hub_p['link'] ); ?>"><?php echo esc_html( $hub_p['label'] ); ?></a>
<?php endforeach; ?>
    </div>

   </div>

    <div class="hero__side">
      <nav class="choose" aria-label="Browse the Hub">
<?php foreach ( $hub_s['home_tiles'] as $hub_t ) : ?>
        <a class="choose__item" href="<?php echo esc_url( $hub_t['link'] ); ?>"><span class="choose__icon"><svg class="ho-icon"><use href="#i-<?php echo esc_attr( $hub_t['icon'] ); ?>"/></svg></span><span class="choose__text"><strong><?php echo esc_html( $hub_t['title'] ); ?></strong><span><?php echo esc_html( $hub_t['text'] ); ?></span></span><svg class="ho-icon choose__go"><use href="#i-arrow"/></svg></a>
<?php endforeach; ?>
      </nav>
      <!-- Client request (18 Nov 2026): quick routes for organisers and businesses under the four choices -->
      <div class="choose-add">
<?php foreach ( $hub_s['home_add_buttons'] as $hub_b ) : ?>
        <a class="ho-btn ho-btn--on-dark" href="<?php echo esc_url( $hub_b['link'] ); ?>"><svg class="ho-icon"><use href="#i-plus"/></svg><?php echo esc_html( $hub_b['label'] ); ?></a>
<?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php
if ( ! $hub_s['home_ticker_hide'] ) {
	get_template_part( 'parts/businesses/ticker', null, array( 'businesses' => $hub_home['newest'] ) );
}
?>

<?php if ( ! $hub_s['home_weekend_hide'] ) : ?>
<section class="section" aria-labelledby="wknd-title">
  <div class="ho-container">
    <div class="sec-head">
      <div><h2 class="sec-title" id="wknd-title"><?php echo $hub_e( 'home_weekend_title' ); ?></h2><p class="sec-sub"><?php echo esc_html( $hub_home['weekend']['label'] ); ?></p></div>
      <a class="link-arrow" href="<?php echo $hub_u( 'home_weekend_link' ); ?>"><?php echo $hub_e( 'home_weekend_link_label' ); ?> <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="home-now">
      <?php
		foreach ( $hub_home['weekend']['events'] as $hub_ev ) {
			get_template_part( 'parts/events/card', null, array( 'event' => $hub_ev ) );
		}
		?>

      <div class="home-now__offer">
        <?php
		if ( $hub_home['weekend_offer'] ) {
			get_template_part( 'parts/offers/card', null, array( 'offer' => $hub_home['weekend_offer'] ) );
		}
		?>
      </div>
<?php if ( ! $hub_s['home_nl_hide'] ) : ?>
      <div class="home-nl">
        <span class="home-nl__icon"><svg class="ho-icon"><use href="#i-mail"/></svg></span>
        <h3 class="home-nl__title"><?php echo $hub_e( 'home_nl_title' ); ?></h3>
        <p class="home-nl__text"><?php echo $hub_e( 'home_nl_text' ); ?></p>
        <form class="home-nl__form" action="https://harrowonline.org/wp-json/ho/v1/subscribe" method="post" data-nl-form>
          <input type="hidden" name="signup_source" value="hub-home">
          <fieldset class="home-nl__editions">
            <legend class="ho-sr">Choose your newsletters</legend>
<?php foreach ( $hub_s['home_nl_editions'] as $hub_n ) : ?>
            <label><input type="checkbox" name="editions[]" value="<?php echo esc_attr( $hub_n['value'] ); ?>"<?php echo $hub_n['checked'] ? ' checked' : ''; ?>> <?php echo esc_html( $hub_n['label'] ); ?><?php if ( '' !== $hub_n['note'] ) : ?> <small><?php echo esc_html( $hub_n['note'] ); ?></small><?php endif; ?></label>
<?php endforeach; ?>
          </fieldset>
          <label class="ho-sr" for="home-nl-email">Email address</label>
          <input class="input" id="home-nl-email" type="email" name="email" placeholder="<?php echo esc_attr( $hub_s['home_nl_placeholder'] ); ?>" autocomplete="email" required>
          <button class="ho-btn ho-btn--primary ho-btn--block" type="submit"><?php echo $hub_e( 'home_nl_button' ); ?></button>
          <p class="home-nl__msg" role="status" aria-live="polite"></p>
        </form>
      </div>
<?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ( ! $hub_s['home_offers_hide'] && $hub_home['offers'] ) : ?>
<section class="section section--soft" aria-labelledby="offers-title">
  <div class="ho-container">
    <div class="sec-head">
      <div><h2 class="sec-title" id="offers-title"><?php echo $hub_e( 'home_offers_title' ); ?></h2><p class="sec-sub"><?php echo $hub_e( 'home_offers_sub' ); ?></p></div>
      <a class="link-arrow" href="<?php echo $hub_u( 'home_offers_link' ); ?>"><?php echo $hub_e( 'home_offers_link_label' ); ?> <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="offer-tiles">
      <?php
		foreach ( $hub_home['offers'] as $hub_offer ) {
			get_template_part( 'parts/offers/tile', null, array( 'offer' => $hub_offer ) );
		}
		?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ( ! $hub_s['home_stats_hide'] ) : $hub_st = $hub_home['stats']; ?>
<section class="stats-band" aria-label="The Hub in numbers">
  <div class="ho-container">
    <div class="stats-strip"><a class="stat" href="<?php echo esc_url( hub_url( 'offers/' ) ); ?>"><span class="stat__label"><?php echo $hub_e( 'home_stat_offers_label' ); ?></span><strong class="stat__value" data-count="<?php echo (int) $hub_st['offers']; ?>"><?php echo (int) $hub_st['offers']; ?></strong><span class="stat__note"><?php echo $hub_e( 'home_stat_offers_note' ); ?></span></a><a class="stat" href="<?php echo esc_url( hub_url( 'events/' ) ); ?>"><span class="stat__label"><?php echo $hub_e( 'home_stat_events_label' ); ?></span><strong class="stat__value" data-count="<?php echo (int) $hub_st['week_events']; ?>"><?php echo (int) $hub_st['week_events']; ?></strong><span class="stat__note"><?php echo $hub_e( 'home_stat_events_note' ); ?></span></a><a class="stat" href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>"><span class="stat__label"><?php echo $hub_e( 'home_stat_classes_label' ); ?></span><strong class="stat__value" data-count="<?php echo (int) $hub_st['classes']; ?>"><?php echo (int) $hub_st['classes']; ?></strong><span class="stat__note"><?php echo $hub_e( 'home_stat_classes_note' ); ?></span></a><a class="stat" href="<?php echo esc_url( hub_url( 'search/' ) ); ?>"><span class="stat__label"><?php echo $hub_e( 'home_stat_biz_label' ); ?></span><strong class="stat__value" data-count="<?php echo (int) $hub_st['businesses']; ?>"><?php echo esc_html( number_format( $hub_st['businesses'] ) ); ?></strong><span class="stat__note"><?php echo esc_html( str_replace( '{areas}', (string) $hub_st['areas'], $hub_s['home_stat_biz_note'] ) ); ?></span></a></div>
  </div>
</section>
<?php endif; ?>

<?php if ( ! $hub_s['home_featured_hide'] && $hub_home['featured'] ) : ?>
<section class="section" aria-labelledby="feat-title">
  <div class="ho-container">
    <div class="sec-head">
      <div><h2 class="sec-title" id="feat-title"><?php echo $hub_e( 'home_featured_title' ); ?></h2><p class="sec-sub"><?php echo $hub_e( 'home_featured_sub' ); ?></p></div>
      <a class="link-arrow hide-mobile" href="<?php echo $hub_u( 'home_featured_link' ); ?>"><?php echo $hub_e( 'home_featured_link_label' ); ?> <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="grid grid-4">
      <?php
		foreach ( $hub_home['featured'] as $hub_biz ) {
			get_template_part( 'parts/businesses/card', null, array( 'business' => $hub_biz ) );
		}
		?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ( ! $hub_s['home_cats_hide'] && $hub_home['categories'] ) : ?>
<section class="section section--soft" id="categories" aria-labelledby="cats-title">
  <div class="ho-container">
    <div class="sec-head">
      <div><h2 class="sec-title" id="cats-title"><?php echo $hub_e( 'home_cats_title' ); ?></h2><p class="sec-sub"><?php echo $hub_e( 'home_cats_sub' ); ?></p></div>
      <a class="link-arrow" href="<?php echo $hub_u( 'home_cats_link' ); ?>"><?php echo $hub_e( 'home_cats_link_label' ); ?> <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="cat-cards"><?php foreach ( $hub_home['categories'] as $hub_c ) : $hub_ic = esc_attr( '' !== $hub_c['icon'] ? $hub_c['icon'] : 'store' ); ?><a class="cat-card" href="<?php echo esc_url( $hub_c['url'] ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-<?php echo $hub_ic; ?>"/></svg></span><span class="cat-card__body"><span class="cat-card__name"><?php echo esc_html( $hub_c['name'] ); ?></span><span class="cat-card__count"><?php echo esc_html( number_format( $hub_c['business_count'] ) . ( 1 === $hub_c['business_count'] ? ' business' : ' businesses' ) ); ?></span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-<?php echo $hub_ic; ?>"/></svg></span></a><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?php if ( ! $hub_s['home_areas_hide'] || ! $hub_s['home_gem_hide'] ) : ?>
<section class="section" id="areas" aria-labelledby="areas-title">
  <div class="ho-container">
<?php if ( ! $hub_s['home_areas_hide'] ) : ?>
    <div class="sec-head">
      <div><h2 class="sec-title" id="areas-title"><?php echo $hub_e( 'home_areas_title' ); ?></h2><p class="sec-sub"><?php echo $hub_e( 'home_areas_sub' ); ?></p></div>
    </div>
    <div class="area-cards"><?php foreach ( $hub_home['areas'] as $hub_a ) : ?><a class="area-card" href="<?php echo esc_url( $hub_a['url'] ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name"><?php echo esc_html( $hub_a['name'] ); ?></span><span class="area-card__count"><?php echo esc_html( number_format( $hub_a['business_count'] ) . ( 1 === $hub_a['business_count'] ? ' business' : ' businesses' ) ); ?></span><span class="area-card__bar" aria-hidden="true"><i style="width:<?php echo (int) $hub_a['bar']; ?>%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><?php endforeach; ?></div>
<?php endif; ?>
<?php if ( ! $hub_s['home_gem_hide'] ) : ?>
    <div class="suggest<?php echo $hub_s['home_areas_hide'] ? '' : ' mt-6'; ?>">
      <div><p class="eyebrow mb-1"><?php echo $hub_e( 'home_gem_eyebrow' ); ?></p><h2 class="suggest__title"><?php echo $hub_e( 'home_gem_title' ); ?></h2><p class="mb-0"><?php echo $hub_e( 'home_gem_text' ); ?></p></div>
      <a class="ho-btn ho-btn--outline" href="<?php echo $hub_u( 'home_gem_link' ); ?>"><?php echo $hub_e( 'home_gem_button' ); ?></a>
    </div>
<?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ( ! $hub_s['home_biz_hide'] ) : ?>
<!-- For businesses: one panel, text and actions left, the three steps as a timeline right (redesign, Nov 2026) -->
<section class="section" aria-labelledby="biz-title">
  <div class="ho-container">
    <div class="biz-panel">
      <div class="biz-panel__intro">
        <p class="eyebrow"><?php echo $hub_e( 'home_biz_eyebrow' ); ?></p>
        <h2 class="biz-panel__title" id="biz-title"><?php echo $hub_e( 'home_biz_title' ); ?></h2>
        <p class="biz-panel__lead"><?php echo $hub_e( 'home_biz_lead' ); ?></p>
        <div class="biz-panel__actions">
<?php foreach ( $hub_s['home_biz_buttons'] as $hub_i => $hub_b ) : ?>
          <a class="ho-btn <?php echo 0 === $hub_i ? 'ho-btn--primary' : 'ho-btn--outline'; ?>" href="<?php echo esc_url( $hub_b['link'] ); ?>"><?php echo esc_html( $hub_b['label'] ); ?></a>
<?php endforeach; ?>
        </div>
        <p class="biz-panel__note"><svg class="ho-icon ho-icon--sm"><use href="#i-check"/></svg><?php echo $hub_e( 'home_biz_note' ); ?><?php if ( '' !== $hub_s['home_biz_note_link_label'] ) : ?> <a href="<?php echo $hub_u( 'home_biz_note_link' ); ?>"><?php echo $hub_e( 'home_biz_note_link_label' ); ?></a><?php endif; ?></p>
      </div>
      <ol class="biz-steps">
<?php foreach ( $hub_s['home_biz_steps'] as $hub_i => $hub_step ) : ?>
        <li><span class="biz-steps__num" aria-hidden="true"><?php echo (int) $hub_i + 1; ?></span><div><h3><?php echo esc_html( $hub_step['title'] ); ?></h3><p><?php echo esc_html( $hub_step['text'] ); ?></p></div></li>
<?php endforeach; ?>
      </ol>
    </div>
  </div>
</section>
<?php endif; ?>
<?php get_footer();
