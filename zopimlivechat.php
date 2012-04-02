<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

/*
 * *********************************************
 * ** ZopimLiveChat Addon Module ***

  If you don't have one, please register here
  https://www.zopim.com/signup/trial
 
  More About Zopim
  http://www.zopim.com
  Zopim is registered by Zopim Technologies Pte Ltd

 * *********************************************
 */

define('ZOPIM_SCRIPT_DOMAIN', "zopim.com");
define('ZOPIM_BASE_URL', "https://www.zopim.com/");
define('ZOPIM_GETACCOUNTDETAILS_URL', ZOPIM_BASE_URL . "plugins/getAccountDetails");
define('ZOPIM_SETDISPLAYNAME_URL', ZOPIM_BASE_URL . "plugins/setDisplayName");
define('ZOPIM_IMINFO_URL', ZOPIM_BASE_URL . "plugins/getImSetupInfo");
define('ZOPIM_IMREMOVE_URL', ZOPIM_BASE_URL . "plugins/removeImSetup");
define('ZOPIM_LOGIN_URL', ZOPIM_BASE_URL . "plugins/login");
define('ZOPIM_SIGNUP_URL', ZOPIM_BASE_URL . "plugins/createTrialAccount");
define('ZOPIM_THEMES_LIST', "http://zopim.com/assets/dashboard/themes/window/plugins-themes.txt");
define('ZOPIM_COLORS_LIST', "http://zopim.com/assets/dashboard/themes/window/plugins-colors.txt");
define('ZOPIM_LANGUAGES_URL', "http://translate.zopim.com/projects/zopim/");
define('ZOPIM_DASHBOARD_URL', "http://dashboard.zopim.com/");
define('ZOPIM_SMALL_LOGO', "http://zopim.com/assets/branding/zopim.com/chatman/online.png");
define('ZOPIM_IM_LOGOS', "http://www.zopim.com/static/images/im/");
define('ZOPIM_THEMES_URL', "http://");
define('ZOPIM_COLOURS_URL', "http://");

function zopimlivechat_config() {
    $configarray = array(
        "name" => "Zopim Live Chat",
        "description" => "This is chat module that using Zopim, More About Zopim http://www.zopim.com",
        "version" => "1.0",
        "author" => "zopim",
        "language" => "english",
        "fields" => array(
            "z_user" => array("FriendlyName" => "Zopim Username", "Type" => "text", "Size" => "100", "Description" => "Zopim Username", "Default" => "",),
            "z_pass" => array("FriendlyName" => "Zopim Password", "Type" => "password", "Size" => "100", "Description" => "Zopim Password",),
            "z_ssl" => array("FriendlyName" => "Use SSL", "Type" => "yesno",),
            ));
    return $configarray;
}

function zopimlivechat_activate() {

    # Create Custom DB Table
    $query = "CREATE TABLE `mod_zopimlivechat` (`user` varchar( 100 ) NOT NULL PRIMARY KEY ,`salt` varchar( 250 ) NOT NULL, `key` varchar( 100 ) NOT NULL )";
    $result = mysql_query($query);

    # Return Result
    return array('status' => 'success', 'description' => 'Welcome to ZopimLiveChat!, please see the forum for more help.');
    //return array('status' => 'error', 'description' => 'Error, there was a problem activating the module');
    // return array('status' => 'info', 'description' => 'Zopim Live Chat can be found in this forum');
}

function zopimlivechat_deactivate() {

    # Remove Custom DB Table
    $query = "DROP TABLE `mod_zopimlivechat`";
    $result = mysql_query($query);

    # Return Result
    return array('status' => 'success', 'description' => 'Thanks for using Zopim Libe Chat');
    //return array('status'=>'error','description'=>'If an error occurs you can return an error message for display here');
    //return array('status'=>'info','description'=>'If you want to give an info message to a user you can return it here');
}

function zopimlivechat_upgrade($vars) {

    $version = $vars['version'];

    # Run SQL Updates for V1.0 to V1.1
    /*
      if ($version < 1.0) {
      $query = "ALTER `mod_addonexample` ADD `demo2` TEXT NOT NULL ";
      $result = mysql_query($query);
      }
     * 
     */
}

