<?php
require_once "tests/test_full_lifecycle_http.php";
$cj = sys_get_temp_dir() . "/qa_sub_debug.txt";
@unlink($cj);

$em = "debug_cit_" . time() . "@example.com";
$pw = "SecurePass@123";

$r1 = makeRequest(BASE_URL . "/auth/citizen_register.php", "GET", [], $cj);
$c1 = extractCsrf($r1["body"]);
$r2 = makeRequest(BASE_URL . "/auth/citizen_register.php", "POST", [
    "csrf_token" => $c1,
    "name" => "Debug Cit",
    "email" => $em,
    "phone" => "9876543210",
    "ward_id" => "1",
    "password" => $pw,
    "confirm" => $pw
], $cj);

$l1 = makeRequest(BASE_URL . "/citizen/login.php", "GET", [], $cj);
$c2 = extractCsrf($l1["body"]);
$l2 = makeRequest(BASE_URL . "/citizen/login.php", "POST", [
    "csrf_token" => $c2,
    "email" => $em,
    "password" => $pw
], $cj);

echo "Login URL: " . $l2["url"] . " Code: " . $l2["code"] . "\n";

$s1 = makeRequest(BASE_URL . "/citizen/submit_complaint.php", "GET", [], $cj);
$c3 = extractCsrf($s1["body"]);

$gif = base64_decode("R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7");
$tmp = sys_get_temp_dir() . "/sub_debug.gif";
file_put_contents($tmp, $gif);

$s2 = makeRequest(BASE_URL . "/citizen/submit_complaint.php", "POST", [
    "csrf_token" => $c3,
    "title" => "Debug Water Pipe Leakage",
    "category_id" => "3",
    "ward_id" => "1",
    "address" => "123 Debug St",
    "description" => "A major pipeline fracture is spilling thousands of liters of clean water into the sewer system.",
    "photos" => new CURLFile($tmp, "image/gif", "evidence.gif")
], $cj, true);

echo "Submit URL: " . $s2["url"] . " Code: " . $s2["code"] . "\n";
preg_match("/(CFX-\d{4}-\d+)/", $s2["body"], $m);
echo "Matched: " . ($m[1] ?? "NONE") . "\n";
if (empty($m[1])) {
    echo "Body: " . substr(strip_tags($s2["body"]), 0, 400) . "\n";
}