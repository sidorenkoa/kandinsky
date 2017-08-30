<?php if( !defined('WPINC') ) die;
/**
 * Custom template tags for this theme.
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package bb
 */


function rdc_has_authors(){
		
	if(defined('TST_HAS_AUTHORS') && TST_HAS_AUTHORS && function_exists('get_term_meta'))
		return true;
	
	return false;
}


/* Custom conditions */
function is_about(){
	global $post;
		
	if(is_page_branch(2))
		return true;
	
	if(is_post_type_archive('org'))
		return true;
	
	if(is_post_type_archive('org'))
		return true;
	
	return false;
}

function is_page_branch($pageID){
	global $post;
	
	if(empty($pageID))
		return false;
		
	if(!is_page() || is_front_page())
		return false;
	
	if(is_page($pageID))
		return true;
	
	if($post->post_parent == 0)
		return false;
	
	$parents = get_post_ancestors($post);
	
	if(is_string($pageID)){
		$test_id = get_page_by_path($pageID)->ID;
	}
	else {
		$test_id = (int)$pageID;
	}
	
	if(in_array($test_id, $parents))
		return true;
	
	return false;
}


function is_tax_branch($slug, $tax) {
	//global $post;
	
	$test = get_term_by('slug', $slug, $tax);
	if(empty($test))
		return false;
	
	if(is_tax($tax)){
		$qobj = get_queried_object();
		if($qobj->term_id == $test->term_id || $qobj->parent == $test->term_id)
			return true;
	}
	
	//if(is_singular() && is_object_in_term($post->ID, $tax, $test->term_id))
	//	return true;
	
	return false;
}


function is_posts() {
	
	if(is_home() || is_category())
		return true;	
	
	if(is_tax('auctor'))
		return true;
	
	if(is_singular('post'))
		return true;
	
	return false;
}


function is_projects() {	
		
	if(is_page('programms'))
		return true;
		
	if(is_singular('programm'))
		return true;
		
	return false;
}

function is_expired_event(){
	
	if(!is_single())
		return false;
	
	$event = new TST_Event(get_queried_object());
	return $event->is_expired();
}



/** Menu filter sceleton **/
//add_filter('wp_nav_menu_objects', 'rdc_custom_menu_items', 2, 2);
function rdc_custom_menu_items($items, $args){			
	
	if(empty($items))
		return;	
	
	//var_dump($args);
	if($args->theme_location =='primary'){
		
		foreach($items as $index => $menu_item){
			if(in_array('current-menu-item', $menu_item->classes))
				$items[$index]->classes[] = 'active';
		}
	}
	
	return $items;
}
 
/** HTML with meta information for the current post-date/time and author **/
function knd_posted_on(WP_Post $cpost) {
	
	$meta = array();
	$sep = '';
	
	if('post' == $cpost->post_type){		
		
		$meta[] = "<span class='date'>".get_the_date('d.m.Y', $cpost)."</span>";
		
		$cat = get_the_term_list($cpost->ID, 'category', '<span class="category">', ', ', '</span>');
		$meta[] = $cat;
		$meta = array_filter($meta);
		
		$sep = '<span class="sep"></span>';
	}
	elseif('event' == $cpost->post_type ) {
		
		$event = new TST_Event($cpost);
		return $event->posted_on_card();		
	}
	elseif('project' == $cpost->post_type) {
		
		$p = get_page_by_path('activity');
		if($p) {
			$meta[] = "<span class='category'><a href='".get_permalink($p)."'>".get_the_title($p)."</a></span>";
		}
	}
	elseif('person' == $cpost->post_type) {
		
		$cat = get_the_term_list($cpost->ID, 'person_cat', '<span class="category">', ', ', '</span>');
		if(!empty($cat)) {
			$meta[] = $cat;
		}
	}
	elseif('page' == $cpost->post_type && is_search()) {
		
		$meta[] = "<span class='category'>".__('Page', 'knd')."</span>";
		
	}
		
	return implode($sep, $meta);		
}


