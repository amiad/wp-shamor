<?php
   /*
   Plugin Name: WP Shamor
   Plugin URI: http://quicksolutions.co.il/
   description: A plugin to redirect user out of your site on Shabbat and Holiday.
   Version: 1.1
   Author: Rivka Chollack
   Author URI: http://quicksolutions.co.il/
   */

defined( 'ABSPATH' ) or die( 'No access' );

require_once 'vendor/autoload.php';
use GeoIp2\Database\Reader;

define('CANDLE_BEFORE_SUNSET' , 18);
define('HAVDALAH_AFTER_SUNSET' , 50);

function get_location_data_from_ip(){
	$reader = new Reader(__DIR__ . '/db/GeoLite2-City.mmdb');
	$record = $reader->city(get_client_ip());

	return apply_filters('location_data_from_ip', $record->location);
};

function get_shabbat_times(){
	$location = get_location_data_from_ip();

	$sunset = date_sun_info(time(), $location->latitude, $location->longitude)['sunset'];
	$candle_lighting = $sunset - CANDLE_BEFORE_SUNSET * 60;
	$havdalah = $sunset + HAVDALAH_AFTER_SUNSET * 60;

	$start_time = get_option('start_time') ?: '0';
	$end_time = get_option('end_time') ?: '0';

	$candle_lighting = strtotime("-$start_time", $candle_lighting);
	$havdalah = strtotime("+$end_time", $havdalah);

	$times = [
		'candle_lighting' => $candle_lighting,
		'havdalah' => $havdalah,
		'timezone' => $location->timeZone,
	];

	return apply_filters('shabbat_times', $times);
}

define('YAMIM_TOVIM', [
	'טו ניסן',
	'כא ניסן',
	'ו סיון',
	'א תשרי',
	'ב תשרי',
	'י תשרי',
	'טו תשרי',
	'כב תשרי',
]);
define('ISRUCHAG', [
	'טז ניסן',
	'כב ניסן',
	'ז סיון',
	'טז תשרי',
	'כג תשרי',
]);

function is_yom_tov(){
	$hebdate = get_hebdate();
	return apply_filters('is_yom_tov', in_array($hebdate, YAMIM_TOVIM));
}
function is_erev_yom_tov(){
	$hebdate = get_hebdate('tomorrow');
	return apply_filters('is_erev_yom_tov', in_array($hebdate, YAMIM_TOVIM));
}
function get_hebdate($str = 'now'){
	$juldate = gregoriantojd(...explode('/', date('m/d/Y', strtotime($str))));
	$hebdate = jdtojewish($juldate, true);
	$hebdate = iconv("windows-1255", "UTF-8", $hebdate);

	$hebdate = explode(' ' , $hebdate);
	$hebdate = "{$hebdate[0]} {$hebdate[1]}";

	return $hebdate;
}

function plugin_action_links($links) {
	$settings_link = '<a href="' . admin_url('admin.php?page=wp-shamor%2Fwp-shamor.php') . '" title="' . __('הגדרות', 'wp-shamor') . '">' . __('הגדרות', 'wp-shamor') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}

function language_redirect($template) {
    global $q_config;
    $new_template = locate_template( array( 'page-'.$q_config['lang'].'.php' ) );
    if ( '' != $new_template ) {
        return $new_template ;
    }
    return $template;
}

add_filter( 'template_include', 'move_out_of_site');
function move_out_of_site($template = ''){

	if(isset( $_GET['wp_shamor'] ) && $_GET['wp_shamor'] == 'preview'){
		return trailingslashit(plugin_dir_path(__FILE__)) . 'block_template.php';
	}

	$times = get_shabbat_times();
	
	if ((date('l') == 'Friday' && time() > $times['candle_lighting']) || (date('l') == 'Saturday' && time() < $times['havdalah'])){

	    if(empty($template)) {
	        echo get_home_url() . '/wp-content/plugins/wp-shamor/block_page.php'; 
	        wp_die();
		}
		else {
			$my_template = trailingslashit(plugin_dir_path(__FILE__)) . 'block_template.php';
	        return $my_template;
		}
	}
	if(empty($template)) {
		exit;
	}
	return $template;
}

