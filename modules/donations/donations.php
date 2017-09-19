<?php

if(!class_exists('Leyka_Payment_Method'))
    return;

require get_template_directory().'/modules/donations/widgets.php';

/** Custom donation functions */

add_filter('leyka_icons_text_text_box', 'knd_text_pm_icon');
function knd_text_pm_icon($icons){
	//size 155x80 px
	
	$icons = array(get_template_directory_uri().'/assets/images/text-box.png');
		
	return $icons;
}

//no icon for text gateway
add_filter('leyka_icons_text_text_box', 'knd_empty_icons');	
function knd_empty_icons($icons){
	return array();
}

function knd_activate_leyka() {
    $imp = new KND_Import_Remote_Content(get_theme_mod('knd_site_scenario'));
    $imp->import_downloaded_content();
    
    $pdb = KND_Plot_Data_Builder::produce_builder($imp);
    $pdb->build_leyka_capmaigns();
}
register_activation_hook( 'leyka/leyka.php', 'knd_activate_leyka' );

/** Form template **/
//custom amount field
function knd_amount_field($form){
	
	if(!defined('LEYKA_VERSION'))
		return;
	
	$supported_curr = leyka_get_active_currencies();
	$current_curr = $form->get_current_currency();

	if(empty($supported_curr[$current_curr])) {
		return; // Current currency isn't supported
	}
?>

<div class="leyka-field amount-selector amount mixed">
	
<div class="currency-selector-row" >
	<div class="currency-variants">
<?php
	foreach($supported_curr as $currency => $data) {

	$variants = explode(',', $data['amount_settings']['fixed']);?>
		<div class="<?php echo $currency;?> amount-variants-container" <?php echo $currency == $current_curr ? '' : 'style="display:none;"';?> >
			<div class="amount-variants-row">
				<?php foreach($variants as $i => $amount) { ?>
					<label class="figure rdc-radio" title="<?php _e('Please, specify your donation amount', 'leyka');?>">
						<input type="radio" value="<?php echo (int)$amount;?>" name="leyka_donation_amount" class="rdc-radio__button" <?php checked($i, 0);?> <?php echo $currency == $current_curr ? '' : 'disabled="disabled"';?>>
						<span class="rdc-radio__label"><?php echo (int)$amount;?></span>
					</label>
				<?php } ?>
				
				<label class="figure-flex">
					<span class="figure-sep"><?php _e('or', 'knd');?></span>
					<input type="text" title="<?php echo __('Specify the amount of your donation', 'leyka');?>" name="leyka_donation_amount" class="donate_amount_flex" value="<?php echo esc_attr($supported_curr[$current_curr]['amount_settings']['flexible']);?>" <?php echo $currency == $current_curr ? '' : 'disabled="disabled"';?> maxlength="6" size="6">
				</label>
			</div>
		</div>	
	<?php } ?>
	</div>
	<div class="currency"><span class="currency-frame"><?php echo $form->get_currency_field();?></span></div>
</div>

<div class="leyka_donation_amount-error field-error"></div>
	
</div>
<?php
}