/** Logo **/
function rdc_site_logo($size = 'regular') {

	switch($size) {
		case 'regular':
			$file = 'pic-logo';
			break;
		case 'small':
			$file = 'pic-logo-small';
			break;	
		default:
			$file = 'icon-logo';
			break;	
	}
	
	$file = esc_attr($file);	
?>
<svg class="logo <?php echo $file;?>">
	<use xlink:href="#<?php echo $file;?>" />
</svg>
<?php
}

function rdc_svg_icon($id, $echo = true) {
	
	ob_start();
?>
<svg class="svg-icon <?php echo $id;?>">
	<use xlink:href="#<?php echo $id;?>" />
</svg>
<?php
	$out = ob_get_contents();
	ob_end_clean();
	if($echo)
		echo $out;
	return $out;
}


/** Separator **/
function knd_get_sep($mark = '//') {
	
	return "<span class='sep'>".$mark."</span>";
}

/** == Titles == **/
/** CPT archive title **/
function rdc_get_post_type_archive_title($post_type) {
	
	$pt_obj = get_post_type_object( $post_type );	
	$name = $pt_obj->labels->menu_name;
	
	
	return $name;
}

function rdc_section_title() {
	
	$title = '';
	$css = '';
	
	if(is_category()){
		
		$p = get_post(get_option('page_for_posts'));
		$title = get_the_title($p);
		$title .= knd_get_sep('&mdash;');
		$title .= single_term_title('', false);
		$css = 'archive';
	}
	elseif(is_tag() || is_tax()){
		$title = single_term_title('', false);
		$css = 'archive';
	}
	elseif(is_home()){
		$p = get_post(get_option('page_for_posts'));
		$title = get_the_title($p);
		$css = 'archive';
	}
	elseif(is_post_type_archive('leyka_donation')){		
		$title = __('Donations history', 'knd');
		$css = 'archive';
	}
	elseif(is_search()){
		$title = __('Search results', 'knd');
		$css = 'archive search';
	}
	elseif(is_404()){
		$title = __('404: Page not found', 'knd');
		$css = 'archive e404';
	}
	
	echo "<h1 class='section-title {$css}'>{$title}</h1>";	
}


/** == NAVs == **/
function rdc_paging_nav(WP_Query $query = null) {

	if( !$query ) {

		global $wp_query;
		$query = $wp_query;
	}

	if($query->max_num_pages < 2) { // Don't print empty markup if there's only one page
		return;
	}

	$p = rdc_paginate_links($query, false);
	if($p) {
?>
	<nav class="paging-navigation" role="navigation"><div class="container"><?php echo $p; ?></div></nav>
<?php
	}
}


function rdc_paginate_links(WP_Query $query = null, $echo = true) {

	if( !$query ) {

		global $wp_query;
		$query = $wp_query;
	}
	
	$current = ($query->query_vars['paged'] > 1) ? $query->query_vars['paged'] : 1; 

	$parts = parse_url(get_pagenum_link(1));

	$pagination = array(
        'base' => trailingslashit(esc_url($parts['host'].$parts['path'])).'%_%',
        'format' => 'page/%#%/',
        'total' => $query->max_num_pages,
        'current' => $current,
		'prev_next' => true,
        'prev_text' => '&lt;',
        'next_text' => '&gt;',
        'end_size' => 4,
        'mid_size' => 4,
        'show_all' => false,
        'type' => 'plain', //list
		'add_args' => array()
    );

    if( !empty($query->query_vars['s']) ) {
        $pagination['add_args'] = array('s' => str_replace(' ', '+', get_search_query()));
	}

	foreach(array('s') as $param) { // Params to remove

		if($param == 's') {
			continue;
		}

		if(isset($_GET[$param]) && !empty($_GET[$param])) {
			$pagination['add_args'] = array_merge($pagination['add_args'], array($param => esc_attr(trim($_GET[$param]))));
		}
	}
		
		    
    if($echo) {

		echo paginate_links($pagination);
		return '';
	} else {
		return paginate_links($pagination);
	}
}


