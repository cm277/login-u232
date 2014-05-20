<?php
/**
 *   https://09source.kicks-ass.net:8443/svn/installer09/
 *   Licence Info: GPL
 *   Copyright (C) 2010 U-232 V2
 *   A bittorrent tracker source based on TBDev.net/tbsource/bytemonsoon.
 *   Project Leaders: Mindless,putyn,kidvision.
 **/
//== Code from Webkreations
//= Alt login by Bigjoos
require_once($_SERVER["DOCUMENT_ROOT"] . "/include/bittorrent.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/include/user_functions.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/cache/timezones.php");
dbconn(false);

  $lang = array_merge( load_language('global'), load_language('signup') , load_language('login'));

  if (get_row_count('users') >= $INSTALLER09['maxusers'])
	stderr($lang['stderr_errorhead'], sprintf($lang['stderr_ulimit'], $INSTALLER09['maxusers']));
  $htmlout = $passhint = $year = $month = $day = '';

  //== shorten timezone 
  function CutName_TZ ($txt, $len=38){
  return (strlen($txt)>$len ? substr($txt,0,$len-1) .'...':$txt);
  }

 //== 09 failed logins
	function left ()
	{
	global $INSTALLER09;
	$total = 0;
	$ip = sqlesc(getip());
	$fail = sql_query("SELECT SUM(attempts) FROM failedlogins WHERE ip={$ip}") or sqlerr(__FILE__, __LINE__);
	list($total) = mysql_fetch_row($fail);
	$left = $INSTALLER09['failedlogins'] - $total;
	if ($left <= 2)
	$left = "<font color='red' size='4'>{$left}</font>";
	else
	$left = "<font color='green' size='4'>{$left}</font>";
	return $left;
	}
	//== End Failed logins
  //==timezone
  $offset = (string)$INSTALLER09['time_offset'];
  $time_select = "<br /><select name='user_timezone'>";
  foreach( $TZ as $off => $words )
  {
  if ( preg_match("/^time_(-?[\d\.]+)$/", $off, $match))
  {
  $time_select .= $match[1] == $offset ? "<option value='{$match[1]}' selected='selected'>".CutName_TZ($words)."</option>\n" : "<option value='{$match[1]}'>".CutName_TZ($words)."</option>\n";
  }
  }
  $time_select .= "</select>";
  //==
   //== Normal Entry Point...
   //== click X by Retro
   $value = array('...','...','...','...','...','...');
   $value[rand(1,count($value)-1)] = 'X';
   $htmlout .="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
	 \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
	 <html xmlns='http://www.w3.org/1999/xhtml'>
	 <head>
   <meta name='generator' content='U-232' />
	 <meta name='MSSmartTagsPreventParsing' content='TRUE' />
	 <title>Login</title>
   <link rel='stylesheet' href='css/start.css' type='text/css' media='screen' />
   <script src='js/jquery.js' type='text/javascript'></script>
   <script src='js/slide.js' type='text/javascript' ></script>
   <script src='js/jquery.simpleCaptcha-0.2.js' type='text/javascript'></script>
   <script src='js/jquery.pstrength-min.1.2.js' type='text/javascript'></script>
   <script src='js/check.js' type='text/javascript'></script>
   <script src='captcha/captcha.js' type='text/javascript'></script>
   </head><body>";
   $htmlout .="
   <script type='text/javascript'>
	 /*<![CDATA[*/
	 $(document).ready(function () {
	 $('#captchalogin').simpleCaptcha();
   });
   /*]]>*/
   </script>
   <!-- Panel -->
   <div id='toppanel'>
   <div id='panel'>
	 <div class='content clearfix'>
   <div class='left'>";
   unset($returnto);
   if (!empty($_GET["returnto"])) {
   $returnto = htmlspecialchars($_GET["returnto"]);
   if (!isset($_GET["nowarn"])) 
   {
   $htmlout .= "<label class='grey'>{$lang['login_not_logged_in']}</label>\n";
   $htmlout .= "<label class='grey'>{$lang['login_error']}</label>";
   }
   }
   $htmlout .="<br /><p><b>{$lang['login_cookies']}</b><br />
   <b>[{$INSTALLER09['failedlogins']}]</b> {$lang['login_failed']}</p>
   <p>{$lang['login_failed_1']} <b>".left()."</b> {$lang['login_failed_2']}</p>
   </div>
   <!-- Login Form -->	
	 <div class='left'>
	 <form class='clearfix' action='../takelogin.php' method='post'>
	 <noscript>{$lang['login_noscript']}</noscript>
	 <h1>{$lang['login_member']}</h1>
	 <label class='grey'><b>{$lang['login_username']}</b></label>
	 <input class='field' type='text' name='username' size='23' />
	 <label class='grey'><b>{$lang['login_password']}</b></label>
	 <input class='field' type='password' name='password' size='23' />		
	 <br />
	 <label class='grey'>&nbsp;<b>{$lang['login_use_ssl']}</b></label><br />
   <label for='ssl'>{$lang['login_ssl1']}<input type='checkbox' name='use_ssl' checked='checked' value='1' id='ssl'/></label><br/>
   <label for='ssl2'>{$lang['login_ssl2']}<input type='checkbox' name='perm_ssl' value='1' id='ssl2'/></label>
	 <label class='grey'>&nbsp;<b>{$lang['login_captcha']}</b></label><br />
	 <div class='field' id='captchalogin'></div><br />";
   for ($i=0; $i < count($value); $i++) {
   $htmlout .="<input name=\"submitme\" type=\"submit\" value=\"".$value[$i]."\" class=\"btn\" />";
   }
   $htmlout .='<div class="clear"></div>
   <label class="grey"><b>'.$lang['login_click'].' <strong>'.$lang['login_x'].'</strong></b></label>
   '.$lang['login_forgot_1'].'</form></div>';
   //==Signup begins
   $htmlout .="
   <script type='text/javascript'>
   /*<![CDATA[*/
   $(function() {
   $('.password').pstrength();
   });
   /*]]>*/
   </script>";
   //== click X by Retro
   $value_s = array('...','...','...','...','...','...');
   $value_s[rand(1,count($value_s)-1)] = 'X';
   $htmlout .='<!-- Register Form -->	
	 <div class="left right">			
	 <form action="../takesignup.php" method="post">
	 <h1>'.$lang['signup_sgnup'].'</h1>				
	 <label class="grey"><b>'.$lang['signup_uname'].'</b></label>	
	 <input class="field" type="text" size="40" name="wantusername" id="wantusername" onblur="checkit();" />
	 <div id="namecheck"></div>
	 <label class="grey"><b>'.$lang['signup_pass'].'</b></label>
	 <label class="grey"><input class="password" type="password" size="40" name="wantpassword" /></label>
	 <label class="grey"><b>'.$lang['signup_passa'].'</b></label>
	 <input class="field" type="password" size="23" name="passagain" />					
	 <label class="grey"><b>'.$lang['signup_email'].'</b></label>
	 <input class="field" type="text" size="23" name="email" />
	 <label>'.$lang['signup_valemail'].'</label><br /><br />';
   $questions = array(
	 array("id"=> "1", "question"=> "{$lang['signup_q1']}"),
	 array("id"=> "2", "question"=> "{$lang['signup_q2']}"),
	 array("id"=> "3", "question"=> "{$lang['signup_q3']}"),
	 array("id"=> "4", "question"=> "{$lang['signup_q4']}"),
	 array("id"=> "5", "question"=> "{$lang['signup_q5']}"),
	 array("id"=> "6", "question"=> "{$lang['signup_q6']}"));
   foreach($questions as $sph) {
   $passhint .= "<option value='" . $sph['id'] . "'>" . $sph['question'] . "</option>\n";
   }
   $htmlout .='<label class="grey"><b>'.$lang['signup_select'].'</b></label><br />
   <select name="passhint">'.$passhint.'</select>
   <br />
   <label class="grey"><b>'.$lang['signup_enter'].'</b></label>
   <input class="field" type="text" size="23"  name="hintanswer" /><br/>
   <font class="small">'.$lang['signup_this_answer'].'<br />'.$lang['signup_this_answer1'].'</font><br /><br />
   <div id="captchaimage">
   <a href="'.$_SERVER['PHP_SELF'].'" onclick="refreshimg(); return false;" title="Click to refresh">
   <img class="cimage" src="captcha/GD_Security_image.php?'.time().'" alt="Oops,missing ATM" />
   </a>
   </div>
	 <label class="grey"><b>'.$lang['captcha_pin'].'</b></label> 
	 <input class="field" type="text" size="23" maxlength="6" name="captcha" id="captcha" onblur="checks(); return false;"/>
   <label class="grey"><b>'.$lang['signup_timez'].'</b></label> 
   <div class="field">'.$time_select.'</div>';
   //==09 Birthday mod
   $year .= "<select name=\"year\">";
   $year .= "<option value=\"0000\">{$lang['signup_year']}</option>";
   $i = "2030";
   while($i >= 1950){
   $year .= "<option value=\"".$i."\">".$i."</option>";
   $i--;
   }
   $year .= "</select>";
   $month .= "<select name=\"month\">
   <option value=\"00\">{$lang['signup_month']}</option>
   <option value=\"01\">{$lang['signup_jan']}</option>
   <option value=\"02\">{$lang['signup_feb']}</option>
   <option value=\"03\">{$lang['signup_mar']}</option>
   <option value=\"04\">{$lang['signup_apr']}</option>
   <option value=\"05\">{$lang['signup_may']}</option>
   <option value=\"06\">{$lang['signup_jun']}</option>
   <option value=\"07\">{$lang['signup_jul']}</option>
   <option value=\"08\">{$lang['signup_aug']}</option>
   <option value=\"09\">{$lang['signup_sep']}</option>
   <option value=\"10\">{$lang['signup_oct']}</option>
   <option value=\"11\">{$lang['signup_nov']}</option>
   <option value=\"12\">{$lang['signup_dec']}</option>
   </select>";
   $day .= "<select name=\"day\">";
   $day .= "<option value=\"00\">{$lang['signup_day']}</option>";
   $i = 1;
   while($i <= 31){
   if($i < 10){
   $day .= "<option value=\"0".$i."\">0".$i."</option>";
   }else{
   $day .= "<option value=\"".$i."\">".$i."</option>";
   }
   $i++;
   }
   $day .= "</select>";
   $htmlout .= " <label class='grey'>{$lang['signup_birth']}<font color=\"red\">*</font></label>
   <div class='field'>". $year . $month . $day ."</div>";
   //==End
   $htmlout.='<br /><input type="checkbox" name="rulesverify" value="yes" /> '.$lang['signup_rules'].'<br />
   <input type="checkbox" name="faqverify" value="yes" /> '.$lang['signup_faq'].'<br />
   <input type="checkbox" name="ageverify" value="yes" /> '.$lang['signup_age'].'<br />';
	 for ($i=0; $i < count($value_s); $i++) {
   $htmlout .="<input name=\"submitme\" type=\"submit\" value=\"".$value_s[$i]."\" class=\"btn\" />";
   }
   $htmlout .='<div class="clear"></div>
   <label class="grey"><b>'.$lang['signup_click'].' <strong>'.$lang['signup_x'].'</strong> '.$lang['signup_click1'].'</b></label>
	 </form>		
	 </div>
	 </div>	
   </div> 
   <!-- The tab on top -->
   <div class="tab">
	 <ul class="login">
	 <li class="left">&nbsp;</li>
	 <li>'.$lang['signup_guest'].'</li>
	 <li class="sep">|</li>
	 <li id="toggle">
	 <a id="open" class="open" href="#">'.$lang['signup_u_panel'].'</a>
	 <a id="close" style="display: none;" class="close" href="#">'.$lang['signup_c_panel'].'</a>			
	 </li>
	 <li class="right">&nbsp;</li>
	 </ul> 
	 </div><!-- / top --></div><!--panel -->';
   if (isset($returnto))
   $htmlout .="<input type=\"hidden\" name=\"returnto\" value=\"" . htmlspecialchars($returnto) . "\" />\n";
   $htmlout .='</body></html>';
echo  $htmlout;
?>