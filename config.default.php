<?php

$KEYS = [
    "hi",
    "there"
];

$RATE_LIMIT_REQUESTS = 5;
$RATE_LIMIT_PERIOD = 300;
$RATE_LIMIT_FILE = "../dump-api-rate-limit.db";

// relative to the public folder. So you would probably want to use ..
$LOG_FILE = "../dump-api.log";
// https://www.php.net/manual/en/timezones.php
$TIMEZONE = "Etc/UTC";

$CHECK_URL_PREFIX = true;
$URL_PREFIX = "https://cdn.discordapp.com/attachments/";
$PASTE_ENDPOINT = "https://paste.rtech.support/upload";

$DEBUGGER_PATH = "C:\\Program Files (x86)\\Windows Kits\\10\\Debuggers\\x64\\cdb.exe";