/** next/previous post when applicabl */
function rdc_post_nav() {

	$previous = is_attachment() ? get_post(get_post()->post_parent) : get_adjacent_post(false, '', true);

	if( !get_adjacent_post(false, '', false) && !$previous) { // Don't print empty markup if there's nowhere to navigate
		return;
	}?>

	<nav class="navigation post-navigation" role="navigation">
		<h1 class="screen-reader-text"><?php _e('Post navigation', 'kds'); ?></h1>
		<div class="nav-links">
			<?php previous_post_link('<div class="nav-previous">%link</div>', '<span class="meta-nav">&larr;</span>');
			next_post_link('<div class="nav-next">%link</div>', '<span class="meta-nav">&rarr;</span>');?>
		</div>
	</nav>
	<?php
}


/** Breadcrumbs  **/
function rdc_breadcrumbs(WP_Post $cpost){
			
	$links = array();
	if(is_singular('post')) {
		$links[] = "<a href='".home_url()."' class='crumb-link'>".__('Homepage', 'kds')."</a>";
				
		$p = get_post(get_option('page_for_posts'));
		if($p){
			$links[] = "<a href='".get_permalink($p)."' class='crumb-link'>".get_the_title($p)."</a>";
		}
				
	}
	elseif(is_singular('programm')) {
		
		$links[] = "<a href='".home_url()."' class='crumb-link'>".__('Homepage', 'kds')."</a>";
				
		$p = get_page_by_path('programms');
		if($p){
			$links[] = "<a href='".get_permalink($p)."' class='crumb-link'>".get_the_title($p)."</a>";
		}

	}
	
	
	$sep = knd_get_sep('&gt;');
	
	return "<div class='crumbs'>".implode($sep, $links)."</div>";	
}


/** post format **/
function rdc_get_post_format($cpost){
	
	$format = get_post_meta($cpost->ID, 'post_format', true);
	if(empty($format))
		$format = 'standard';
		
	return $format;
}



/** More section **/
function knd_more_section($posts, $title = '', $type = 'news', $css= ''){
	
	if(empty($posts))
		return;
	
	$all_link = '';
	
	if($type == 'projects'){
		$all_link = "<a href='".home_url('activity')."'>".__('More projects', 'knd')."&nbsp;&rarr;</a>";
		$title = (empty($title)) ? __('Our projects', 'knd') : $title;
	}
	elseif($type == 'people') {
		$cat = get_term_by('slug', 'volunteers', 'person_cat');
		$all_link = "<a href='".get_term_link($cat)."'>".__('More volunteers', 'knd')."&nbsp;&rarr;</a>";
		$title = (empty($title)) ? __('Our volunteers', 'knd') : $title;
	}
	elseif($type == 'team') {
	    $cat = get_term_by('slug', 'team', 'person_cat');
	    $all_link = "<a href='".get_term_link($cat)."'>".__('More team members', 'knd')."&nbsp;&rarr;</a>";
	    $title = (empty($title)) ? __('Our team', 'knd') : $title;
	}
	elseif($type == 'events') {
		$p = get_page_by_path('events');
		if($p) {
			$all_link = "<a href='".get_permalink($p)."'>".__('More events', 'knd')."&nbsp;&rarr;</a>";
			$title = (empty($title)) ? get_the_title($p) : $title;
		}
	}
	else {
		$all_link = "<a href='".home_url('news')."'>".__('More news', 'knd')."&nbsp;&rarr;</a>";
		$title = (empty($title)) ? __('Latest news', 'knd') : $title;
	}

	$css .= ' related-card-holder';
?>
<section class="<?php echo esc_attr($css);?>"><div class="container">
<h3 class="related-title"><?php echo $title; ?></h3>

<?php if(is_singular('person')) { ?>
<div class="cards-loop related-people-loop flex-row">
	<?php
		foreach($posts as $p){
			knd_person_card($p, true);
		}
	?>
</div>
<?php } else { ?>
<div class="related-cards-loop flex-row">
	<?php
		foreach($posts as $p){			
		    knd_related_post_card($p);
		}		
	?>
</div>
<?php } ?>

<div class="related-all-link"><?php echo $all_link;?></div>
</div></section>
<?php
}



