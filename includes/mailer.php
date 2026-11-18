<?php
// E-mail (SMTP or PHP mail()) and SMS (generic HTTP gateway)

function mail_html($name, $title, $body, $link = ''){
    $btn = $link !== '' ? '<p style="margin:26px 0"><a href="'.e(preg_match('~^https?://~', $link) ? $link : base_url().'/'.ltrim($link, '/')).'" style="background:#059669;color:#fff;padding:13px 26px;border-radius:12px;text-decoration:none;font-weight:700;display:inline-block">Open Telesom LearnHub</a></p>' : '';
    return '<div style="background:#f4f9f6;padding:28px 12px;font-family:Segoe UI,Arial,sans-serif"><div style="max-width:560px;margin:auto;background:#fff;border-radius:18px;overflow:hidden;border:1px solid #e3eee8">'
        .'<div style="background:linear-gradient(135deg,#064e3b,#059669);padding:22px 28px;color:#fff;font-size:20px;font-weight:800">Telesom LearnHub</div>'
        .'<div style="padding:28px;color:#0b1f17;line-height:1.6"><p style="margin:0 0 6px">Hello '.e($name).',</p><h2 style="margin:10px 0;font-size:20px">'.e($title).'</h2><p style="color:#5b6f66;margin:0">'.nl2br(e($body)).'</p>'.$btn
        .'<p style="color:#8aa397;font-size:12px;margin:24px 0 0">You receive this because you have a Telesom LearnHub account. You can change reminder settings in your profile.</p></div></div></div>';
}

function send_mail($to, $subject, $html, $text = ''){
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    if ($text === '') $text = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '</p>', '</h2>', '</div>'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
    $b = 'lh'.bin2hex(random_bytes(8));
    $subj = '=?UTF-8?B?'.base64_encode($subject).'?=';
    $headers = 'From: =?UTF-8?B?'.base64_encode(MAIL_FROM_NAME).'?= <'.MAIL_FROM.">\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$b\"";
    $body = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($text))
          ."--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($html))."--$b--\r\n";
    if (SMTP_HOST === '') return (bool)@mail($to, $subj, $body, $headers);
    return smtp_send($to, $subj, $headers, $body);
}

function smtp_send($to, $subj, $headers, $body){
    $fp = @stream_socket_client((SMTP_SECURE === 'ssl' ? 'ssl://' : 'tcp://').SMTP_HOST.':'.SMTP_PORT, $errno, $errstr, 12);
    if (!$fp) { error_log("SMTP connect failed: $errstr"); return false; }
    stream_set_timeout($fp, 12);
    $read = function() use ($fp){ $r = ''; while (($l = fgets($fp, 515)) !== false) { $r .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $r; };
    $cmd = function($c, $ok) use ($fp, $read){ if ($c !== null) fwrite($fp, $c."\r\n"); $r = $read(); return in_array(substr($r, 0, 3), (array)$ok, true); };
    $dom = preg_replace('/[^A-Za-z0-9.\-]/', '', $_SERVER['SERVER_NAME'] ?? 'localhost') ?: 'localhost';
    $fail = function($why) use ($fp){ error_log('SMTP failed at: '.$why); @fclose($fp); return false; };
    if (!$cmd(null, '220')) return $fail('greeting');
    if (!$cmd("EHLO $dom", '250')) return $fail('EHLO');
    if (SMTP_SECURE === 'tls') {
        if (!$cmd('STARTTLS', '220')) return $fail('STARTTLS');
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return $fail('TLS handshake');
        if (!$cmd("EHLO $dom", '250')) return $fail('EHLO after TLS');
    }
    if (SMTP_USER !== '') {
        if (!$cmd('AUTH LOGIN', '334') || !$cmd(base64_encode(SMTP_USER), '334') || !$cmd(base64_encode(SMTP_PASS), '235')) return $fail('authentication');
    }
    if (!$cmd('MAIL FROM:<'.MAIL_FROM.'>', '250')) return $fail('MAIL FROM');
    if (!$cmd('RCPT TO:<'.$to.'>', ['250', '251'])) return $fail('RCPT TO');
    if (!$cmd('DATA', '354')) return $fail('DATA');
    $msg = 'To: <'.$to."> \r\nSubject: ".$subj."\r\nDate: ".date('r')."\r\n".$headers."\r\n\r\n".$body;
    $msg = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $msg)); $msg = str_replace("\n", "\r\n", $msg);
    fwrite($fp, $msg."\r\n.\r\n");
    if (!$cmd(null, '250')) return $fail('message rejected');
    $cmd('QUIT', '221'); @fclose($fp);
    return true;
}

function send_sms($phone, $text){
    if (SMS_API_URL === '' || !function_exists('curl_init')) return false;
    $to = preg_replace('/[^0-9+]/', '', (string)$phone); if (strlen($to) < 7) return false;
    $ch = curl_init(SMS_API_URL);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query([SMS_FIELD_TO => $to, SMS_FIELD_TEXT => mb_substr($text, 0, 300), 'sender' => SMS_SENDER]),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_HTTPHEADER => SMS_API_KEY !== '' ? [SMS_AUTH_HEADER.SMS_API_KEY] : []]);
    $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($res === false) error_log('SMS failed: '.curl_error($ch));
    curl_close($ch);
    return $res !== false && $code >= 200 && $code < 300;
}
