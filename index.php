<?php

function get_value($name) {
  return isset($_POST[$name]) ? $_POST[$name] : null;
}

// Check for vietnam network only
function validate_phone(&$phone) {
  $phone   = str_replace(' ', '', $phone);
  $pattern = '~^
    ((?<country_code>\+84)|0) # Country code, support +84 or 0
    (?<operator>
      9[0-9]    | # Start with 09x
      12[0-9]   | # Start with 012x: Mobifone, Vinaphone
      16[2-9]   | # Start with 016x: Viettel
      186 | 188 | # Vietnam mobile
      199       | # Beeline
    ) # Check for mobile telecom providers, see http://vi.wikipedia.org/wiki/M%C3%A3_%C4%91i%E1%BB%87n_tho%E1%BA%A1i_Vi%E1%BB%87t_Nam
    (?<number>
      [0-9]{7} # 7 digits
    ) # Phone number
  $~x';

  return preg_match($pattern, $phone);
}

$spool_path  = '/var/spool/asterisk/outgoing/';
$device_id   = 'dongle0';
$is_writable = is_writable($spool_path);

$name       = '';
$phone      = '';
$song       = '';
$submitted  = get_value('submitted') | 0;
$messages   = array();
$songs      = array(
  'baptiste'           => 'Baptiste\'s voice',
  'alone'              => 'Alone (Celine Dion)',
  'i-am-your-angel'    => 'I\'m your angel (Celine Dion)',
  'gangnam-style'      => 'Gangnam Style (PSY)',
  'misty-mountain'     => 'Misty Moutain (Richard Armitage and the dwarf cast)',
  'rieng-mot-goc-troi' => 'Riêng một góc trời (Tuấn Ngọc)',
);

if($is_writable && $submitted) {
  $name      = get_value('name');
  $phone     = get_value('phone');
  $song      = get_value('song');
  $validated = true;

  if(empty($name)) {
    $messages['error'][] = 'Please tell me your name :-)';
    $validated = false;
  }

  if(empty($phone)) {
    if(empty($name)) {
      $messages['error'][] = '... and your phone number.';
    }
    else {
      $messages['error'][] = 'Please tell me your phone number :-)';
    }

    $validated = false;
  }
  else if (!validate_phone($phone)) {
    if(empty($name)) {
      $messages['error'][] = '... and a valid phone number.';
    }
    else {
      $messages['error'][] = 'C\'mon, give me a valid number :\'(';
    }

    $validated = false;
  }

  if(empty($song)) {
    if(empty($messages['error'])) {
      $messages['error'][] = '... and your song.';
    }
    else {
      $messages['error'][] = 'Please choose your song :-)';
    }

    $validated = false;
  }
  else if(!isset($songs[$song])) {
    $messages['error'][] = 'Sorry, I don\'t know this song :\'(';
    $validated = false;
  }

  if($validated) {
    $file = time() . '_' . $name . '_' . $phone . '_' . $song . '_' . rand(0, 1000);
    $file = $spool_path . '/' . md5($file) . '.call';

    if($fp = @fopen($file, 'wb')) {
      fwrite($fp, "Channel: Dongle/$device_id/$phone\n");
      fwrite($fp, "Application: Playback\n");
      fwrite($fp, "Data: $song");
      fclose($fp);

      $messages['message'][] = "Thank you $name for using our IVR Demo. You will receive a call shortly.";
    }
    else {
      $messages['error'][] = 'Server cannot make outgoing call, Please contact site administrator.';
      $is_writable = false;
    }
  }
}

if(!$is_writable) {
  $messages['error'][] = 'Server cannot make outgoing call, Please contact site administrator.';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN"
  "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
      xmlns:fb="http://ogp.me/ns/fb#"
      xml:lang="en"
      lang="en"
      version="XHTML+RDFa 1.0"
      dir="ltr"
      xmlns:content="http://purl.org/rss/1.0/modules/content/"
      xmlns:dc="http://purl.org/dc/terms/"
      xmlns:foaf="http://xmlns.com/foaf/0.1/"
      xmlns:og="http://ogp.me/ns#"
      xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
      xmlns:sioc="http://rdfs.org/sioc/ns#"
      xmlns:sioct="http://rdfs.org/sioc/types#"
      xmlns:skos="http://www.w3.org/2004/02/skos/core#"
      xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
>

<head profile="http://www.w3.org/1999/xhtml/vocab">
  <meta http-equiv="Content-Language" content="en">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" href="/images/favicon.png" type="image/png" />
  <title>IVR Demonstration | Ozerside</title>
  <link rel="stylesheet" type="text/css" href="/css/style.css" />
  <script type="text/javascript" src="/js/jquery.min.js"></script>
</html>
<body>
  <div id="page">
    <div id="logo"></div>
    <h1 id="title"><span>IVR Demo</span></h1>

    <form id="ivr" action="index.php" method="POST">
      <table id="form" border="0" cellpadding="0" cellspacing="0">
        <?php if(!empty($messages)) : ?>
        <tr>
          <td colspan="2">
          <?php foreach($messages as $type => $msgs) : ?>
          <div class="message <?php echo $type ?>">
            <ul>
              <?php foreach($msgs as $message) : ?>
              <li><?php echo $message ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endforeach; ?>
          </td>
        </tr>
        <tr>
          <td><label for="name">Name</label></td>
          <td><input type="text" id="name" name="name" size="60" value="<?php echo $name ?>" /></td>
        </tr>
        <tr>
          <td><label for="phone">Phone number</label></td>
          <td><input type="text" id="phone" name="phone" size="60" value="<?php echo $phone ?>" /></td>
        </tr>
        <tr>
          <td><label for="song">Song</label></td>
          <td>
            <select name="song" id="song">
              <?php foreach($songs as $id => $name) : ?>
                <option value="<?php echo $id ?>"<?php echo $song == $id ? ' selected="selected"' : '' ?>><?php echo $name ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <td colspan="2" valign="center">
            <div class="button<?php echo $is_writable ? '' : ' disabled' ?>" id="submit" onclick="document.forms['ivr'].submit();">
              <span>Call me</span>
            </div>
            <input type="hidden" name="submitted" value="1" />
            <input type="submit" style="visibility: hidden;"  />
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <p class="toc">
              * Your name, phone and any other information you provide aren't
              stored in our database. We ask you this information only so we can
              make a test call to you. We do not sell or share your information
              with anyone else.
            </p>
          </td>
        </tr>
      </table>
    </form>
  </div>
  <?php if(!$is_writable) : ?>
  <script type="text/javascript">
    function submit() {
      return false;
    };

    document.forms['ivr'].onsubmit = submit;
    document.forms['ivr'].submit = submit;
  </script>
  <?php endif; ?>
</body>