/** Related project on single page **/
function rdc_related_project(WP_Post $cpost){
	
	$pl = get_permalink($cpost);
	$ex = apply_filters('rdc_the_title', rdc_get_post_excerpt($cpost, 25, true));
?>
<div class="related-widget widget">
	<h3 class="widget-title"><?php _e('Related project', 'kds');?></h3>
	<a href="<?php echo $pl;?>" class="entry-link">
		<div class="rw-preview">
			<?php echo knd_post_thumbnail($cpost->ID, 'post-thumbnail');?>
		</div>
		<div class="rw-content">
			<h4 class="entry-title"><?php echo get_the_title($cpost);?></h4>
			<div class="entry-summary"><?php echo $ex;?></div>
		</div>
	</a>
	<div class="help-cta">
		<?php echo rdc_get_help_now_cta();?>
	</div>
</div>
<?php	
}

function rdc_get_help_now_cta($cpost = null, $label = ''){
	
	$label = (empty($label)) ? __('Help now', 'kds') : $label;
	$cta = '';
	
	if(!$cpost){
		
		$help_id = get_theme_mod('help_campaign_id');
		if(!$help_id)
			return '';
		
		$cta = "<a href='".get_permalink($help_id)."' class='help-button'>{$label}</a>";
	}
	else {
		$url = get_post_meta($cpost->ID, 'cta_link', true);
		$txt = get_post_meta($cpost->ID, 'cta_text', true);
		
		if(empty($url))
			return '';
		
		if(empty($txt))
			$txt = $label;
		
		$css = (false !== strpos($url, '#')) ? 'help-button local-scroll' : 'help-button'; 
		$cta = "<a href='{$url}' class='{$css}'>{$txt}</a>";
	}
	
	return $cta;
}


/** == People fuctions == **/
function knd_people_gallery($category_ids = '', $person_ids = ''){
	
	$args = array(
		'post_type'=> 'person',
		'posts_per_page' => -1
	);
	
	if($category_ids) {
		$args['tax_query'] = array(
			array(
				'taxonomy'=> 'person_cat',
				'field'   => 'id',
				'terms'   => $category_ids
			)
		);
	}
    if($person_ids) {
        $args['post__in'] = explode(',', $person_ids);
    }

	$query = new WP_Query($args);
	if( !$query->have_posts() ) {
		return '';
    }?>

	<div class="people-gallery eqh-container frame cards-loop">
	<?php foreach($query->posts as $person) {?>
		<div class="bit md-3 eqh-el"><?php knd_person_card($person);?></div>
	<?php }?>
	</div>
<?php
}

/** == Orgs functions == **/
function knd_orgs_gallery($category_ids = '', $org_ids = '') {
	
$args = array(
		'post_type'=> 'org',
		'posts_per_page' => -1
	);

    if($category_ids) {
        $args['tax_query'] = array(
            array(
                'taxonomy'=> 'org_cat',
                'field'   => 'id',
                'terms'   => $category_ids
            )
        );
    }
    if($org_ids) {
        $args['post__in'] = explode(',', $org_ids);
    }

	$query = new WP_Query($args);
	if( !$query->have_posts() ) {
		return '';
    }?>

	<div class="orgs-gallery  frame">
	<?php foreach($query->posts as $org) {?>
		<div class="bit mf-6 sm-4 md-3 "><?php knd_org_card($org);?></div>
	<?php }?>
	</div>
<?php
}


/** == Events functions == **/

/** always populate end-date **/
add_action('wp_insert_post', 'rdc_save_post_event_actions', 50, 2);
function rdc_save_post_event_actions($post_ID, $post){
	
	//populate end date
	if($post->post_type == 'event'){
		$event = new TST_Event($post_ID);		
		$event->populate_end_date();
		
	}	
}

