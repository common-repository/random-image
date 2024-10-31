<?php
/*
 * Plugin Name:   Random Image
 * Version:       3.0
 * Plugin URI:    http://wordpress.org/extend/plugins/random-image/
 * Description:   This plugin allows you to display random image in the sidebar, posts or any other location in WordPress. Simply upload image and adjust Advance Settings, random image will appear just on the place where you want. Adjust your settings <a href="options-general.php?page=random-image">here</a>.
 * Author:        MaxBlogPress
 * Author URI:    http://www.maxblogpress.com
 *
 * License:       GNU General Public License
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * Copyright (C) 2007 www.maxblogpress.com
 *
 */
  
  
$mbri_path     = preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', __FILE__);
$mbri_path     = str_replace('\\','/',$mbri_path);
$mbri_dir      = substr($mban_path,0,strrpos($mbri_path,'/'));
$mbri_siteurl  = get_bloginfo('wpurl');
$mbri_siteurl  = (strpos($mbri_siteurl,'http://') === false) ? get_bloginfo('siteurl') : $mbri_siteurl;
$mbri_fullpath = $mbri_siteurl.'/wp-content/plugins/'.$mbri_dir.'';
$mbri_fullpath  = $mbri_fullpath.'random-image/';
$mbri_abspath  = str_replace("\\","/",ABSPATH); 
define('MBP_RI_ABSPATH', $mbri_abspath);
define('MBP_RI_LIBPATH', $mbri_fullpath);
define('MBP_RI_NAME', 'Random Image');
define('MBP_RI_VERSION', '3.0');  
define('MBP_RI_SITEURL', $mbri_siteurl);

global $table_prefix;
$rdmimg_tbl = $table_prefix.'mbprandimage';
define('MBP_RANDOM_TBL', $rdmimg_tbl);

// Hook for adding admin menus
add_action('admin_menu', 'mr_random_image');
add_filter('the_content', 'mr_ramdomImg_post');
add_action('activate_'.$mbri_path, 'MBP_ri_active' );

function MBP_ri_active(){
	$db_check = mysql_query('SHOW TABLES LIKE '.MBP_RANDOM_TBL.'');
	$exists = mysql_fetch_row($db_check);
	if ( !$exists ) {
		$sql = "CREATE TABLE ".MBP_RANDOM_TBL." (                                        
		   `image_id` int(11) NOT NULL auto_increment,                           
		   `img_name` varchar(200) collate latin1_general_ci NOT NULL,           
		   `flag` enum('0','1') collate latin1_general_ci NOT NULL default '1',  
		   PRIMARY KEY  (`image_id`)                                             
		 )";
	mysql_query($sql);	 
	}
}

// action function for above hook
function mr_random_image() {
	// Add a new submenu under Options:
	add_options_page('Random Image', 'Random Image', 8, 'random-image', 'mr__option_page');
}

/**
 * Creates a directory to upload banners
 */
function __mbanMakeDir() {
	$mbpimg_upload_path = MBP_RI_ABSPATH.'wp-content/mbp-random-image';
	if ( is_admin() && !is_dir($mbpimg_upload_path) ) {
		@mkdir($mbpimg_upload_path);
	}
	return $mbpimg_upload_path;
}

