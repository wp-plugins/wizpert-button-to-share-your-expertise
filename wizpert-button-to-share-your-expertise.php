<?php 
/*
Plugin Name: Wizpert Button to Share Your Expertise
Plugin URI: http://wizpert.com/?beta_key=3ba42
Description: Call Me on Wizpert.&nbsp; To get started: 1) Click the Activate link to the left of this description, 2) Register your email on the <a href='options-general.php?page=wizpert-button-to-share-your-expertise'>Settings</a> page, 3) Complete your free profile on Wizpert, if you don't have one yet, and 4) Return to the <a href='options-general.php?page=wizpert-button-to-share-your-expertise'>Settings</a> page, to customize the placement of your button. 
Version: 1.1
Author: Wizpert
Author URI: http://wizpert.com/?beta_key=3ba42
License: GPL
*/

register_activation_hook(__FILE__,'wizpert_button_install'); 
register_deactivation_hook( __FILE__,'wizpert_button_remove');
register_uninstall_hook( __FILE__,'wizpert_button_remove');
add_filter('the_content', 'wizpert_post_display');
add_filter('the_content', 'wizpert_page_display');
add_filter('plugin_action_links', 'wizpert_settings_link', 10, 2 );
add_action('plugins_loaded', 'wizpert_button_init');
add_shortcode('Wizpert','wizpert_button_iframe');
add_shortcode('wizpert','wizpert_button_iframe');
add_shortcode('wizpert_small','wizpert_button_small_iframe');

function wizpert_button_install() {
add_option('wizpert_button_data', 'beta_key=3ba42', '', 'yes');
add_option('wizpert_button_bottompost', 'checked', '', 'yes');
add_option('wizpert_button_bottompage', 'checked', '', 'yes');
add_option('wizpert_button_result', 'start_result', '', 'yes');
add_option('wizpert_button_email', '', '', 'yes');
$pagenum = wizpert_add_page();
add_option("wizpert_button_pagenum", $pagenum, '', 'yes');
}

function wizpert_button_remove() {
delete_option('wizpert_button_data');
delete_option('wizpert_button_bottompost');
delete_option('wizpert_button_bottompage');
delete_option('wizpert_button_result');
delete_option('wizpert_button_email');
$pageid = get_option('wizpert_button_pagenum');
if ($pageid <> '') {
	wp_trash_post($pageid);
}
delete_option('wizpert_button_pagenum');
}

function wizpert_button_iframe() {
	return "<iframe width='180' height='260' src='http://wizpert.com/wizapi/widget?" . get_option( "wizpert_button_data",'') . "&size=standard' frameborder='0' scrolling='no' allowfullscreen></iframe>";
}

function wizpert_button_small_iframe() {
	return "<iframe width='180' height='65' src='http://wizpert.com/wizapi/widget?" . get_option( "wizpert_button_data",'') . "' frameborder='0' scrolling='no' allowfullscreen></iframe>";
}

function wizpert_button_create() {
	echo wizpert_button_iframe();
}

function wizpert_post_display($content) {    
    if ( is_single() and get_option("wizpert_button_bottompost",'') == 'checked') {
		if (get_option('wizpert_button_pagenum') == '' or get_the_ID() <> get_option('wizpert_button_pagenum')) {  
        	return $content . "[wizpert_small]";  
    	} 
		else {  
        	return $content;  
    	}
	}
	else {
		return $content;  
	}  
}

function wizpert_page_display($content) {    
    if ( is_page() and get_option("wizpert_button_bottompage",'') == 'checked') {
		if (get_option('wizpert_button_pagenum') == '' or get_the_ID() <> get_option('wizpert_button_pagenum')) {  
        	return $content . "[wizpert_small]";  
    	} 
		else {  
        	return $content;  
    	}
	}
	else {
		return $content;  
	}  
}