/* remove forms from expired events */
function rdc_remove_unused_form($the_content){
	
	$msg = "<div class='tst-notice'>Регистрация закрыта</div>";
	$the_content = preg_replace('/\[formidable(.+)\]/', $msg, $the_content);
	
	return $the_content;
}



/** Single template helpers **/
function rdc_related_reports(TST_Event $event, $css=''){	

	$related = $event->get_related_post_id();
	if(!empty($related)) {
?>
	<div class="expired-notice <?php echo esc_attr($css);?>">
		<h6>Читать отчет</h6>
	<?php
		foreach($related as $r){
			$report = get_post($r);
	?>
		<p><a href="<?php echo get_permalink($r);?>"><?php echo get_the_title($r);?></a></p>
	<?php }	?>
	</div>
<?php }

}

/** Add to calendar links - details at http://addtocalendar.com/ **/
function rdc_add_to_calendar_link(TST_Event $event, $echo = true, $container_class = 'tst-add-calendar', $txt = "", $icon = false) {	
	
	if($event->is_expired())
		return '';
	
	$default_label = "Добавить в календарь";
	
	$start_date  = $event->date_start;
	$start_titme = $event->time_start; 
	$end_date    = $event->date_end;
	$end_time    = $event->time_end;
	
	if(empty($start_date))
		return '';
	
	if(empty($start_titme))
		$start_titme = '12.00 PM';
	
	$start = date('d.m.Y', $start_date).' '.$start_titme;	
	$start_mark = date_i18n('Y-m-d H:i:00', strtotime($start));
		
	
	if(empty($end_date) && empty($end_time)){ //no data about ends
		$end_mark = date_i18n('Y-m-d H:i:00', strtotime('+2 hours '.$start));		
	}
	elseif(empty($end_date) && !empty($end_time)) {
		$end = date('d.m.Y', $start_date).' '.$end_time;	
		$end_mark = date_i18n('Y-m-d H:i:00', strtotime($end));
	}
	else {
		$end = date('d.m.Y', $end_date).' '.$end_time;	
		$end_mark = date_i18n('Y-m-d H:i:00', strtotime($end));
	}
	
	if(empty($txt))
		$txt = $default_label;
		
	if($icon)
		$icon = rdc_svg_icon('icon-add-cal', false);
	
	$location = $event->get_full_address_mark();
	$e = (!empty($event->post_excerpt)) ? wp_trim_words($event->post_excerpt, 20) : wp_trim_words(strip_shortcodes($event->post_content), 20);
	$id = 'tst-'.uniqid();
			
	wp_enqueue_script(
		'atc',
		get_template_directory_uri().'/assets/js/atc.min.js',
		array(),
		null,
		true
	);
?>
	<span id="<?php echo esc_attr($id);?>"  class="<?php echo esc_attr($container_class);?>">
		
		<?php if($icon) { echo $icon; } ?>
		
		<span class="addtocalendar">
			<a class="atcb-link"><?php echo $txt;?></a>
			<var class="atc_event">
				<var class="atc_date_start"><?php echo $start_mark;?></var>
				<var class="atc_date_end"><?php echo $end_mark;?></var>
				<var class="atc_timezone">Europe/Moscow</var>
				<var class="atc_title"><?php echo esc_attr($event->post_title);?></var>
				<var class="atc_description"><?php echo apply_filters('rdc_the_title', $e);?></var>
				<var class="atc_location"><?php echo esc_attr($location);?></var>          
			</var>		
		</span>
		<?php if($txt != $default_label) { ?>
			<span class="tst-tooltip" for="<?php echo esc_attr($id);?>"><?php echo $default_label;?></span>
		<?php } ?>
	</span>
	
<?php	
}

