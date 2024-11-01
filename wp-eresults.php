<?
/*
Plugin Name: Wp Eresults
Plugin URI: http://wpresults.com
Description: Shows the results of EuroMillions and earn money whenever your visitors to play, for life.
Version: 1.4 (14/5/2009)
Author: INGENIA-COMUNICACION for en15.com
Author URI: http://wpresults.com
*/

/*  Copyright 2009  en15.com
*/


//********** INI GLOBAL CONSTS & VARS

define('en15_E_RES_BASE_URL','http://www.en15.com'); 

//Some domain vars
	$locale=WPLANG;// Wordpress locale config	
	// plugin unique name
	$wp_eresults_plugin_domain='wp-eresults';
	//link to en15 page
	$e15_e_res_serverside_url=en15_E_RES_BASE_URL.'/css/plugins/wp_e_results.php?wp_e=1&lang='.$locale;
	//link to js script
	$e15_e_res_serversidde_js_url=en15_E_RES_BASE_URL.'/css/plugins/e15_e_res2.js';
	//plugin dir
	$plugindir=PLUGINDIR.'/'.dirname(plugin_basename(__FILE__));
	//widget images dir
	$wp_eresults_img_dir=get_option('siteurl').'/'.$plugindir;

//Other ini vars

	//defalut widget configuration options
	$options_defaults = array('title' => 'En15 E-Results', 'title_color' => '', 'height' => '200px', 'backcolor' => '#ffffff', 'border' => '0px', 'bordercolor' => '', 'backgroundimage'=>'','fontsize'=>'11px','fontcolor'=>'#333','gadminCount'=>'');
	
	
	
//************* END GLOBAL VARS

// carga de contenidos por socket
	
function http_get($url, $range = 0)
	{
		$buffer='';
		$query='';
	    $url_stuff = parse_url($url);
	    $port = isset($url_stuff['port']) ? $url_stuff['port'] : 80;
	   
	    $fp = @fsockopen($url_stuff['host'], $port);
	   
	    if (!$fp)
	        return false;
	    $query  = 'GET '.$url_stuff['path'].(isset($url_stuff['query'])?'?'.$url_stuff['query']:'')." HTTP/1.1\r\n";
	    $query .= 'Host: '.$url_stuff['host']."\r\n";
	    $query .= 'Connection: close'."\r\n";
	    $query .= 'Cache-Control: no'."\r\n";
	    $query .= 'Accept-Ranges: bytes'."\r\n";
	    if ($range != 0)
	        $query .= 'Range: bytes='.$range.'-'."\r\n"; // -500
	    $query .= "\r\n";
	   
	    fwrite($fp, $query);
	   
	    $chunksize = 1*(1024*1024);
	    $headersfound = false;

	    while (!feof($fp) && !$headersfound) {
	        $buffer .= @fread($fp, 1);
	        if (preg_match('/HTTP\/[0-9]\.[0-9][ ]+([0-9]{3}).*\r\n/', $buffer, $matches)) {
	            $headers['HTTP'] = $matches[1];
	            $buffer = '';
	        } else if (preg_match('/([^:][A-Za-z_-]+):[ ]+(.*)\r\n/', $buffer, $matches)) {
	            $headers[$matches[1]] = $matches[2];
	            $buffer = '';
	        } else if (preg_match('/^\r\n/', $buffer)) {
	            $headersfound = true;
	            $buffer = '';
	        }

	        if (strlen($buffer) >= $chunksize)
	            return false;
	    }

	    if (preg_match('/4[0-9]{2}/', $headers['HTTP']))
	        return false;
	    else if (preg_match('/3[0-9]{2}/', $headers['HTTP']) && !empty($headers['Location'])) {
	        $url = $headers['Location'];
	        return http_get($url, $range);
	    }
		$contenido='';
	    while (!feof($fp) && $headersfound) {
	        $buffer = @fread($fp, $chunksize);
			$contenido.=$buffer;
	    }

	    $status = fclose($fp);
	    return $contenido;

	}
	
	
