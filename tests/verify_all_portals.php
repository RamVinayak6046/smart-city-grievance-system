<?php
/**
 * Automated Portal Route Verification
 */
define('BASE_URL', 'http://localhost:8000');

function makeReq($url, $method = 'GET', $data = [], $cookieJar = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    $hasFatal = (stripos($res, 'Fatal error') !== false || stripos($res, 'PDOException') !== false || stripos($res, 'Parse error') !== false);
    return [
        'code' => $code,
        'url' => $finalUrl,
        'hasFatal' => $hasFatal,
        'body' => $res
    ];
}

function getCsrf($html) {
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/i', $html, $m)) {
        return $m[1];
    }
    return '';
}

function testLogin($loginPath, $fields, $cookieJar) {
    $get = makeReq(BASE_URL . $loginPath, 'GET', [], $cookieJar);
    $csrf = getCsrf($get['body']);
    $fields['csrf_token'] = $csrf;
    $post = makeReq(BASE_URL . $loginPath, 'POST', $fields, $cookieJar);
    return $post;
}

echo "=================================================================\n";
echo "   CIVICFIX PRO — AUTOMATED PORTAL & ROUTE HEALTH VERIFICATION   \n";
echo "=================================================================\n\n";

$allPassed = true;

// 1. Super Admin Portal
echo "[1] Super Admin Portal\n";
$saJar = tempnam(sys_get_temp_dir(), 'sa_jar_');
$saLogin = testLogin('/auth/staff_login.php', [
    'email' => 'superadmin@civicfix.local',
    'password' => 'SuperAdmin@2026',
    'portal_type' => 'super_admin'
], $saJar);
echo "  Login: HTTP {$saLogin['code']} -> {$saLogin['url']}\n";

$saRoutes = [
    '/superadmin/dashboard.php',
    '/superadmin/departments.php',
    '/superadmin/categories.php',
    '/superadmin/wards.php',
    '/superadmin/manage_citizens.php',
    '/superadmin/manage_officials.php',
    '/superadmin/manage_admins.php',
    '/superadmin/complaints.php',
    '/superadmin/audit_logs.php',
    '/superadmin/reports.php',
    '/superadmin/settings.php'
];

foreach ($saRoutes as $r) {
    $res = makeReq(BASE_URL . $r, 'GET', [], $saJar);
    $ok = ($res['code'] === 200 && !$res['hasFatal']);
    if (!$ok) $allPassed = false;
    $tag = $ok ? 'PASS' : 'FAIL';
    echo "  $r: [$tag] HTTP {$res['code']} (fatal=" . ($res['hasFatal'] ? 'YES' : 'NO') . ")\n";
    if ($res['hasFatal']) {
        echo "    " . substr(strip_tags($res['body']), 0, 150) . "\n";
    }
}

// 2. Department Admin Portal
echo "\n[2] Department Admin Portal\n";
$deptJar = tempnam(sys_get_temp_dir(), 'dept_jar_');
$deptLogin = testLogin('/auth/staff_login.php', [
    'email' => 'admin.sanitation@civicfix.local',
    'password' => 'Admin@2026',
    'portal_type' => 'department_admin'
], $deptJar);
echo "  Login: HTTP {$deptLogin['code']} -> {$deptLogin['url']}\n";

$deptRoutes = [
    '/department/dashboard.php',
    '/department/complaints.php',
    '/department/unassigned.php',
    '/department/assignments.php',
    '/department/verify_completion.php',
    '/department/officials.php',
    '/department/reports.php'
];

foreach ($deptRoutes as $r) {
    $res = makeReq(BASE_URL . $r, 'GET', [], $deptJar);
    $ok = ($res['code'] === 200 && !$res['hasFatal']);
    if (!$ok) $allPassed = false;
    $tag = $ok ? 'PASS' : 'FAIL';
    echo "  $r: [$tag] HTTP {$res['code']} (fatal=" . ($res['hasFatal'] ? 'YES' : 'NO') . ")\n";
    if ($res['hasFatal']) {
        echo "    " . substr(strip_tags($res['body']), 0, 150) . "\n";
    }
}

// 3. Work Official Portal
echo "\n[3] Work Official Portal\n";
$offJar = tempnam(sys_get_temp_dir(), 'off_jar_');
$offLogin = testLogin('/auth/staff_login.php', [
    'email' => 'official.sanitation1@civicfix.local',
    'password' => 'Official@2026',
    'portal_type' => 'work_official'
], $offJar);
echo "  Login: HTTP {$offLogin['code']} -> {$offLogin['url']}\n";

$offRoutes = [
    '/official/dashboard.php',
    '/official/assignments.php',
    '/official/profile.php'
];

foreach ($offRoutes as $r) {
    $res = makeReq(BASE_URL . $r, 'GET', [], $offJar);
    $ok = ($res['code'] === 200 && !$res['hasFatal']);
    if (!$ok) $allPassed = false;
    $tag = $ok ? 'PASS' : 'FAIL';
    echo "  $r: [$tag] HTTP {$res['code']} (fatal=" . ($res['hasFatal'] ? 'YES' : 'NO') . ")\n";
    if ($res['hasFatal']) {
        echo "    " . substr(strip_tags($res['body']), 0, 150) . "\n";
    }
}

// 4. Citizen Portal
echo "\n[4] Citizen Portal\n";
$citJar = tempnam(sys_get_temp_dir(), 'cit_jar_');
$citLogin = testLogin('/auth/citizen_login.php', [
    'email' => 'aarav.sharma@example.com',
    'password' => 'Citizen@2026'
], $citJar);
echo "  Login: HTTP {$citLogin['code']} -> {$citLogin['url']}\n";

$citRoutes = [
    '/citizen/dashboard.php',
    '/citizen/my_complaints.php',
    '/citizen/submit_complaint.php',
    '/citizen/profile.php'
];

foreach ($citRoutes as $r) {
    $res = makeReq(BASE_URL . $r, 'GET', [], $citJar);
    $ok = ($res['code'] === 200 && !$res['hasFatal']);
    if (!$ok) $allPassed = false;
    $tag = $ok ? 'PASS' : 'FAIL';
    echo "  $r: [$tag] HTTP {$res['code']} (fatal=" . ($res['hasFatal'] ? 'YES' : 'NO') . ")\n";
    if ($res['hasFatal']) {
        echo "    " . substr(strip_tags($res['body']), 0, 150) . "\n";
    }
}

// 5. Public Landing Page & Role Selection
echo "\n[5] Landing & Role Cards\n";
$landing = makeReq(BASE_URL . '/', 'GET');
$landingOk = ($landing['code'] === 200 && strpos($landing['body'], 'Continue as Super Admin') !== false);
if (!$landingOk) $allPassed = false;
echo "  Landing Page: [" . ($landingOk ? 'PASS' : 'FAIL') . "] HTTP {$landing['code']}\n";

echo "\n=================================================================\n";
echo "OVERALL VERIFICATION RESULT: " . ($allPassed ? "100% HEALTHY - ALL 25+ ROUTES RETURNING HTTP 200" : "FAILURES DETECTED") . "\n";
echo "=================================================================\n";
