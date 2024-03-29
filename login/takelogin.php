<?php
/**
 *   https://github.com/Bigjoos/
 *   Licence Info: GPL
 *   Copyright (C) 2010 U-232 v.3
 *   A bittorrent tracker source based on TBDev.net/tbsource/bytemonsoon.
 *   Project Leaders: Mindless, putyn.
 *
 */
require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'bittorrent.php');
require_once (INCL_DIR.'user_functions.php');
require_once (INCL_DIR.'password_functions.php');
require_once (CLASS_DIR.'page_verify.php');
require_once (CLASS_DIR.'class_browser.php');
dbconn();
global $CURUSER;
if (!$CURUSER) {
    get_template();
}
session_start();
$newpage = new page_verify();
//$newpage->check('takelogin');
$lang = array_merge(load_language('global') , load_language('takelogin'));
// 09 failed logins thanks to pdq - Retro
function failedloginscheck()
{
    global $INSTALLER09;
    $total = 0;
    $ip = sqlesc(getip());
    $res = sql_query("SELECT SUM(attempts) FROM failedlogins WHERE ip=$ip") or sqlerr(__FILE__, __LINE__);
    list($total) = mysqli_fetch_row($res);
    if ($total >= $INSTALLER09['failedlogins']) {
        sql_query("UPDATE failedlogins SET banned = 'yes' WHERE ip=$ip") or sqlerr(__FILE__, __LINE__);
        stderr("Login Locked!", "You have been <b>Exceeded</b> the allowed maximum login attempts without successful login, therefore your ip address <b>(".htmlsafechars($ip).")</b> has been locked for 24 hours.");
    }
} // End
if (!mkglobal('username:password'.($INSTALLER09['captcha_on'] ? ":captchaSelection:" : ":").'submitme')) die('Something went wrong');
if ($submitme != 'X') stderr('Ha Ha', 'You Missed, You plonker !');
if ($INSTALLER09['captcha_on']) {
    if(empty($captchaSelection) || $_SESSION['simpleCaptchaAnswer'] != $captchaSelection){
        header('Location: login/index.php');
        exit();
    }
}
function bark($text = 'Username or password incorrect')
{
    global $lang, $INSTALLER09, $mc1;
    $sha = sha1($_SERVER['REMOTE_ADDR']);
    $dict_key = 'dictbreaker:::'.$sha;
    $flood = $mc1->get_value($dict_key);
    if ($flood === false) $mc1->cache_value($dict_key, 'flood_check', 20);
    else die('Minimum 8 seconds between login attempts :)');
    stderr($lang['tlogin_failed'], $text);
}
failedloginscheck();
$res = sql_query("SELECT id, ip, passhash, perms, ssluse, secret, enabled FROM users WHERE username = ".sqlesc($username)." AND status = 'confirmed'");
$row = mysqli_fetch_assoc($res);
$ip_escaped = sqlesc(getip());
$ip = getip();
$added = TIME_NOW;
if (!$row) {
    $fail = (@mysqli_fetch_row(sql_query("SELECT COUNT(id) from failedlogins where ip=$ip_escaped"))) or sqlerr(__FILE__, __LINE__);
    if ($fail[0] == 0) sql_query("INSERT INTO failedlogins (ip, added, attempts) VALUES ($ip_escaped, $added, 1)") or sqlerr(__FILE__, __LINE__);
    else sql_query("UPDATE failedlogins SET attempts = attempts + 1 where ip=$ip_escaped") or sqlerr(__FILE__, __LINE__);
    bark();
}
if ($row['passhash'] != make_passhash($row['secret'], md5($password))) {
    $fail = (@mysqli_fetch_row(sql_query("SELECT COUNT(id) from failedlogins where ip=$ip_escaped"))) or sqlerr(__FILE__, __LINE__);
    if ($fail[0] == 0) sql_query("INSERT INTO failedlogins (ip, added, attempts) VALUES ($ip_escaped, $added, 1)") or sqlerr(__FILE__, __LINE__);
    else sql_query("UPDATE failedlogins SET attempts = attempts + 1 where ip=$ip_escaped") or sqlerr(__FILE__, __LINE__);
    $to = ((int)$row["id"]);
    $subject = "Failed login";
    $msg = "[color=red]Security alert[/color]\n Account: ID=".(int)$row['id']." Somebody (probably you, ".htmlsafechars($username)." !) tried to login but failed!"."\nTheir [b]Ip Address [/b] was : ".htmlsafechars($ip)."\n If this wasn't you please report this event to a {$INSTALLER09['site_name']} staff member\n - Thank you.\n";
    $sql = "INSERT INTO messages (sender, receiver, msg, subject, added) VALUES('System', '$to', ".sqlesc($msg).", ".sqlesc($subject).", $added);";
    $res = sql_query($sql) or sqlerr(__FILE__, __LINE__);
    $mc1->delete_value('inbox_new_'.$row['id']);
    $mc1->delete_value('inbox_new_sb_'.$row['id']);
    bark("<b>Error</b>: Username or password entry incorrect <br />Have you forgotten your password? <a href='{$INSTALLER09['baseurl']}/resetpw.php'><b>Recover</b></a> your password !");
}
if ($row['enabled'] == 'no') bark($lang['tlogin_disabled']);
sql_query("DELETE FROM failedlogins WHERE ip = $ip_escaped");
$userid = (int)$row["id"];
$row['perms'] = (int)$row['perms'];
//== Start ip logger - Melvinmeow, Mindless, pdq
$no_log_ip = ($row['perms'] & bt_options::PERMS_NO_IP);
if ($no_log_ip) {
    $ip = '127.0.0.1';
    $ip_escaped = sqlesc($ip);
}
if (!$no_log_ip) {
    $res = sql_query("SELECT * FROM ips WHERE ip=$ip_escaped AND userid =".sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
    if (mysqli_num_rows($res) == 0) {
        sql_query("INSERT INTO ips (userid, ip, lastlogin, type) VALUES (".sqlesc($userid).", $ip_escaped , $added, 'Login')") or sqlerr(__FILE__, __LINE__);
        $mc1->delete_value('ip_history_'.$userid);
    } else {
        sql_query("UPDATE ips SET lastlogin=$added WHERE ip=$ip_escaped AND userid=".sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
        $mc1->delete_value('ip_history_'.$userid);
    }
} // End Ip logger
$passh = md5($row["passhash"].$_SERVER["REMOTE_ADDR"]);
logincookie($row["id"], $passh);
if (isset($_POST['use_ssl']) && $_POST['use_ssl'] == 1 && !isset($_SERVER['HTTPS'])) $INSTALLER09['baseurl'] = str_replace('http', 'https', $INSTALLER09['baseurl']);
$ssl_value = (isset($_POST['perm_ssl']) && $_POST['perm_ssl'] == 1 ? 'ssluse = 2' : 'ssluse = 1');
$ssluse = ($row['ssluse'] == 2 ? 2 : 1);
// output browser
$ua = getBrowser();
$browser = "Browser: ".$ua['name']." ".$ua['version'].". Os: ".$ua['platform'].". Agent : ".$ua['userAgent'];
sql_query('UPDATE users SET browser='.sqlesc($browser).', '.$ssl_value.', ip = '.$ip_escaped.', last_access='.TIME_NOW.', last_login='.TIME_NOW.' WHERE id='.sqlesc($row['id'])) or sqlerr(__FILE__, __LINE__);
$mc1->begin_transaction('MyUser_'.$row['id']);
$mc1->update_row(false, array(
    'browser' => $browser,
    'ip' => $ip,
    'ssluse' => $ssluse,
    'last_access' => TIME_NOW,
    'last_login' => TIME_NOW
));
$mc1->commit_transaction($INSTALLER09['expires']['curuser']);
$mc1->begin_transaction('user'.$row['id']);
$mc1->update_row(false, array(
    'browser' => $browser,
    'ip' => $ip,
    'ssluse' => $ssluse,
    'last_access' => TIME_NOW,
    'last_login' => TIME_NOW
));
$mc1->commit_transaction($INSTALLER09['expires']['user_cache']);
header("Location: {$INSTALLER09['baseurl']}/index.php");
?>
