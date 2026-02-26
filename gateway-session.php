<?php
/**
 * Gateway Session Creator
 * Creates a valid OpenCATS session for gateway-authenticated users
 * Bypasses password validation - only call from trusted gateway
 */

// Only allow requests from the gateway (check for special header)
$gatewaySecret = getenv('GATEWAY_SESSION_SECRET') ?: 'wigoh-gateway-secret';
$providedSecret = $_SERVER['HTTP_X_GATEWAY_SECRET'] ?? '';

if ($providedSecret !== $gatewaySecret) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Get user data from request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['user_id']) || !isset($input['site_id'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required fields: user_id, site_id']);
    exit;
}

// Include OpenCATS configuration and dependencies
define('LEGACY_ROOT', '.');
include_once('./config.php');

// Define constants if not defined
if (!defined('CATS_SESSION_NAME')) {
    define('CATS_SESSION_NAME', 'CATS');
}

include_once('./constants.php');
include_once('./lib/DatabaseConnection.php');

// Start session with CATS name
@session_name(CATS_SESSION_NAME);
@session_start();

// Include Session after session starts
include_once('./lib/Session.php');

$db = DatabaseConnection::getInstance();

// Get user and site data from database
$userId = (int)$input['user_id'];
$siteId = (int)$input['site_id'];

$sql = sprintf(
    "SELECT
        user.user_id AS userID,
        user.user_name AS username,
        user.password AS password,
        user.first_name AS firstName,
        user.last_name AS lastName,
        user.access_level AS accessLevel,
        user.site_id AS userSiteID,
        user.is_demo AS isDemoUser,
        user.email AS email,
        user.categories AS categories,
        user.pipeline_entries_per_page AS pipelineEntriesPerPage,
        user.column_preferences as columnPreferences,
        user.can_see_eeo_info as canSeeEEOInfo,
        site.name AS siteName,
        site.unix_name AS unixName,
        site.user_licenses AS userLicenses,
        site.company_id AS companyID,
        site.is_demo AS isDemo,
        site.account_active AS accountActive,
        site.account_deleted AS accountDeleted,
        site.time_zone AS timeZone,
        site.date_format_ddmmyy AS dateFormatDMY,
        site.is_free AS isFree,
        site.is_hr_mode AS isHrMode,
        site.first_time_setup as isFirstTimeSetup,
        site.localization_configured as isLocalizationConfigured,
        site.agreed_to_license as isAgreedToLicense
    FROM
        user
    LEFT JOIN site
        ON site.site_id = user.site_id
    WHERE
        user.user_id = %d AND user.site_id = %d",
    $userId,
    $siteId
);

$rs = $db->getAssoc($sql);

if (!$rs || $db->isEOF()) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Create a new CATSSession and set values using reflection (to access private properties)
$session = new CATSSession();
$reflection = new ReflectionClass($session);

$properties = [
    '_username' => $rs['username'] ?? '',
    '_password' => $rs['password'] ?? '',
    '_userID' => (int)$rs['userID'],
    '_siteID' => (int)$rs['userSiteID'],
    '_firstName' => $rs['firstName'] ?? '',
    '_lastName' => $rs['lastName'] ?? '',
    '_siteName' => $rs['siteName'] ?? '',
    '_unixName' => $rs['unixName'] ?? '',
    '_userLicenses' => (int)($rs['userLicenses'] ?? 0),
    '_accessLevel' => (int)$rs['accessLevel'],
    '_realAccessLevel' => (int)$rs['accessLevel'],
    '_categories' => $rs['categories'] ? explode(',', $rs['categories']) : [],
    '_isASP' => ($rs['companyID'] != 0),
    '_isHrMode' => ($rs['isHrMode'] != 0),
    '_siteCompanyID' => ($rs['companyID'] != 0 ? (int)$rs['companyID'] : -1),
    '_isFree' => ($rs['isFree'] != 0),
    '_isFirstTimeSetup' => ($rs['isFirstTimeSetup'] != 0),
    '_isLocalizationConfigured' => ($rs['isLocalizationConfigured'] != 0),
    '_isAgreedToLicense' => ($rs['isAgreedToLicense'] != 0),
    '_accountActive' => ($rs['accountActive'] != 0),
    '_accountDeleted' => ($rs['accountDeleted'] != 0),
    '_email' => $rs['email'] ?? '',
    '_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    '_userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Gateway',
    '_timeZoneOffset' => (int)($rs['timeZone'] ?? 0),
    '_timeZone' => (int)($rs['timeZone'] ?? 0),
    '_dateDMY' => ($rs['dateFormatDMY'] != 0),
    '_canSeeEEOInfo' => ($rs['canSeeEEOInfo'] != 0) || ((int)$rs['accessLevel'] >= 400),
    '_pipelineEntriesPerPage' => (int)($rs['pipelineEntriesPerPage'] ?? 15),
    '_isLoggedIn' => true,
    '_isDemo' => false,
    '_userLoginID' => 0,
];

foreach ($properties as $name => $value) {
    try {
        $prop = $reflection->getProperty($name);
        $prop->setAccessible(true);
        $prop->setValue($session, $value);
    } catch (Exception $e) {
        // Property doesn't exist, skip
    }
}

// Store in session
$_SESSION['CATS'] = $session;

// Return session ID
header('Content-Type: application/json');
echo json_encode([
    'session_id' => session_id(),
    'success' => true,
    'user_id' => $userId,
    'site_id' => $siteId
]);