function get_client_ip()
{
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }

    return $ipaddress;
}

add_action('admin_menu', 'shamor_plugin_menu');
function shamor_plugin_menu() {
	add_menu_page('WP Shamor', 'WP Shamor', 'administrator', __FILE__, 'shamor_plugin_settings_page' );
	add_action( 'admin_init', 'register_shamor_plugin_settings' );
	add_filter(
      'plugin_action_links_' . plugin_basename(__FILE__),
      'plugin_action_links'
    );
}

function register_shamor_plugin_settings() {
	register_setting( 'shamor-plugin-settings-group', 'start_time' );
	register_setting( 'shamor-plugin-settings-group', 'end_time' );
	register_setting( 'shamor-plugin-settings-group', 'display_text' );
	register_setting( 'shamor-plugin-settings-group', 'display_template' );
}

function shamor_plugin_settings_page() {
?>
	<div class="wrap">
	<h1>הגדרות WP Shamor</h1>
		<div>
			<a href="<?php echo get_home_url(); ?>/wp-content/plugins/wp-shamor/block_page.php" target="_blank">לחצו כאן כדי לראות את דף החסימה שיוצג בשבתות וחגים</a>
		</div>
	<form method="post" action="options.php">
		<?php settings_fields( 'shamor-plugin-settings-group' ); ?>
		<?php do_settings_sections( 'shamor-plugin-settings-group' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">הגדירו כמה דקות לפני שבת האתר יחסם:</th>
				<th scope="row">שימו לב: זמן שבת המוגדר הוא מ 18 דקות לפני שקיעה</th>			
				<td><span>הזינו זמן בפורמט של 00:00 - שעות ודקות</span><br><input type="time" name="start_time" value="<?php echo esc_attr( get_option('start_time') ); ?>" /></td>
			</tr>			
			<tr valign="bottom">
				<th scope="row">הגדירו כמה דקות אחרי שבת האתר יפתח:</th>		
				<th scope="row">שימו לב: זמן שבת המוגדר הוא עד 50 דקות אחרי שקיעה</th>				
				<td><span>הזינו זמן בפורמט של 00:00 - שעות ודקות</span><br><input type="time" name="end_time" value="<?php echo esc_attr( get_option('end_time') ); ?>" /></td>
			</tr>			
			<tr valign="bottom">
				<th scope="row">הגדירו את הטקסט היוצג בדף החסימה:</th>		
				<th scope="row">טקסט זה יוצג לגולשים בזמן שהאתר יהיה חסום</th>				
				<td><input type="text" name="display_text" value="<?php echo esc_attr( get_option('display_text') ); ?>" /></td>
			</tr>
			<tr valign="bottom">
			    <th scope="row">או לחילופין בחרו את הטמפלייט  שיוצג בדף החסימה (ממאגר הטמפלייטים של אלמנטור הנמצאים באתר שלכם):</th>
			    <th>הקפידו לבחור טמפלייט ללא אפשרות גלילה, וללא אפשרות שום פעולה כדי שלא יגרם חילול שבת ח"ו</th>
			    <td><select name="display_template" id="display_template">
			            <option value>--ללא--</option>
    			        <?php 
    			            $query_args = array(
                            	'posts_per_page' => '-1',
                            	'post_type' => 'elementor_library',
                            	'post_status' => 'publish'
                            );
                            $the_query = new WP_Query( $query_args );
                            if ( $the_query->have_posts() ) {
                            	while ( $the_query->have_posts() ) {
                            		$the_query->the_post();
                            		echo '<option value="' . get_the_ID() .'"';
                            		if(get_option('display_template') == get_the_ID())
                            		    echo 'selected';
                            		echo '>' . get_the_title() . '</option>';
                            	}
                            	wp_reset_postdata();
                            } 
    			        ?>
			    </select></td>
			</tr>
			<tr>
				<td colspan="3">
					טיפ: בטמפלייט החסימה ניתן לשלב את השורטקוד <code>[wp_shammor_countdown]</code> כדי להציג סטופר המראה עוד כמה זמן יפתח האתר מחדש.
				</td>
			</tr>
		</table>
		
		<?php submit_button(); ?>

	</form>
	</div>
<?php 
}

function _print_shammor_page() {
	?>
<!DOCTYPE html>
<html dir="rtl" lang="he-IL">
	<head>
		<title>אתר סגור בשבתות וחגים</title>
	</head>
	<body>
		<?php 
		    if(empty(get_option('display_template'))) {
		        echo '<div style="text-align: center; padding: 100px;"><h1>';
		        echo get_option('display_text'); 
		        echo '</h1><div>';
		    } else {
		        echo do_shortcode('[elementor-template id="' . get_option('display_template') . '"]');
		    }
		 ?>
	</body>
</html>
	
	<?php
}	

function shamor_site_get_headers_503($date_end = '')
{
	nocache_headers();
    $protocol = 'HTTP/1.0';
    if (isset($_SERVER['SERVER_PROTOCOL']) && 'HTTP/1.1' === $_SERVER['SERVER_PROTOCOL']) {
      $protocol = 'HTTP/1.1';
    }
    header("$protocol 503 Service Unavailable", true, 503);
    if($date_end != '')
	    header('Retry-After: ' . gmdate('D, d M Y H:i:s', $date_end));
}

add_action( 'wp_enqueue_scripts', 'wp_shammor_enqueue' );
function wp_shammor_enqueue($hook) {
	wp_enqueue_script( 'ajax-script', plugins_url( 'script.js', __FILE__ ), array('jquery') );
	wp_localize_script( 'ajax-script', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
}

add_action( 'wp_ajax_validate_wp_shammor', 'validate_wp_shammor' );
add_action( 'wp_ajax_nopriv_validate_wp_shammor', 'validate_wp_shammor' );
function validate_wp_shammor() {
    move_out_of_site();
}

add_shortcode('wp_shammor_countdown', 'wp_shammor_countdown');
function wp_shammor_countdown($atts) {
    $result = '<div class="shamor_countdown" style="direction:ltr; font-size: 60px;">';
	$havdalah = get_havdalah_time();
	$result .= '<span id="shammor_countdown_hours">';
	$hours = $havdalah[0];
	if($hours < 0)
		$hours = 0;
	if($hours < 10)
		$result .= '0';
	$result .= $hours . '</span> : <span id="shammor_countdown_minutes">';
	$minutes = $havdalah[1];
	if($minutes < 0)
		$minutes = 0;
	if($minutes < 10)
		$result .= '0';
	$result .=  $minutes . '</span> : <span id="shammor_countdown_seconds">';
	$seconds = $havdalah[2];
	if($seconds < 0)
		$seconds = 0;
	if($seconds < 10)
		$result .= '0';
	$result .= $seconds . '</span>';
    $result .= '</div>
				<script>
					hours = ' . $hours .';
					minutes = ' . $minutes . ';
					seconds = ' . $seconds . ';
					setInterval(function() {
						if(seconds > 0) {
							seconds--;
						} else if(minutes > 0) {
							minutes --;
							seconds = 59;
						} else if(hours > 0) {
							hours --;
							minues = 59;
							seconds = 59;
						}
						document.getElementById("shammor_countdown_hours").innerHTML = ((hours < 10 ? "0" : "") + hours);
						document.getElementById("shammor_countdown_minutes").innerHTML = ((minutes < 10 ? "0" : "") + minutes);
						document.getElementById("shammor_countdown_seconds").innerHTML = ((seconds < 10 ? "0" : "") + seconds);
					}, 1000); 
				</script>';
    return $result;
}

function get_havdalah_time() {
	$times = get_shabbat_times();
	$havdalah = $times['havdalah'];
	
	$seconds = strtotime('Saturday ' . date('H:i:s', $havdalah)) - time();
	$days = gmdate('d', $seconds);
	$hours = $days * 24 + gmdate('h', $seconds);
	$hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
	$time = $hours . ':' . gmdate('i:s', $seconds);

	return apply_filters('havdalah_time', explode(':', $time));
}