function knd_get_site_icon_img_url() {

    $logo_id = get_option('site_icon');
    if($logo_id) {
        return wp_get_attachment_image_url($logo_id, 'full', false);
    } else {

        $site_scenario = get_theme_mod('knd_site_scenario');
        return $site_scenario ? get_template_directory_uri()."/vendor/envato_setup/images/$site_scenario/favicon.png" : '';

    }

}

function knd_get_logo_img_id() {

    $logo_id = get_theme_mod('knd_custom_logo');

    return $logo_id ? (int)$logo_id : false;

}

function knd_get_logo_img_url() {

    $logo_id = knd_get_logo_img_id();
    if($logo_id) {
        return wp_get_attachment_image_url($logo_id, 'full', false);
    } else {

        $site_scenario = get_theme_mod('knd_site_scenario');
        return $site_scenario ? get_template_directory_uri()."/vendor/envato_setup/images/$site_scenario/logo.svg" : '';

    }

}

function knd_get_logo_img() {

    $logo_id = knd_get_logo_img_id();
    return $logo_id ?
        wp_get_attachment_image($logo_id, 'full', false, array('alt' => get_bloginfo('name'))) :
        '<img class="site-logo-img" src="'.get_template_directory_uri().'/vendor/envato_setup/images/'.get_theme_mod('knd_site_scenario').'/logo.svg" width="315" height="66" alt="'.get_bloginfo('name').'">';

}


function knd_logo_markup() {

    /** @todo logo sizes may depends on test content */
    $mod = get_theme_mod('knd_custom_logo_mod', 'image_only');
    if($mod == 'nothing') {
        return;
    }?>

<a href="<?php echo esc_url(home_url('/'));?>" rel="home" class="site-logo">
<?php if($mod == 'image_only') {?>
    <div class="logo-image-only"><?php echo knd_get_logo_img();?></div>
<?php } elseif($mod == 'text_only') {?>
    <div class="logo-text-only">
        <h1 class="logo-name"><?php bloginfo('name');?></h1>
        <h2 class="logo-name"><?php bloginfo('description');?></h2>
    </div>
<?php } else {?>
    <div class="logo-complex">
        <div class="logo"><?php echo knd_get_logo_img();?></div>
        <div class="text">
            <h1 class="logo-name"><?php bloginfo('name');?></h1>
            <h2 class="logo-name"><?php bloginfo('description');?></h2>
        </div>
    </div>
<?php }?>
</a>
<?php
}

function knd_hero_image_markup() {

    $hero = get_theme_mod('knd_hero_image');
    $hero_img = '';

    if($hero) {
        $hero_img = wp_get_attachment_image_src( (int)$hero, 'full' );
        if(!empty($hero_img)) {
            $hero_img = $hero_img[0];
        }
    }
    
    if($hero_img) {
        $knd_hero_image_support_title = get_theme_mod('knd_hero_image_support_title');
        $knd_hero_image_support_url = get_theme_mod('knd_hero_image_support_url');
        $knd_hero_image_support_text = get_theme_mod( 'knd_hero_image_support_text');
        $knd_hero_image_support_button_caption = get_theme_mod( 'knd_hero_image_support_button_caption');
    ?>
<section class="intro-head-image text-over-image">
<div class="tpl-pictured-bg" style="background-image: url(<?php echo $hero_img;?>)"></div>
</section>

<section class="container intro-head-content text-over-image has-button">

<div class="ihc-content">
<a href="<?php echo $knd_hero_image_support_url ?>">
<?php if($knd_hero_image_support_title):?>
<h1 class="ihc-title">
<span><?php echo $knd_hero_image_support_title ?></span>
</h1>
<?php endif; ?>

<?php if($knd_hero_image_support_text):?>
<div class="ihc-desc">
<p>
<?php echo $knd_hero_image_support_text ?>
</p>
</div>
<?php endif; ?>

<?php if($knd_hero_image_support_button_caption):?>
<div class="cta"><?php echo $knd_hero_image_support_button_caption ?></div>
<?php endif;?>
</a>
</div>

</section>

<?php
    }
}

