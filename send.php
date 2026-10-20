<?php
// Estimate form handler for Hostinger shared hosting (uses PHP mail()).
// FROM_EMAIL must be a mailbox on the site's own domain, or Hostinger will reject the message.
const TO_EMAIL   = 'info@unicornlandscaping.ca';
const FROM_EMAIL = 'info@unicornlandscaping.ca';
const MAX_FILES  = 5;
const MAX_BYTES  = 10 * 1024 * 1024;

header('Content-Type: application/json; charset=utf-8');

function out($code, $ok, $msg) {
  http_response_code($code);
  echo json_encode(['ok' => $ok, 'message' => $msg]);
  exit;
}
function field($k) { return trim(str_replace(["\r", "\0"], '', (string)($_POST[$k] ?? ''))); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, false, 'Method not allowed.');
if (field('website') !== '') out(200, true, 'Thanks.'); // honeypot

$name = field('name'); $email = field('email'); $phone = field('phone'); $addr = field('address');
$kind = field('kind') === 'indoor' ? 'Indoor' : 'Outdoor';
$svc = field('service_label') ?: field('service');
$budget = field('budget') ?: 'Not given'; $time = field('timeline') ?: 'Not given';
$desc = trim((string)($_POST['description'] ?? ''));

if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen(preg_replace('/\D/', '', $phone)) < 10
    || mb_strlen($addr) < 4 || $svc === '' || mb_strlen($desc) < 15 || empty($_POST['consent'])) {
  out(422, false, 'Some required fields are missing or invalid.');
}

// Collect valid image attachments
$attach = [];
if (!empty($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
  $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  foreach ($_FILES['photos']['name'] as $i => $fname) {
    if (count($attach) >= MAX_FILES) break;
    if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
    $tmp = $_FILES['photos']['tmp_name'][$i];
    if (filesize($tmp) > MAX_BYTES) continue;
    $mime = $finfo->file($tmp);
    $isHeic = preg_match('/\.(heic|heif)$/i', $fname);
    if (!in_array($mime, $allowed, true) && !$isHeic) continue;
    $attach[] = ['name' => preg_replace('/[^\w.\- ]/', '_', basename($fname)), 'type' => $isHeic && !in_array($mime, $allowed, true) ? 'image/heic' : $mime, 'data' => file_get_contents($tmp)];
  }
}

$body = implode("\n", [
  'New estimate request', '',
  "Name: $name", "Email: $email", "Phone: $phone", "Location: $addr",
  "Project: $kind · $svc", "Budget: $budget", "Timeline: $time",
  'Photos: ' . ($attach ? implode(', ', array_column($attach, 'name')) : 'None'),
  '', $desc,
]);

$safeName = preg_replace('/[^\p{L}\p{N} .\'-]/u', '', $name);
$subject = '=?UTF-8?B?' . base64_encode("Estimate request: $safeName ($svc)") . '?=';
$headers = [
  'From: Unicorn Landscaping Website <' . FROM_EMAIL . '>',
  'Reply-To: ' . $email,
  'MIME-Version: 1.0',
];

if ($attach) {
  $b = 'b_' . bin2hex(random_bytes(12));
  $headers[] = "Content-Type: multipart/mixed; boundary=\"$b\"";
  $msg = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($body));
  foreach ($attach as $a) {
    $msg .= "--$b\r\nContent-Type: {$a['type']}; name=\"{$a['name']}\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"{$a['name']}\"\r\n\r\n" . chunk_split(base64_encode($a['data']));
  }
  $msg .= "--$b--";
} else {
  $headers[] = 'Content-Type: text/plain; charset=UTF-8';
  $headers[] = 'Content-Transfer-Encoding: base64';
  $msg = chunk_split(base64_encode($body));
}

if (!mail(TO_EMAIL, $subject, $msg, implode("\r\n", $headers), '-f' . FROM_EMAIL)) {
  out(500, false, 'The message could not be sent.');
}
out(200, true, 'Sent.');