function mr__option_page() {

	$mbpri_activate = get_option('mbpri_activate');
	$reg_msg = '';
	$mbpri_msg = '';
	$form_1 = 'mbpri_reg_form_1';
	$form_2 = 'mbpri_reg_form_2';
		// Activate the plugin if email already on list
	if ( trim($_GET['mbp_onlist']) == 1 ) {
		$mbpri_activate = 2;
		update_option('mbpri_activate', $mbpri_activate);
		$reg_msg = 'Thank you for registering the plugin. It has been activated'; 
	} 
	// If registration form is successfully submitted
	if ( ((trim($_GET['submit']) != '' && trim($_GET['from']) != '') || trim($_GET['submit_again']) != '') && $mbpri_activate != 2 ) { 
		update_option('mbpri_name', $_GET['name']);
		update_option('mbpri_email', $_GET['from']);
		$mbpri_activate = 1;
		update_option('mbpri_activate', $mbpri_activate);
	}
	if ( intval($mbpri_activate) == 0 ) { // First step of plugin registration
		global $userdata;
		mbpriRegisterStep1($form_1,$userdata);
	} else if ( intval($mbpri_activate) == 1 ) { // Second step of plugin registration
		$name  = get_option('mbpri_name');
		$email = get_option('mbpri_email');
		mbpriRegisterStep2($form_2,$name,$email);
	} else if ( intval($mbpri_activate) == 2 ) { // Options page
		if ( trim($reg_msg) != '' ) {
			echo '<div id="message" class="updated fade"><p><strong>'.$reg_msg.'</strong></p></div>';
		}

 // Start Execute  
	if( $_GET['action'] == 1 ){
		$imgname = mysql_query("select img_name from ".MBP_RANDOM_TBL." where image_id='".$_GET['id']."'");
		$delete_imgname = mysql_fetch_array($imgname);
		@unlink(MBP_RI_ABSPATH.'wp-content/mbp-random-image/'.$delete_imgname['img_name'].'');
		$db_sql = "delete from ".MBP_RANDOM_TBL." where image_id='".$_GET['id']."'";
		mysql_query($db_sql);
		$msg = 'Image removed from system';
	}
	
	if( $_GET['action'] == "pub"  || $_GET['action'] == "unpub" ){
	if( $_GET['action'] == "pub" ) $flag='0';
	elseif( $_GET['action'] == "unpub" ) $flag='1';
		$imgname = mysql_query("Update ".MBP_RANDOM_TBL." set flag='".$flag."' where image_id='".$_GET['id']."'");
	}

	if($_POST['submit'] == "Submit"){
		$grouprec = array(''.$_POST['img_option'].'',''.$_POST['width'].'',''.$_POST['height'].'',''.$_POST['pwdby'].'');
		update_option('mbprev_randomImage', $grouprec);
		$msg = "Advance Image Option Updated";
	}
	
	if($_POST['upload'] == "Upload"){ // Upload from local computer
		$mbp_valid_file  = array("image/pjpeg", "image/png", "image/jpeg", "image/gif", "image/bmp");
		$mbpimg_upload_path = __mbanMakeDir();  
		$upload_name      = $_FILES['url_local']['name'];
		$upload_type      = $_FILES['url_local']['type'];
		$upload_size      = $_FILES['url_local']['size'];
		$upload_tmp_name  = $_FILES['url_local']['tmp_name'];
		
		$file_ext_pos     = strrpos($upload_name,'.');
		$filename         = substr($upload_name,0,$file_ext_pos);
		$extension        = substr($upload_name,$file_ext_pos+1);
		$upload_name      = $filename.'_'.date('YmdHis').'.'.$extension;
		$banner_path      = $mbpimg_upload_path.'/'.$upload_name;
		$banner_url       = MBP_RI_SITEURL.'/wp-content/mbp-random-image/'.$upload_name; 
		$url              = $banner_url;
		if ( in_array($upload_type,$mbp_valid_file) ) {
			if ( move_uploaded_file($upload_tmp_name, $banner_path) ) {
				list($banner_width, $banner_height) = @getimagesize($banner_path);
				$sql = "insert into ".MBP_RANDOM_TBL."(img_name) values('".$upload_name."')";
				mysql_query($sql);
				$msg = "Image uploaded from local computer.\n";
			} else {
				$upload_err = 1;
				$msg = "Image couldn't be uploaded from local computer.";
			}
		} else {
			$upload_err = 1;
			$msg = "Image couldn't be uploaded from local computer. Invalid file type.";
		}
	}//Eof Upload

	
	$img_data = get_option('mbprev_randomImage');
	if( $img_data[0] == 'orig' ) $orig = 'checked'; 
	elseif( $img_data[0] == 'high' ) $high = 'checked'; 
	elseif( $img_data[0] == 'wide' ) $wide = 'checked'; 
	elseif( $img_data[0] == 'spec' ) $spec = 'checked';
	if( $img_data[3] == 'pwdby' ) $pwdby = 'checked';
	
	$msg_ri_txt = "We request you to have the powered by link as this would be visible to your blog visitors and they would be benefited by this plugin as well.<br/><br/>If you want to remove the powered by link, we will appreciate a review post for this plugin in your blog. This will help lots of other people know about the plugin and get benefited by it. By the way, if for any reason you do not want to write a review post then its ok as well. No obligation. We will be much happy if you find out some other ways to spread the word for this plugin ";
		?>
<script>		
function __ShowHide(curr, img, path) {
	var curr = document.getElementById(curr);
	if ( img != '' ) {
		var img  = document.getElementById(img);
	}
	var showRow = 'block'
	if ( navigator.appName.indexOf('Microsoft') == -1 && curr.tagName == 'TR' ) {
		var showRow = 'table-row';
	}
	if ( curr.style == '' || curr.style.display == 'none' ) {
		curr.style.display = showRow;
		if ( img != '' ) img.src = path + 'image/minus.gif';
	} else if ( curr.style != '' || curr.style.display == 'block' || curr.style.display == 'table-row' ) {
		curr.style.display = 'none';
		if ( img != '' ) img.src = path + 'image/plus.gif';
	}
}
function ConfirmDelete(){
	boolReturn = confirm(" Are you sure you wish to delete this record?");
	if (boolReturn)
	return true;
	else
	return false;
}
</script>	
<script type="text/javascript" src="<?php echo MBP_RI_LIBPATH;?>tooltip.js"></script>
<link href="<?php echo MBP_RI_LIBPATH;?>tooltip.css" rel="stylesheet" type="text/css">
	
<div class="wrap">
<h2><?php echo MBP_RI_NAME.' '.MBP_RI_VERSION; ?></h2>
<br>
<strong><img src="<?php echo MBP_RI_LIBPATH;?>image/how.gif" border="0" align="absmiddle" /> <a href="http://wordpress.org/extend/plugins/random-image/other_notes/" target="_blank">How to use it</a>&nbsp;&nbsp;&nbsp;
		<img src="<?php echo MBP_RI_LIBPATH;?>image/comment.gif" border="0" align="absmiddle" /> <a href="http://www.maxblogpress.com/forum/forumdisplay.php?f=26" target="_blank">Community</a></strong>
<br>

<br>
				
<?php if($msg){ ?>
<div id="message" class="updated fade" style="border:1px #0099FF dashed; padding:5px 5px 5px 35px ; color:#FF3300; font-weight:bold"><p ><?php echo $msg; ?></p></div><br><?php } ?>
			
				<table width="100%" border="0" cellpadding="3" cellspacing="4">
				<tr>
				<td width="48%" valign="top">

				<br>
				<form action="" method="post" enctype="multipart/form-data">
				<b>Upload Image:</b><input type="file" name="url_local" />
				<input type="Submit" value="Upload" name="upload" />
				</form>
				<br>

				<form action="" method="post">
			<b><img src="<?php echo MBP_RI_LIBPATH?>image/plus.gif" id="rep_img" border="0" /><a style="cursor:hand;cursor:pointer" onclick="__ShowHide('div1','rep_img','<?php echo MBP_RI_LIBPATH ?>')">Advance Image Option:</a></b><br> 
				<div id="div1" style="display:none" >
					<div style="border:1px #ccc dashed; padding:10px 0px 10px 10px; width:90%; background-color:#f1f1f1;" >
<input name="img_option" type="radio" value="orig" <?php echo $orig; ?> /> &nbsp;Leave the image as it is.<br>
<input name="img_option" type="radio" value="high" <?php echo $high; ?> /> &nbsp;Scale to a specific HEIGHT.<br>
<input name="img_option" type="radio" value="wide" <?php echo $wide; ?> /> &nbsp;Scale to a specific WIDTH.<br>
<input name="img_option" type="radio" value="spec" <?php echo $spec; ?> /> &nbsp;Constrain both height & width.<br>
<input name="pwdby" type="checkbox" value="pwdby" <?php echo $pwdby; ?> /> &nbsp;Remove "powered by Random Image"&nbsp;  <a href="" onMouseover="tooltip('<?php echo $msg_ri_txt; ?>',480)" onMouseout="hidetooltip()" style="border-bottom:none;"><img src="<?php echo MBP_RI_LIBPATH."image/help.gif"; ?>"></a><br>
<div id="w_h_show" style="padding:5px 5px 5px 5px;background-color:#FFFFFF">Width:&nbsp;&nbsp;<input type="text" name="width"  value="<?php echo $img_data['1']; ?>" /><br>Height:&nbsp;<input type="text" name="height" value="<?php echo $img_data['2']; ?>"></div>
<input name="submit" type="Submit" value="Submit" />
					</div><br>
				</div>
				</form>
<br>				
<div style="background-color:#f1f1f1; padding:2px 0px 2px 5px; border:1px #0066CC dashed" >
<p><b>Post Tag:</b><br>
<input type="text" style="width:200px" value="<!--mbprandomimage-->">
<p><b>Template Tag:</b><br>
&lt;?php<br>
     if (function_exists('__MBPR_RandomImageTag'))<br>
     {<br>
       echo  __MBPR_RandomImageTag();<br>
     }<br>
?&gt;
</p>
</div>				
				
				</td>
				<td width="52%" valign="top">
				<!-- test -->
				<?php 
				$sql = "select * from ".MBP_RANDOM_TBL." ";
				$db_process = mysql_query($sql);
				$noforows = mysql_num_rows($db_process);
				if( $noforows > 0 ){
				 ?>
				<table width="100%" border="0" cellpadding="3" cellspacing="4">
				<tr style="background-color:#ccc;">
				<td width="15%"><div align="center"><b>S.no</b></div></td>
				<td width="49%"><b>Image</b></td>
				<td colspan="2"><div align="center"><b>Action</b></div></td>
				</tr>
				
			   <?php $i = 1; while( $img = mysql_fetch_array($db_process) ){ 
			   if($i%2==0) $color='#f1f1f1';
			   else $color='';
			    ?>
				<tr>
				<td align="center" style="background-color:<?php echo $color; ?>"><?php echo $i; ?></td>
				<td style="background-color:<?php echo $color; ?>"><img src="<?php echo MBP_RI_SITEURL.'/wp-content/mbp-random-image/'.$img['img_name']; ?>" width="30px" height="30px" ></td>
				<td width="22%" style="background-color:<?php echo $color; ?>">
				  <div align="center">
				    <?php
				if($img['flag'] == 1){
				echo "<a href='?page=random-image&action=pub&id=".$img['image_id']."'>Publish</a>";
				}else{
				echo "<a href='?page=random-image&action=unpub&id=".$img['image_id']."'>Unpublish</a>";
				}
				?>				
			      </div></td>
				<td width="14%" style="background-color:<?php echo $color; ?>"><div align="center"><a href="?page=random-image&action=1&id=<?php echo $img['image_id']; ?>" onclick=" return ConfirmDelete()">Delete</a></div></td>
				</tr>
				<?php 
				$i++;
				} } 
				?>
				</table>
				<!--end-->
				</td>
				</tr>
				</table>
				
				<br>
				
<div align="center" style="background-color:#f1f1f1; padding:5px 0px 5px 0px" >
<p align="center"><strong><?php echo MBP_RI_NAME.' '.MBP_RI_VERSION; ?> by <a href="http://www.maxblogpress.com" target="_blank">MaxBlogPress</a></strong></p>
<p align="center">This plugin is the result of <a href="http://www.maxblogpress.com/blog/219/maxblogpress-revived/" target="_blank">MaxBlogPress Revived</a> project.</p>
</div>
			</div>
           <?php
}
}

	/****************
		DISPLAY IMAGE ON TEMPLATE
	****************/