// Call the language function and load the textdomain
add_action('init', 'wp_eresults_load_textdomain');
function wp_eresults_load_textdomain() {
	global $wp_eresults_plugin_domain,$plugindir;
	load_plugin_textdomain($wp_eresults_plugin_domain, $plugindir );
}


// CSS for widget format
function wp_eresults_css() {

	global $plugindir,$wp_eresults_img_dir,$options_defaults,$wp_eresults_plugin_domain;
	
		$options = (array) get_option($wp_eresults_plugin_domain);
		

		foreach ( $options_defaults as $key => $value )
			if ( !isset($options[$key]) || strlen($options[$key])==0 )
				$options[$key] = $options_defaults[$key];


		$border=str_replace(' ','',$options['border']);
		$border=str_replace('px','',$options['border']);
		$border='border:'.(strlen($border)>0?$border.'px solid;':'none;');
		$backcolor=(strlen($options['backcolor'])>0?'background-color:'.$options['backcolor'].';':'background-color:#fff;');
		$bordercolor=(strlen($options['bordercolor'])>0?'border-color:'.$options['bordercolor'].' !important;':'');
		
		$title_color=(strlen($options['title_color'])>0?'color:'.$options['title_color'].' !important;':'');
		$border_title_color=(strlen($options['title_color'])>0?$options['title_color'].' !important;':'');
		$heightFx=(strlen($options['height'])>0?'min-height:'.$options['height'].';':'min-height:200px;');
		$heightIE=(strlen($options['height'])>0?'height:'.$options['height'].';':'height:200px;');
		$backgroundimage=(strlen($options['backgroundimage'])>0?"background:url($wp_eresults_img_dir/".$options['backgroundimage'].") left 32px no-repeat;":"");
		$fontcolor=(strlen($options['fontcolor'])>0?'color:'.$options['fontcolor'].' !important;':'color:#333 !important;');
		$fontsize=(strlen($options['fontsize'])>0?'font-size:'.$options['fontsize'].' !important;':'font-size:11px !important;');
		
		
	
	echo "
	<style type='text/css'>
	
	.wp_eresults .widgettitle{ display:none; }	
	.wp_eresults div{ text-transform:none; letter-spacing:normal; border:0px solid #000; line-height:normal; }
	#e15_e_res_box {
		position:relative; 
		left:0px; margin-top:0px;
 		width:100%;
		$heightFx\n
		padding:0px 0px 10px 0px;margin:0px;
		$border\n
		$bordercolor\n
		$backcolor\n
		$backgroundimage\n
		font-size:11px;
		line-height:11px;
	}
	* html #e15_e_res_box{ $heightIE }
		

	#e15_e_res_title{
		position:relative; left:0px; top:0px; width:100%; height:12px; 
		margin:0px; padding:0px;
		padding-top:10px; padding-bottom:8px;
		font-size:12px;
		font-weight:bold; text-indent:30px;
		color:#000;
		$title_color\n	
		background:url($wp_eresults_img_dir/e_ico.gif) left center no-repeat #fff;
		border:none;
		border-top:1px solid $border_title_color;
		border-bottom:1px solid $border_title_color;
	}


	#e15_e_res_content{
		margin:0px; padding:0px;
		margin-left:3%;
		padding-top:6px;padding-bottom:10px;
		width:94%;
		text-align:center;
		font-size:12px; 
		font-weight:bold;
	
	}
	
	#e15_e_res_content_date{
		position:relative; left:0px; width:100%; margin-top:5px; margin-left:0px; 
		font-weight:bold; text-align:center; 
		$fontsize\n	
		$fontcolor\n	
	} 
	#e15_e_res_content_date span{
		display:block;
		position:relative; left:0px; width:100%; margin-top:5px; margin-left:0px; 
		font-weight:bold; text-align:center; 
		$fontsize\n	
		$fontcolor\n	
	}
	
	#e15_e_res_content_results{
		position:relative; width:100%; margin-top:10px; margin-bottom:10px; height:20px; 
	}
	
	.e15_e_res_content_num{
		position:relative;
		color:#fff;
		font-size:11px; font-weight:bold;
		width:14%; height:17px;
		text-align:center;
		margin-top:2px;
		float:left;
		padding-top:3px;
		background:url($wp_eresults_img_dir/num.gif) center top no-repeat;
	}
	
	* html .e15_e_res_content_num{ height:20px;}
	
	#e15_e_res_content_star1,#e15_e_res_content_star2{
		position:relative;
		color:#333;
		font-size:11px; font-weight:bold;
		width:14%;height:17px;
		text-align:center;
		margin-top:2px;
		float:left;
		padding-top:3px;
		background:url($wp_eresults_img_dir/star.gif) center top no-repeat;
		
	}
	* html #e15_e_res_content_star1,* html #e15_e_res_content_star2{ height:20px;}
	
	#e15_e_res_content_jackpot{
		position:relative; left:0px; width:100%; min-height:30px; padding-top:0px;
		font-weight:bold; 
		$fontsize\n	
		$fontcolor\n	
	}
	* html #e15_e_res_content_jackpot{ height:30px;  }
	
	#e15_e_res_content_jackpot_data{ display:block; position:relative; color:#c00; }
	
	#e15_e_res_content_jackpot_date_data{
		position:relative; left:0px; width:100%; margin-top:0px; margin-left:0px; 
		font-weight:bold; text-align:center; min-height:20px; 
		$fontsize\n	
		$fontcolor\n	
	}
	* html #e15_e_res_content_jackpot_date_data{ height:20px; }
	
	#e15_e_res_button1{ 
		position:relative; left:0px; margin-top:0px; 
		width:100%;	height:30px; 
		font-weight:bold; 
		text-align:center; 
		$fontsize\n	
		$fontcolor\n
	}
	
	#e15_e_res_button1 a{ 
		display:block; position:absolute;
		left:0px; top:0px;
		width:100%; height:24px;
		text-decoration:none;
		text-align:center;
		color:#333;
		$fontsize\n	
		background:url($wp_eresults_img_dir/bot_jugarOff.gif) center top no-repeat;
		padding-top:5px; text-indent:10px; border:none;

	}	
	
	#e15_e_res_button1 a:hover{ background:url($wp_eresults_img_dir/bot_jugarOn.gif) center top no-repeat; color:#c00;} 
		
	</style>
	";
}

