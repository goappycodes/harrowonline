<?php
/** WordPress Pages: the template is chosen by the page's path (inc/page-map.php), e.g. claim/submitted. */
defined( 'ABSPATH' ) || exit;
$hub_map = require get_template_directory() . '/inc/page-map.php';
$hub_uri = get_page_uri( get_queried_object_id() );
if ( isset( $hub_map[ $hub_uri ] ) ) {
	require get_template_directory() . '/page-templates/' . $hub_map[ $hub_uri ]['tpl'] . '.php';
	return;
}
hub_set_page( array( 'title' => get_the_title() ) );
get_header();
?>
<header class="page-head ho-container"><h1 class="page-head__title"><?php the_title(); ?></h1></header>
<div class="ho-container prose"><?php while ( have_posts() ) { the_post(); the_content(); } ?></div>
<?php
get_footer();