function mr_ramdomImg_post($post_content){

	global $post;
	global $wp_version;
	
	$pwdby = get_option('mbprev_randomImage');
		$post_tag = '<!--mbprandomimage-->';
		$search = "(<!--\s*mbprandomimage\s*-->)";
		///echo stristr($post_content,$post_tag);
		if ( stristr($post_content,$post_tag) ) { 
			if (preg_match_all($search, $post_content, $matches)) { 
			  	if ( is_array( $matches )) { 
					foreach ( $matches as $key => $val ) { 
					        $randomImage    = __MBPR_RandomImageTag(); 
							$post_content   = preg_replace($search, $randomImage, $post_content, 1);
						}
			 	 }
			}   
		}
		return $post_content;
}


function __MBPR_RandomImageTag(){

	$db_sql = "select * from ".MBP_RANDOM_TBL." where flag='1'";
	$process_result = mysql_query($db_sql);
	$noofrows = mysql_num_rows($process_result);
	
	if( $noofrows > 0 ){
	$bannerPath = MBP_RI_ABSPATH.'wp-content/mbp-random-image/';
	$bannerUrl =  MBP_RI_SITEURL.'/wp-content/mbp-random-image';
	$get_randomImagearray = get_option('mbprev_randomImage');
	$scaleOption = $get_randomImagearray[0];
	$scaleHeight = $get_randomImagearray[2];
	$scaleWidth = $get_randomImagearray[1];
	$pwdby = $get_randomImagearray[3];
	
	$image_types = array('jpg','png','gif'); // Array of valid image types 
	
/*	$image_directory = opendir($bannerPath);
	while($image_file = readdir($image_directory))
	{  
	  if(in_array(strtolower(substr($image_file,-3)),$image_types))
	  {  
		 $image_array[] = $image_file;
		 sort($image_array);
		 reset ($image_array);
	  }
	}
*/	
	
	while( $randomImg = mysql_fetch_array($process_result) ){
		 $image_array[] = $randomImg['img_name'];
		 sort($image_array);
		 reset ($image_array);
	}
	
	$image_filename=$image_array[rand(1,count($image_array))-1];
	$filename=$bannerUrl.'/'.$image_filename;
	
	$imageInfo = getimagesize($bannerPath.'/'.$image_filename);
	 $physHeight = $imageInfo[1];
	 $physWidth = $imageInfo[0];
	
	switch($scaleOption)
	{
	  case 'high':  
	$ratio = $physHeight / $scaleHeight;
		 $physWidth = $physWidth / $ratio;
		 $physHeight = $scaleHeight;
	break;
	  case 'wide':
	$ratio = $physWidth / $scaleWidth;
		$physHeight = $physHeight / $ratio;
		$physWidth = $scaleWidth;
	break;
	  case 'spec':
	$physHeight = $scaleHeight;
	$physWidth = $scaleWidth;
	break;
	  default:
	break;
	}
	
	if( $pwdby == '' ){ $pwdby = '<br><a href="http://wordpress.org/extend/plugins/random-image/" style="font-size:9px;font-weight:normal;font-color:#0000FF;letter-spacing:-1px" target="_blank">Powered by Random Image</a><br>';
	}else{
	$pwdby = '';
	}
	
	return '<img src="'.$filename.'" title="'.substr($image_filename,0,-4).'" alt="'.substr($image_filename,0,-4).'" height="'.$physHeight.'" width="'.$physWidth.'"/>'.$pwdby;
	}	
}

