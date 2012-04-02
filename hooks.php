<?php

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

function ZopimLiveChatJS($vars) {

    $scripts = "";

    $q = @mysql_query("SELECT * FROM tbladdonmodules WHERE module = 'zopimlivechat'");
    while ($arr = mysql_fetch_array($q)) {
        $settings[$arr['setting']] = $arr['value'];
    }

    if (isset($settings['z_user'])) {
        $q2 = @mysql_query("SELECT * FROM mod_zopimlivechat WHERE user = '" . $settings['z_user'] . "' ");
        $arr2 = mysql_fetch_array($q2);
        $code = $arr2['key'];
        $table = "mod_zopimlivechat";
        $fields = "user,salt,key";
        $where = array("user" => $settings['z_user']);
        $result = select_query($table, $fields, $where);
        $data = mysql_fetch_array($result);


        $scripts = "<!--Start of Zopim Live Chat Script-->
<script type=\"text/javascript\">
window.\$zopim||(function(d,s){var z=\$zopim=function(c){z._.push(c)},$=z.s=
d.createElement(s),e=d.getElementsByTagName(s)[0];z.set=function(o){z.set.
_.push(o)};z._=[];z.set._=[];$.async=!0;$.setAttribute('charset','utf-8');
$.src='//cdn.zopim.com/?" . $code . "';z.t=+new Date;$.
type='text/javascript';e.parentNode.insertBefore($,e)})(document,'script');
</script>
<!--End of Zopim Live Chat Script-->";

        if ($_SESSION['uid']) {
            $userid = $_SESSION['uid'];
            $result = mysql_query("SELECT firstname,lastname,email FROM tblclients WHERE id=$userid");
            $data = mysql_fetch_array($result);
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $email = $data["email"];

            $scripts .= "<script type=\"text/javascript\">
  \$zopim(function() {
    \$zopim.livechat.setName('" . $firstname . " " . $lastname . "');
    \$zopim.livechat.setEmail('" . $email . "');
  });
</script>
";
        }
    }



    return $scripts;
}

add_hook('ClientAreaFooterOutput', 1, 'ZopimLiveChatJS');
?>