function zopimlivechat_output($vars) {

    $modulelink = $vars['modulelink'];
    $LANG = $vars['_lang'];

    $error = false;

    $q = @mysql_query("SELECT * FROM tbladdonmodules WHERE module = 'zopimlivechat'");
    while ($arr = mysql_fetch_array($q)) {
        $settings[$arr['setting']] = $arr['value'];
    }

    echo '<p>' . $LANG['intro'] . '</p>';
    echo '<p>' . $LANG['description'] . '</p>';
    echo '<p>' . $LANG['documentation'] . '</p>';

    if (!$settings['z_user'] OR !$settings['z_pass']) {
        echo 'Please use the Configure Addons page <a href=\"configaddonmods.php\">here</a>.';
        $error = true;
    } else {
        $table = "mod_zopimlivechat";
        $fields = "salt";
        $where = "";
        $result = select_query($table, $fields, $where);
        $data = mysql_fetch_array($result);
        
        if (!$data['salt']) {
            $logindata = array("email" => $settings['z_user'], "password" => $settings['z_pass']);
            $loginresult = json_to_array(do_post_request(ZOPIM_LOGIN_URL, $logindata));

            if (isset($loginresult->error)) {
                echo "<b>Could not log in to Zopim. Please check your login details. If problem persists, try connecting without SSL enabled.</b>";
                $error = true;
            } else {

                $account = getAccountDetails($loginresult->salt);
                if (isset($account)) {
                    $table = "mod_zopimlivechat";
                    if (!$data['user']) {
                        $values = array("user" => $settings['z_user'], "key" => $account->account_key, "salt" => $loginresult->salt);
                        $newid = insert_query($table, $values);
                    } else {
                        $update = array("key" => $account->account_key, "salt" => $loginresult->salt);
                        $where = array("user" => $settings['z_user']);
                        update_query($table, $update, $where);
                    }
                } else {
                    echo "<b>Could not log in to Zopim. We were unable to contact Zopim servers. Please check with your server administrator to ensure that <a href='http://www.php.net/manual/en/book.curl.php'>PHP Curl</a> is installed and permissions are set correctly.</b>";
                    $error = true;
                }
            }
        }

        if (!$error) {
            echo "Thanks for using Zopim Live Chat with WHMCS, copy code below and paste before the closing &lt;/body&gt; tag of the page.";

            $q2 = @mysql_query("SELECT * FROM mod_zopimlivechat WHERE user = '" . $settings['z_user'] . "' ");
            $arr2 = mysql_fetch_array($q2);
            $code = $arr2['key'];

            $scripts = "<!--Start of Zopim Live Chat Script-->
<script type=\"text/javascript\">
window.\$zopim||(function(d,s){var z=\$zopim=function(c){z._.push(c)},$=z.s=
d.createElement(s),e=d.getElementsByTagName(s)[0];z.set=function(o){z.set.
_.push(o)};z._=[];z.set._=[];$.async=!0;$.setAttribute('charset','utf-8');
$.src='//cdn.zopim.com/?" . $code . "';z.t=+new Date;$.
type='text/javascript';e.parentNode.insertBefore($,e)})(document,'script');
</script>
<!--End of Zopim Live Chat Script-->";

            echo "<textarea cols='100' rows='10'>";
            echo $scripts;
            echo "</textarea>";
        }
    }
}

function zopimlivechat_sidebar($vars) {

    $modulelink = $vars['modulelink'];
    $version = $vars['version'];

    $sidebar = '<span class="header">ZopimLiveChat</span>
    <ul class="menu">
        <li><a href="https://www.zopim.com/signup/trial" target="_blank" >Register</a></li>
        <li><a href="http://www.zopim.com" target="_blank" >Lern More</a></li>
        <li><a href="#">Version: ' . $version . '</a></li>
    </ul>';
    return $sidebar;
}

function do_post_request($url, $_data, $optional_headers = null) {
    if (!is_ssl())
        $url = str_replace("https", "http", $url);

    $data = array();

    while (list($n, $v) = each($_data)) {
        $data[] = urlencode($n) . "=" . urlencode($v);
    }

    $data = implode('&', $data);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

function json_to_array($json) {
    require_once('JSON.php');
    $jsonparser = new Services_JSON();
    return ($jsonparser->decode($json));
}

function to_json($variable) {
    require_once('JSON.php');
    $jsonparser = new Services_JSON();
    return ($jsonparser->encode($variable));
}

function getAccountDetails($salt) {
    $salty = array("salt" => $salt);
    return json_to_array(do_post_request(ZOPIM_GETACCOUNTDETAILS_URL, $salty));
}

function curl_get_url($filename) {
    $ch = curl_init();
    $timeout = 5; // set to zero for no timeout
    curl_setopt($ch, CURLOPT_URL, $filename);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $file_contents = curl_exec($ch);
    curl_close($ch);
    return $file_contents;
}

function is_ssl() {
    $q = @mysql_query("SELECT * FROM tbladdonmodules WHERE module = 'zopimlivechat'");
    while ($arr = mysql_fetch_array($q)) {
        $settings[$arr['setting']] = $arr['value'];
    }
    if ($settings["z_ssl"])
        return true;
    else
        return false;
}

?>