/***************
	WIDGET
****************/

if( $wp_version < 2.8  ) {
	add_action('plugins_loaded', 'MBP_rimg_widget');
}

function MBP_rimg_widget(){

		if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') ) { 
		return; 
		}
		
		function RandomImageWidgetController() {
		if ( isset($_POST["random_image_submit"]) == 1 ) {
		$randomimg_sidebar_title   = $_POST['random_image'];
		update_option('mbp_random_image_widget_title', $randomimg_sidebar_title);
		}
		$random_imgtitle = get_option('mbp_random_image_widget_title');
		?>
		<div><strong>Title:</strong></div>
		<div><input type="text" name="random_image" value="<?php echo $random_imgtitle; ?>"  style="width:180px"  /></div>
		<input type="hidden" name="random_image_submit" id="random_image_submit" value="1" />
		<?php
		}
	
		function RandomImageWidgetSidebar($args) {  
		global $wp_version;
		
		extract($args);
		echo $before_widget;
		echo $before_title;
		echo $after_title;
		$title = get_option('mbp_random_image_widget_title');
		echo "<h2>".$title."</h2>";
		echo $randomImg = __MBPR_RandomImageTag();
		echo $after_widget;
		}
		
		if ( function_exists('wp_register_sidebar_widget') ) { // fix for wordpress 2.2
			wp_register_sidebar_widget(sanitize_title('Random Image'), 'Random Image', 'RandomImageWidgetSidebar');
		} else {
			register_sidebar_widget('Random Image', 'RandomImageWidgetSidebar');
		}
		register_widget_control('Random Image', 'RandomImageWidgetController', '', '210px');
}


