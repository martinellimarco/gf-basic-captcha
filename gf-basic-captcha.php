<?php
/*
Plugin Name: GF Basic Captcha
Description: A very simple captcha solution for Gravity Forms
Author: 13Byte srl
Author URI: mailto:assistenza@13byte.com
Version: 1.0
License: GPL-3.0+
License URI: http://www.gnu.org/licenses/gpl-3.0.txt
*/

class GF_Basic_Captcha extends GF_Field {
	public $type = 'basic_captcha';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Basic Captcha', 'gravityforms' );
	}

	function get_form_editor_field_settings() {
		return array(
		    'label_setting',
		);
	}

	function get_field_input($form, $value = '', $entry = null){
		$id = (int) $this->id;

		$k = rand(0, 9999);
		$h = $k ^ get_option('basic_captcha_key', 0xCAFE);

		return "
			<div id='basic-captcha'>
				<img src='".get_site_url()."/wp-admin/admin-ajax.php?action=gfbasiccaptcha_getimg&k=$k'/>
				<br/>
				<input type='text' name='input_{$id}[v]' style='width:128px'/>
				<input type='hidden' name='input_{$id}[h]' value='$h'/>
			</div>
		";
	}

	function validate($value, $form){
		$id = (int) $this->id;

		if(isset($_POST["input_$id"]) && is_array($_POST["input_$id"]) && isset($_POST["input_$id"]['v']) && isset($_POST["input_$id"]['h'])){
			$h = (int)$_POST["input_$id"]['h'];
			$v = (int)$_POST["input_$id"]['v'];

			if($v == ($h ^ get_option('basic_captcha_key', 0xCAFE))){
				return;
			}
		}

		$this->failed_validation = true;
		$this->validation_message = __('INVALID CAPTCHA', 'basic_captcha');
	}
}


add_action('init', function(){
	GF_Fields::register(new GF_Basic_Captcha());
}, 99, 1);

add_action('wp_ajax_gfbasiccaptcha_getimg', 'gfbasiccaptcha_getimg');
add_action('wp_ajax_nopriv_gfbasiccaptcha_getimg', 'gfbasiccaptcha_getimg');
function gfbasiccaptcha_getimg(){
	$k = (int)isset($_GET['k']) ? $_GET['k'] : -1;
	if($k<0 || $k>9999){
		die();
	}

	$n = [];

	$n[3] = $k      %10;
	$n[2] = $k/10   %10;
	$n[1] = $k/100  %10;
	$n[0] = $k/1000 %10;

	header('Content-type: image/jpeg');

	$image = imagecreate(128, 64);
	imagecolorallocate($image, rand(0xA0, 0xFF), rand(0xA0, 0xFF), rand(0xA0, 0xFF));
	
	for($i=0; $i<count($n); $i++){
		$text_color = imagecolorallocate($image, rand(0x00, 0x8F), rand(0x00, 0x8F), rand(0x00, 0x8F));
		imagettftext(
			$image,
			20+rand(-3, 3),	//font size
			rand(-20, 20),  //angle
			15 + 25*$i + rand(-5, 5), //x
			40+rand(-5, 5), //y
			$text_color,
			plugin_dir_path(__FILE__).'arial.ttf',
			$n[$i]
		);
	}

	imagesetthickness($image, 1);
	for ($i=0; $i<20; $i++) {
		$arc_color = imagecolorallocate($image, rand(0x94, 0x9B), rand(0x94, 0x9B), rand(0x94, 0x9B));
		imagearc(
		    $image,
		    rand(1, 128), // origin x
		    rand(1, 128), // origin y
		    rand(1, 256), // arc width
		    rand(1, 256), // arc height
		    rand(1, 360), // arc start angle
		    rand(1, 360), // arc end angle
		    $arc_color
		);
	}

	imagejpeg($image);
	imagedestroy($image);

	die();
}

register_activation_hook(__FILE__, function(){
	update_option('basic_captcha_key', rand(0x0000, 0xFFFF));
});

