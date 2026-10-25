<?php
require_once __DIR__ . '/../tests/test_full_lifecycle_http.php';

$cj = sys_get_temp_dir() . '/test_cit.txt';
@unlink($cj);

$l1 = makeRequest(BASE_URL . '/citizen/login.php', 'GET', [], $cj);
$csrf = extractCsrf($l1['body']);
$l2 = makeRequest(BASE_URL . '/citizen/login.php', 'POST', [
    'csrf_token' => $csrf,
    'email' => 'test@citizen.local',
    'password' => 'Admin@123'
], $cj);

$s1 = makeRequest(BASE_URL . '/citizen/submit_complaint.php', 'GET', [], $cj);
$csrfSub = extractCsrf($s1['body']);

$gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
$tmp = sys_get_temp_dir() . '/test.gif';
file_put_contents($tmp, $gif);

$sub = makeRequest(BASE_URL . '/citizen/submit_complaint.php', 'POST', [
    'csrf_token'  => $csrfSub,
    'title'       => 'Water Supply Disruption at QA Sector',
    'category_id' => '3',
    'ward_id'     => '1',
    'address'     => 'Sector 4 Main Pipe',
    'description' => 'Severe pipeline burst spilling fresh water onto public thoroughfare.',
    'photos'      => new CURLFile($tmp, 'image/gif', 'test.gif')
], $cj, true);

echo "Submit URL: " . $sub['url'] . " Code: " . $sub['code'] . "\n";
preg_match('/(CFX-\d{4}-\d+)/', $sub['body'], $m);
echo "Matched Code: " . ($m[1] ?? 'NONE') . "\n";
if (empty($m[1])) {
    echo "Body: " . substr(strip_tags($sub['body']), 0, 300) . "\n";
}