/* WP greater then 2.8 */
if( $wp_version >= 2.8  ) {
add_action('widgets_init', create_function('', 'return register_widget("MBPRI_widget");'));
class MBPRI_widget extends WP_Widget {
	function MBPRI_widget() {
		parent::WP_Widget(false, $name = 'Random Image');	
	}
	function widget($args, $instance) {		
		global $wp_version;
		extract( $args );
		echo $before_widget
			  . $before_title
			  . $instance['title']
			  . $after_title
			  . __MBPR_RandomImageTag()
			  . $after_widget; 
	}
	function update($new_instance, $old_instance) {				
		return $new_instance;
	}
	function form($instance) {				
		$title = esc_attr($instance['title']);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
		<?php 
	}
}
}

/***************
	EOF WIDGET
****************/


// Srart Registration.

/**
 * Plugin registration form
 */
function mbpriRegistrationForm($form_name, $submit_btn_txt='Register', $name, $email, $hide=0, $submit_again='') {
	$wp_url = get_bloginfo('wpurl');
	$wp_url = (strpos($wp_url,'http://') === false) ? get_bloginfo('siteurl') : $wp_url;
	$plugin_pg    = 'options-general.php';
	$thankyou_url = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'];
	$onlist_url   = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'].'&amp;mbp_onlist=1';
	if ( $hide == 1 ) $align_tbl = 'left';
	else $align_tbl = 'center';
	?>
	
	<?php if ( $submit_again != 1 ) { ?>
	<script><!--
	function trim(str){
		var n = str;
		while ( n.length>0 && n.charAt(0)==' ' ) 
			n = n.substring(1,n.length);
		while( n.length>0 && n.charAt(n.length-1)==' ' )	
			n = n.substring(0,n.length-1);
		return n;
	}
	function mbpriValidateForm_0() {
		var name = document.<?php echo $form_name;?>.name;
		var email = document.<?php echo $form_name;?>.from;
		var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
		var err = ''
		if ( trim(name.value) == '' )
			err += '- Name Required\n';
		if ( reg.test(email.value) == false )
			err += '- Valid Email Required\n';
		if ( err != '' ) {
			alert(err);
			return false;
		}
		return true;
	}
	//-->
	</script>
	<?php } ?>
	<table align="<?php echo $align_tbl;?>">
	<form name="<?php echo $form_name;?>" method="post" action="http://www.aweber.com/scripts/addlead.pl" <?php if($submit_again!=1){;?>onsubmit="return mbpriValidateForm_0()"<?php }?>>
	 <input type="hidden" name="unit" value="maxbp-activate">
	 <input type="hidden" name="redirect" value="<?php echo $thankyou_url;?>">
	 <input type="hidden" name="meta_redirect_onlist" value="<?php echo $onlist_url;?>">
	 <input type="hidden" name="meta_adtracking" value="mr-random-image">
	 <input type="hidden" name="meta_message" value="1">
	 <input type="hidden" name="meta_required" value="from,name">
	 <input type="hidden" name="meta_forward_vars" value="1">	
	 <?php if ( $submit_again == 1 ) { ?> 	
	 <input type="hidden" name="submit_again" value="1">
	 <?php } ?>		 
	 <?php if ( $hide == 1 ) { ?> 
	 <input type="hidden" name="name" value="<?php echo $name;?>">
	 <input type="hidden" name="from" value="<?php echo $email;?>">
	 <?php } else { ?>
	 <tr><td>Name: </td><td><input type="text" name="name" value="<?php echo $name;?>" size="25" maxlength="150" /></td></tr>
	 <tr><td>Email: </td><td><input type="text" name="from" value="<?php echo $email;?>" size="25" maxlength="150" /></td></tr>
	 <?php } ?>
	 <tr><td>&nbsp;</td><td><input type="submit" name="submit" value="<?php echo $submit_btn_txt;?>" class="button" /></td></tr>
	 </form>
	</table>
	<?php
}