function wp_eresults_admin_css() {

	echo "
		<style type='text/css'>
		
		#e15_e_res_admin_box{
			position:relative; background-color:#F2F2F2;padding-top:10px; padding-bottom:10px;
		
		}
		#e15_e_res_admin_p0{
			position:relative; text-align:center; border-bottom:1px dotted #ccc;	margin-top:6px; padding-bottom:20px;
		}
		#e15_e_res_admin_p0 label{
			color:#333; font-size:12px; font-style:italic;
		}
		#e15_e_res_admin_p0 input{
			width:240px; height:20px; font-size:11px; color:#666; text-align:center;
		}
		#e15_e_res_admin_p0 a{
			display:block; position:relative;
			left:0px; margin-top:10px;
			width:100%; height:19px;
			text-decoration:none;
			text-align:center;
			font-weight:bold;
			font-size:11px; 
		}	
		#e15_e_res_admin_p0 a span{ background-color:#666; color:#fff; border:1px solid; padding:3px;}

	
		#e15_e_res_admin_p0 a:hover span{ color:#ff9;} 
		
		.e15_e_res_admin_p{
			position:relative; text-align:right; border-bottom:1px dotted #ccc;	margin-top:2px; padding-bottom:2px;
		}
		.e15_e_res_admin_p label{
			color:#333; font-size:11px;
		}
		.e15_e_res_admin_p input{
			width:60px; height:22px; text-align:center; font-size:12px; color:#666;
		}
		</style>
	"; 

}


add_action('wp_head', 'wp_eresults_css');
add_action('admin_head', 'wp_eresults_admin_css');