function wizpert_settings_link($links, $file) {
	static $this_plugin;
	if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
	if ($file == $this_plugin){
		$settings_link = '<a href="' . admin_url("options-general.php?page=wizpert-button-to-share-your-expertise") . '">Settings</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}

function wizpert_button_init() {
    if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') ){
        return;
    }
    wp_register_sidebar_widget('wizpert_widget_01', 'Wizpert Button', 'wizpert_button_create', array('description' => "Call Me on Wizpert.&nbsp; Drag this widget to your sidebar on the right to add the button."));
}

function wizpert_add_page() {
    return wp_insert_post(array(
        'post_name' => "Ask Me",
        'post_title' => 'Ask Me',
        'post_type' => 'page',
        'post_status' => 'publish',
        'comment_status' => 'closed',
        'post_content' => "Talk to me when I'm online, and ask me anything about my blog. <p> [Wizpert] <p> Wizpert is a community of helpful, caring and knowledgeable experts, on a variety of topics like parenting, relationships or nutrition."));
}

function wizpert_remove_page() {
	$pagenum = get_option('wizpert_button_pagenum');
    wp_trash_post($pagenum);
}

if ( is_admin() ){
add_action('admin_menu', 'wizpert_button_admin_menu');
function wizpert_button_admin_menu() {
add_options_page('Wizpert', 'Wizpert', 'administrator',
'wizpert-button-to-share-your-expertise', 'wizpert_button_settings');
}
}

function wizpert_process_email($active) {
    if ($active == "active") {
		$sendemail = "&sendemail=1";
	}
	else {
		$sendemail = "";
	}
	extract(wizpert_api_call("http://wizpert.com/wizapi/getbutton?email=" .
                        urlencode(get_option('wizpert_button_email')).
                        "&usage=wordpress".$sendemail));
    if (!$success) {
        $message = "<b>Temporary problems.</b>&nbsp; Please try again.&nbsp; If the problem persists, please contact us at <a href='mailto:support@wizpert.com?subject=Wordpress Problem'>support@wizpert.com</a>.";
    } else {      
		$data = json_decode($raw_data,true);
        $message = "<b>Temporary problems.</b>&nbsp; Please try again.&nbsp; If the problem persists, please contact us at <a href='mailto:support@wizpert.com?subject=Wordpress Problem'>support@wizpert.com</a>.";
        if ($data["success"] == true) {
			$result = $data["data"][apiresult];
			update_option('wizpert_button_result',$result);
			$message = '';
			if ($result == 'registered') {
			    update_option('wizpert_button_data',$data["data"][urlstring]); 
   			}
        } else {
            $message = "<b>We encountered a problem.</b>&nbsp; Please contact us at <a href='mailto:support@wizpert.com?subject=Wordpress Problem'>support@wizpert.com</a>.";
        }
    }
	return $message;
}

function wizpert_api_call($url) {
    $get_result = wp_remote_get($url);
    $success = true;
    if ($get_result['response']['code'] != 200) {
        $success = false;
        $raw_data = $get_result['response']['message'];
    } else {
        $raw_data = $get_result['body'];
    }
    return compact('raw_data', 'success');
}


function wizpert_button_settings() {
	
	if ($_POST["action"] == "submit_email" and is_admin())  
	 {  
		$new_email = $_POST["wizpert_button_email"];
		if (wizpert_isvalidemail($new_email) == true) {
			update_option("wizpert_button_email", $new_email);
			update_option('wizpert_button_data',"beta_key=3ba42&email=".$new_email);
			$message = wizpert_process_email("active");
		}  
		else {
		$message = '<b>Invalid Email</b>';
		}
		switch (get_option('wizpert_button_result')) {
			case 'check_email':
			$message = '<b>Email Sent</b>&nbsp; Please check your email, and follow the confirmation link to create a profile.';
			break;
			case 'complete_registration':
			$message = '<b>Profile Incomplete</b>&nbsp; Please click <a href="http://wizpert.com/expert_login" target="_blank">here</a> to complete your profile on Wizpert.';
			break;
			case 'select_expertise':
			$message = '<b>No Topic Selected</b>&nbsp; Please click <a href="http://wizpert.com/dashboard_subjects" target="_blank">here</a> to select a topic for your button.';
			break;
			case 'registered':
			$message = '<b>Linked to Profile</b>';
			break;
		}
	 }  
	
	 if (get_option('wizpert_button_result') <> 'registered' and get_option("wizpert_button_email") <> "" and is_admin()) {
		wizpert_process_email("");
		switch (get_option('wizpert_button_result')) {
			case 'complete_registration':
			$message = '<b>Profile Incomplete</b>&nbsp; Please click <a href="http://wizpert.com/expert_login" target="_blank">here</a> to complete your profile on Wizpert.';
			break;
			case 'select_expertise':
			$message = '<b>No Topic Selected</b>&nbsp; Please click <a href="http://wizpert.com/dashboard_subjects" target="_blank">here</a> to select a topic for your button.';
			break;
		}
	 }
	
	 if ($_POST["action"] == "update" and is_admin())  
	 {  
	    $_POST["show_pages"] == "on" ? update_option("wizpert_button_bottompage", "checked") : update_option("wizpert_button_bottompage", "");  
	    $_POST["show_posts"] == "on" ? update_option("wizpert_button_bottompost", "checked") : update_option("wizpert_button_bottompost", "");  
		if ($_POST["show_wpage"] == "on" and get_option("wizpert_button_pagenum") == "") {
			$pagenum = wizpert_add_page();
		 	update_option("wizpert_button_pagenum", $pagenum );
		}
		if ($_POST["show_wpage"] <> "on" and get_option("wizpert_button_pagenum") <> "") {
			wizpert_remove_page();
	     	update_option("wizpert_button_pagenum", '');
		}
	    $message = "<b>Settings Saved</b>";  
	 }  
		
	switch (get_option("wizpert_button_pagenum")) {
		case '':
		$wpage = "";
		break;
		default:
		$wpage = "checked";
		break;
	}
	$options["page"] = get_option("wizpert_button_bottompage");  
	$options["post"] = get_option("wizpert_button_bottompost");
	$options["wpage"] = $wpage;

	echo "<div class='wrap'>";
	if ($message <> "") {
	 	echo "<div id='message' class='updated fade'><p>".$message." 
	     	</p></div>";
	}
	
	echo "<div id='icon-options-general' class='icon32'><br /></div>
		<h2>Wizpert Settings</h2>
		<h3>Your Profile:</h3>";
		
	if (get_option('wizpert_button_result') <> 'registered')
		{		
			echo "<form method='post'' action=''> 
	     		<input type='hidden' name='action' value='submit_email' />
				<p>Create a free profile, or connect with your existing Wizpert account.";
			if (get_option('wizpert_button_email') <> "") {	
				echo "<p>Enter Email: <input size='40' name='wizpert_button_email' type='text' id='wizpert_button_email' value=".get_option('wizpert_button_email')." />";
			}
			else {
				echo "<p>Enter Email: <input size='40' name='wizpert_button_email' type='text' id='wizpert_button_email' value='' />";
			}
			
			echo "&nbsp;<input type='submit' class='button-primary' value='Submit' /> 
	     		</form>
				<p>Your visitors will be able to talk with you when you are available.";
		}
	else {
			echo "<p>You have successfully connected your Wizpert account.
				<p>If you would like to use a different account, please re-activate the plugin.";
		}

 echo "	<p>&nbsp;
		<p>
		<h3>Display Your Button:</h3> 
		<p>
	 	To place the button in your <i>sidebar</i> menu, go to your <a href='widgets.php'>Widgets</a> page, and drag the <i>Wizpert Button</i> widget into your <i>sidebar</i>.
		<p>
     	<form method='post'' action=''> 
     	<input type='hidden' name='action' value='update' />
     	<input name='show_pages' type='checkbox' id='show_pages' ".$options["page"]." /> Under every Page<br /> 
     	<input name='show_posts' type='checkbox' id='show_posts' ".$options["post"]." /> Under every Post<br /> 
		<input name='show_wpage' type='checkbox' id='show_wpage' ".$options["wpage"]." /> On a newly created <i>Ask Me</i> page";

		if (get_option("wizpert_button_pagenum") <> '') {
			echo " (edit <a href='post.php?post=". get_option("wizpert_button_pagenum") ."&action=edit'>here</a>)";
		}
		
echo " <br />
	    <p>You can also type <b>[Wizpert]</b> in any post or page to manually add the button.
		<p>
		<input type='submit' class='button-primary' value='Save Changes' /> 
     	</form>
	    <p>&nbsp;
		<p>Any questions or feedback, contact us at <a href='mailto:info@wizpert.com?subject=Wizpert Button for Wordpress'>info@wizpert.com</a>.";
}

/* help function from Douglas Lovell*/

function wizpert_isvalidemail($email) {
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)
   {
      $isValid = false;
   }
   else
   {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if
(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\\\","",$local)))
      {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))
         {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}
?>