/**
 * Register Plugin - Step 2
 */
function mbpriRegisterStep2($form_name='frm2',$name,$email) {
	$msg = 'You have not clicked on the confirmation link yet. A confirmation email has been sent to you again. Please check your email and click on the confirmation link to activate the plugin.';
	if ( trim($_GET['submit_again']) != '' && $msg != '' ) {
		echo '<div id="message" class="updated fade"><p><strong>'.$msg.'</strong></p></div>';
	}
	?>
	<style type="text/css">
	table, tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_RI_NAME.' '.MBP_RI_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff; text-align:left;">
	  <tr><td align="center"><h3>Almost Done....</h3></td></tr>
	  <tr><td><h3>Step 1:</h3></td></tr>
	  <tr><td>A confirmation email has been sent to your email "<?php echo $email;?>". You must click on the link inside the email to activate the plugin.</td></tr>
	  <tr><td><strong>The confirmation email will look like:</strong><br /><img src="http://www.maxblogpress.com/images/activate-plugin-email.jpg" vspace="4" border="0" /></td></tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr><td><h3>Step 2:</h3></td></tr>
	  <tr><td>Click on the button below to Verify and Activate the plugin.</td></tr>
	  <tr><td><?php mbpriRegistrationForm($form_name.'_0','Verify and Activate',$name,$email,$hide=1,$submit_again=1);?></td></tr>
	 </table>
	 </td></tr></table><br />
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding:8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding:8px; background-color:#ffffff; text-align:left;">
	   <tr><td><h3>Troubleshooting</h3></td></tr>
	   <tr><td><strong>The confirmation email is not there in my inbox!</strong></td></tr>
	   <tr><td>Dont panic! CHECK THE JUNK, spam or bulk folder of your email.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>It's not there in the junk folder either.</strong></td></tr>
	   <tr><td>Sometimes the confirmation email takes time to arrive. Please be patient. WAIT FOR 6 HOURS AT MOST. The confirmation email should be there by then.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>6 hours and yet no sign of a confirmation email!</strong></td></tr>
	   <tr><td>Please register again from below:</td></tr>
	   <tr><td><?php mbpriRegistrationForm($form_name,'Register Again',$name,$email,$hide=0,$submit_again=2);?></td></tr>
	   <tr><td><strong>Help! Still no confirmation email and I have already registered twice</strong></td></tr>
	   <tr><td>Okay, please register again from the form above using a DIFFERENT EMAIL ADDRESS this time.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr>
		 <td><strong>Why am I receiving an error similar to the one shown below?</strong><br />
			 <img src="http://www.maxblogpress.com/images/no-verification-error.jpg" border="0" vspace="8" /><br />
		   You get that kind of error when you click on &quot;Verify and Activate&quot; button or try to register again.<br />
		   <br />
		   This error means that you have already subscribed but have not yet clicked on the link inside confirmation email. In order to  avoid any spam complain we don't send repeated confirmation emails. If you have not recieved the confirmation email then you need to wait for 12 hours at least before requesting another confirmation email. </td>
	   </tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>But I've still got problems.</strong></td></tr>
	   <tr><td>Stay calm. <strong><a href="http://www.maxblogpress.com/contact-us/" target="_blank">Contact us</a></strong> about it and we will get to you ASAP.</td></tr>
	 </table>
	 </td></tr></table>
	 </center>		
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_RI_NAME.' '.MBP_RI_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}

/**
 * Register Plugin - Step 1
 */
function mbpriRegisterStep1($form_name='frm1',$userdata) {
	$name  = trim($userdata->first_name.' '.$userdata->last_name);
	$email = trim($userdata->user_email);
	?>
	<style type="text/css">
	tabled , tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_RI_NAME.' '.MBP_RI_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:2px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	  <tr><td align="center">
		<table width="548" align="center" cellpadding="3" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff;">
		  <tr><td align="center"><h3>Please register the plugin to activate it. (Registration is free)</h3></td></tr>
		  <tr><td align="left">In addition you'll receive complimentary subscription to MaxBlogPress Newsletter which will give you many tips and tricks to attract lots of visitors to your blog.</td></tr>
		  <tr><td align="center"><strong>Fill the form below to register the plugin:</strong></td></tr>
		  <tr><td align="center"><?php mbpriRegistrationForm($form_name,'Register',$name,$email);?></td></tr>
		  <tr><td align="center"><font size="1">[ Your contact information will be handled with the strictest confidence <br />and will never be sold or shared with third parties ]</font></td></tr>
		</table>
	  </td></tr></table>
	 </center>
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_RI_NAME.' '.MBP_RI_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}
?>