//widget init/setup function
function wp_eresults_init() {
		
	if ( !function_exists('register_sidebar_widget') )
			return;
			
	function wp_eresults($args) {
	
			global $plugindir,$locale,$wp_eresults_plugin_domain,$e15_e_res_serverside_url;
			global $e15_e_res_serversidde_js_url,$options_defaults;
			extract($args);
			$textos="&t1=".urlencode(__('Draw date: ',$wp_eresults_plugin_domain))."&t2=".urlencode(__('Next Jackpot: ',$wp_eresults_plugin_domain));
			$datos_contenido=http_get($e15_e_res_serverside_url.$textos);
			$options = (array) get_option($wp_eresults_plugin_domain);
			
			echo $before_widget;
			foreach ( $options_defaults as $key => $value )
				if ( !isset($options[$key]) )
					$options[$key] = $options_defaults[$key];
		

			$border=str_replace(' ','',$options['border']);
			$border=str_replace('px','',$options['border']);
			$border='border:'.(strlen($border)>0?$border.'px;':'none');
			$backcolor=(strlen($options['backcolor'])>0?'background-color:'.$options['backcolor'].';':'');
			
			$title_color=(strlen($options['title_color'])>0?'color:'.$options['title_color']:'');
			$height=(strlen($options['height'])>0?'height:'.$options['height']:'200px');
			$gadminCount = (( strlen($options['gadminCount'])>0  && strpos($options['gadminCount'],'@')>0) ? $options['gadminCount']:'');
		
			echo $before_title;  
			echo $after_title;			
			echo "
			<div id='e15_e_res_box' >";
			
			echo	"<script src='$e15_e_res_serversidde_js_url'></script>
					<div id='e15_e_res_title' >".$options['title']."</div>
					
					<div id='e15_e_res_content'>";
						
						echo $datos_contenido;			
			
			echo	"</div>";
	
			echo "	<div id='e15_e_res_button1'><a href='#' onClick='return e15_e_res_onClick(\"".utf8_encode($gadminCount.'&e15_wp_url='.get_bloginfo('url'))."\")'  onFocus='if(this.blur){this.blur();}' target='_blank'>".__('Bet Now',$wp_eresults_plugin_domain)."</a></div>";
			
			echo '</div>';
					
			echo $after_widget;
	}
		
	function wp_eresults_control() {

		global $locale,$options_defaults,$wp_eresults_plugin_domain;
		
			$options = get_option($wp_eresults_plugin_domain);
			
			if ( !is_array($options) )	$options = $options_defaults;
			

			if ( $_POST['en15_e_results-submit'] ) {

				$options['title'] = strip_tags(stripslashes($_POST['en15_e_results-title']));
				$options['title_color'] = strip_tags($_POST['en15_e_results-title_color']);
				$options['height'] = strip_tags($_POST['en15_e_results-height']);
				$options['backcolor'] = strip_tags($_POST['en15_e_results-backcolor']);
				$options['border'] = strip_tags($_POST['en15_e_results-border']);
				$options['bordercolor'] = strip_tags($_POST['en15_e_results-bordercolor']);
				$options['backgroundimage'] = strip_tags($_POST['en15_e_results-backgroundimage']);
				$options['fontsize'] = strip_tags($_POST['en15_e_results-fontsize']);
				$options['fontcolor'] = strip_tags($_POST['en15_e_results-fontcolor']);
				$options['gadminCount'] = strip_tags(stripslashes($_POST['en15_e_results-gadminCount']));
				
				update_option($wp_eresults_plugin_domain, $options);
			}
			

	        $title = htmlspecialchars($options['title'], ENT_QUOTES);
			$title_color = ( strlen($options['title_color'])>0 ? $options['title_color'] : '');
			$height = ( strlen($options['height'])>0 ? $options['height'] : '200px');
			$backcolor = ( strlen($options['backcolor'])>0 ? $options['backcolor'] : '#ffffff');
			$border = ( strlen($options['border'])>0 ? $options['border']: '0px');
			$bordercolor = ( strlen($options['bordercolor'])>0 ? $options['bordercolor'] : '');
			$backgroundimage = ( strlen($options['backgroundimage'])>0 ? $options['backgroundimage']:'');
			$fontsize = ( strlen($options['fontsize'])>0 ? $options['fontsize']:'11px');
			$fontcolor = ( strlen($options['fontcolor'])>0 ? $options['fontcolor']:'#333');
			$gadminCount = (( strlen($options['gadminCount'])>0  && strpos($options['gadminCount'],'@')>0) ? $options['gadminCount']:'');
			
			echo '
			<div id="e15_e_res_admin_box">
				
				<div id="e15_e_res_admin_p0">
					<label for="en15_e_results-gadminCount">'.__("Registered Email on your En15 user count.<br>If you don't configure this we'll not be able to pay a commission:",$wp_eresults_plugin_domain).'<br><input id="en15_e_results-gadminCount" name="en15_e_results-gadminCount" type="text" value="'.$gadminCount.'" /></label>';
				
			if (strlen($gadminCount)<6 || !strpos($gadminCount,'@'))
				echo '<a href="'.en15_E_RES_BASE_URL.'/register.php?wpUrl='.utf8_encode(get_bloginfo('url')).'" target="_blank" onFocus="if(this.blur){this.blur();}">'.__("Don't have one? > ",$wp_eresults_plugin_domain).'<span>'.__('Create a count',$wp_eresults_plugin_domain).'</span></a>';

				
			echo '		
				</div>
				
				
				<div class="e15_e_res_admin_p"><label for="en15_e_results-title">'.__('Title:',$wp_eresults_plugin_domain).'<input style="width:200px;" id="en15_e_results-title" name="en15_e_results-title" type="text" value="'.$title.'" /></label></div>
				
				<div class="e15_e_res_admin_p"><label for="en15_e_results-title_color">'.__('Title Color (hex):',$wp_eresults_plugin_domain).'<input id="en15_e_results-title_color" name="en15_e_results-title_color" type="text" value="'.$title_color.'" /></label></div>
				
				<div class="e15_e_res_admin_p"><label for="en15_e_results-fontcolor">'.__('Font Color (hex):',$wp_eresults_plugin_domain).'<input id="en15_e_results-fontcolor" name="en15_e_results-fontcolor" type="text" value="'.$fontcolor.'" /></label></div>
				
				<div class="e15_e_res_admin_p"><label for="en15_e_results-fontsize">'.__('Font Size (px):',$wp_eresults_plugin_domain).'<input id="en15_e_results-fontsize" name="en15_e_results-fontsize" type="text" value="'.$fontsize.'" /></label></div>
				
				<div class="e15_e_res_admin_p"><label for="en15_e_results-height">'.__('Box Height (px):',$wp_eresults_plugin_domain).' <input id="en15_e_results-height" name="en15_e_results-height" type="text" value="'.$height.'" /></label></div>

				<div class="e15_e_res_admin_p"><label for="en15_e_results-backcolor">'.__('Background Color (hex):',$wp_eresults_plugin_domain).'<input id="en15_e_results-backcolor" name="en15_e_results-backcolor" type="text" value="'.$backcolor.'" /></label></div>
						
				<div class="e15_e_res_admin_p"><label for="en15_e_results-border">'.__('Border (px):',$wp_eresults_plugin_domain).'<input id="en15_e_results-border" name="en15_e_results-border" type="text" value="'.$border.'" /></label></div>
				
				<div class="e15_e_res_admin_p"><label for="en15_e_results-bordercolor">'.__('Border Color (hex):',$wp_eresults_plugin_domain).'<input id="en15_e_results-bordercolor" name="en15_e_results-bordercolor" type="text" value="'.$bordercolor.'" /></label></div>
				
				<div class="e15_e_res_admin_p"><label for="en15_e_results-backgroundimage">'.__('Background Image (url):',$wp_eresults_plugin_domain).'<input style="width:140px;" id="en15_e_results-backgroundimage" name="en15_e_results-backgroundimage" type="text" value="'.$backgroundimage.'" /></label></div>
				
			</div>
			
			<input type="hidden" id="en15_e_results-submit" name="en15_e_results-submit" value="1" />';
			
		
		}
		
		register_sidebar_widget('en15 E-results', 'wp_eresults');
		register_widget_control('en15 E-results', 'wp_eresults_control', 300, 265);
}

add_action('plugins_loaded', 'wp_eresults_init');



?>
