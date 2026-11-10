<?php
require_once '../helpers/response.php';

sendResponse(true, "Backend Kantin Sekolah Siap Digunakan", [
    "timestamp" => date("Y-m-d H:i:s")
]);