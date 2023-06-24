<?php

include("../config.php");
ini_set("log_errors", true);
ini_set("error_log", $LOG_FILE);
ini_set("date.timezone", $TIMEZONE);
set_time_limit(300);

// Request/Error Handling -----------------------------------------------------

function error_response($code, $msg) {
    http_response_code($code);
    header("Content-Type: application/json");
    $error_json = [
        "success" => false,
        "error" => $msg
    ];
    echo json_encode($error_json);
    die;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Content-Type: application/json");
    header("Allow: POST");
    $error_json = [
        "success" => false,
        "error" => "Bad method"
    ];
    echo json_encode($error_json);
    die;
}

if ($_SERVER["CONTENT_TYPE"] !== "application/json") {
    error_response(400, "Bad content type");
}

$input = json_decode(file_get_contents('php://input'), true);

if ($input === null || !array_key_exists("key", $input) || !array_key_exists("url", $input)) {
    error_response(400, "Bad body format");
}

$key = $input["key"];
$url = $input["url"];

if (!in_array($key, $KEYS)) {
    error_log("Bad key ($key)");
    error_response(401, "Bad key");
}

if ($CHECK_URL_PREFIX && !str_starts_with($url, $URL_PREFIX)) {
    error_log("URL $url doesn't start with prefix $URL_PREFIX");
    error_response(422, "Bad URL");
}

$rate_limits = [];
if (file_exists($RATE_LIMIT_FILE)) {
    $rate_limits = unserialize(file_get_contents($RATE_LIMIT_FILE));
}

$current_period = time() - (time() % $RATE_LIMIT_PERIOD);

if (!isset($rate_limits[$key]["period"]) || $rate_limits[$key]["period"] !== $current_period) {
    $rate_limits[$key]["requests"] = 0;
    $rate_limits[$key]["period"] = $current_period;
}

if ($rate_limits[$key]["requests"] >= $RATE_LIMIT_REQUESTS) {
    error_log("Too many requests for $key");
    error_response(429, "Bad timing");
}

// File Getting ------------------------------------------------------------

$dmp_file = tempnam(sys_get_temp_dir(), "dmp");
$dmp_file_status = file_put_contents($dmp_file, file_get_contents($url));

if (!$dmp_file_status) {
    error_log("Could not download file $url");
    unlink($dmp_file);
    error_response(500, "Could not get file");
}

// File Processing ------------------------------------------------------------

$out_file = tempnam(sys_get_temp_dir(), $dmp_file."_output");
$output = null; // unused
$result_code = null;

$parse_status = 
    exec("\"$DEBUGGER_PATH\" -z $dmp_file -c \"k; !analyze; q\" -logo $out_file", $output, $result_code);

if ($parse_status === false || $result_code !== 0) {
    error_log("Could not analyze $url");
    unlink($dmp_file);
    unlink($out_file);
    error_response(500, "Could not analyze file");
}

unlink($dmp_file);

// Upload File ----------------------------------------------------------------

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $PASTE_ENDPOINT);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$cFile = curl_file_create($out_file);
curl_setopt($curl, CURLOPT_POSTFIELDS, file_get_contents($out_file)); // https://stackoverflow.com/a/871445
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']); 

unlink($out_file);
$curl_res = curl_exec($curl);
curl_close($curl);

// Cleanup --------------------------------------------------------------------

$rate_limits[$key]["requests"]++;
file_put_contents($RATE_LIMIT_FILE, serialize($rate_limits));

header("Content-Type: application/json");
echo json_encode([
    "success" => true,
    "url" => str_replace("\n", "", $curl_res)
], JSON_UNESCAPED_SLASHES);