function knd_donation_card(WP_Post $campaign){


if($campaign->post_type != Leyka_Campaign_Management::$post_type) { // Wrong campaign data
    return;
}

$current_post = get_post();

$thumbnail_size = apply_filters('leyka_campaign_card_thumbnail_size', 'post-thumbnail', $campaign);
$css_class = apply_filters('leyka_campaign_card_class', 'leyka-campaign-card', $campaign);
if(has_post_thumbnail($campaign->ID)) {
    $css_class .= ' has-thumb';
}

$url = trailingslashit(get_permalink($campaign->ID)).'#leyka-payment-form';

$leyka_campaign = new Leyka_Campaign($campaign);

$target = (int)$leyka_campaign->target;
$curr_label = leyka_get_currency_label('rur');
$collected = $leyka_campaign->get_collected_amount();

if($target <= 0) {
    return;
}

$percentage = round(($collected/$target)*100);
if($percentage > 100) {
    $percentage = 100;
}

$target_f = number_format($target, 0, '.', ' ');
$collected_f = number_format($collected, 0, '.', ' ');

$campaign_age = get_post_meta($campaign->ID, 'campaign_age', true);
$is_finished = get_post_meta($campaign->ID, 'is_finished', true);

?>
<article class="flex-md-6 tpl-post card">
    <div class="<?php echo esc_attr($css_class);?>">
        <?php if(has_post_thumbnail($campaign->ID)) {?>
            <div class="lk-thumbnail">
                <a href="<?php echo get_permalink($campaign);?>">
                    <?php echo get_the_post_thumbnail(
                        $campaign->ID,
                        $thumbnail_size,
                        array('alt' => esc_attr(sprintf(__('Thumbnail for - %s', 'leyka'), $campaign->post_title)),)
                    );?>
                </a>
            </div>
        <?php }?>

        <div class="lk-info">
        
            <div class="help-purpose">Помощь семье</div>
        
            <h4 class="lk-title"><a href="<?php echo get_permalink($campaign);?>">
                <?php echo get_the_title($campaign);?><?php if($campaign_age): echo ", {$campaign_age}"; endif;?>
            </a></h4>

            <?php
                // Default excerpt filters:
                add_filter('leyka_get_the_excerpt', 'wptexturize');
                add_filter('leyka_get_the_excerpt', 'convert_smilies');
                add_filter('leyka_get_the_excerpt', 'convert_chars');
                add_filter('leyka_get_the_excerpt', 'wp_trim_excerpt');?>
                <p>
                    <?php if(has_excerpt($campaign->ID)) {
                        $text = $campaign->post_excerpt;
                    } else {

                        $text = $campaign->post_content ? $campaign->post_content : ' '; // So wp_trim_excerpt work correctly
                        $text = leyka_strip_string_by_words($text, 200, true).(mb_strlen($text) > 200 ? '...' : '');

                    }
                    echo apply_filters('leyka_get_the_excerpt', $text, $campaign);?>
                </p>
        </div>

        <div class="leyka-scale-compact">
            <?php if(!$is_finished):?>
            <div class="leyka-scale-scale">
                <div class="target">
                    <div style="width:<?php echo $percentage;?>%" class="collected">&nbsp;</div>
                </div>
            </div>
            <?php endif?>
            <div class="flex-row leyka-scale-label">
            
                <div class="flex-md-5">
                    <div class="caption"><?php _e("Collected", 'knd')?></div>
                    <div class="sum"><?php echo $collected_f?> <?php echo $curr_label?></div>
                </div>
                
                <?php if(!$is_finished):?>
                <div class="flex-md-7 knd-campaign-needed">
                    <div class="caption"><?php _e("Needed", 'knd')?></div>
                    <div class="sum"><?php echo $target_f?> <?php echo $curr_label?></div>
                </div>
                <?php endif?>
                
            </div>
        </div>
        <?php if(!$is_finished):?>
        <div class="leyka-scale-button-alone">
            <a href="<?php echo $url;?>"><?php echo get_theme_mod('knd_hero_image_support_button_caption'); ?></a>
        </div>
        <?php endif?>

    </div>
</article>
<?php
}

// customize default leyka colors
remove_action( 'wp_head', 'leyka_inline_scripts');

add_action('wp_head', 'knd_leyka_inline_scripts');
function knd_leyka_inline_scripts(){
    
    $main_color = knd_get_main_color();
    $dark_color = knd_color_luminance($main_color, -0.1);
    $light_color = knd_color_luminance($main_color, 0.2);
    
    $colors = array($main_color, $dark_color, $light_color); // Leyka green

// detect if we have JS at all... ?>

    <script>
        document.documentElement.classList.add("leyka-js");
    </script>
    <style>
        :root {
            --color-main: 		<?php echo $colors[0];?>;
            --color-main-dark: 	<?php echo $colors[1];?>;
            --color-main-light: <?php echo $colors[2];?>;
        }
    </style>
    <?php
}


add_action('parse_query', 'knd_leyka_request_corrected');
function knd_leyka_request_corrected(WP_Query $query) {

    if(is_admin()) {
        return;
    }
    
    if(is_post_type_archive( Leyka_Campaign_Management::$post_type )) {
        $meta_query = $query->get( 'meta_query' );
        if(!$meta_query) {
            $meta_query = array();
        }
        
        $meta_query[] = array(
            'key'     => 'campaign_target',
            'value'   => '1',
            'compare' => '>'
        );
        
        if(isset($query->query_vars['completed']) && $query->query_vars['completed'] == 'true') {
            $meta_query[] = array(
                'key' => 'is_finished',
                'value' => 1,
            );
        }
        elseif(isset($query->query_vars['active']) && $query->query_vars['active'] == 'true') {
            $meta_query[] = array(
                'key' => 'is_finished',
                'value' => 0,
            );
        }
        
        $query->set( 'meta_query', $meta_query );
    }

}

add_action('init', 'knd_leyka_rewrite_rules');
function knd_leyka_rewrite_rules(){
    
    add_rewrite_rule(
        '^campaign/completed/page/(\d+)/?$',
        'index.php?post_type=' . Leyka_Campaign_Management::$post_type . '&completed=true&paged=$matches[1]',
        'top'
    );
    
    add_rewrite_rule(
        '^campaign/completed/?$',
        'index.php?post_type=' . Leyka_Campaign_Management::$post_type . '&completed=true',
        'top'
    );
    
    
    add_rewrite_rule(
        '^campaign/active/page/(\d+)/?$',
        'index.php?post_type=' . Leyka_Campaign_Management::$post_type . '&active=true&paged=$matches[1]',
        'top'
    );
    
    add_rewrite_rule(
        '^campaign/active/?$',
        'index.php?post_type=' . Leyka_Campaign_Management::$post_type . '&active=true',
        'top'
    );
    
    flush_rewrite_rules();
}

function knd_leyka_add_query_vars_filter( $vars ){
    $vars[] = "completed";
    $vars[] = "active";
    return $vars;
}
add_filter( 'query_vars', 'knd_leyka_add_query_vars_filter' );