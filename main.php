<?php
function mainLoop($headers) {
    $claim_count = 0;
    try {
        $user_response = file_get_contents("https://api-clicker.pixelverse.xyz/api/users", false, stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => $headers
            ]
        ]));
        
        if ($http_response_header[0] == "HTTP/1.1 200 OK") {
            $user_data = json_decode($user_response, true);
            $telegram_user = isset($user_data["telegramUserId"]) ? $user_data["telegramUserId"] : null;
            $claim_count = isset($user_data["clicksCount"]) ? $user_data["clicksCount"] : 0;
            if ($telegram_user) {
                echo "Login successful!\n";
            } else {
                echo "Login successful! But Telegram User ID not found.\n";
            }
        } else {
            echo "Login failed. Status code: " . $http_response_header[0] . "\n";
            return;
        }

        while (true) {
            for ($remaining = 25; $remaining > 0; $remaining--) {
                echo "Next claim in $remaining seconds\r";
                sleep(1);
            }
            
            $claim_response = file_get_contents("https://api-clicker.pixelverse.xyz/api/mining/claim", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => $headers
                ]
            ]));
            
            if ($http_response_header[0] == "HTTP/1.1 201 Created") {
                $claim_data = json_decode($claim_response, true);
                $claimed_amount = isset($claim_data["claimedAmount"]) ? $claim_data["claimedAmount"] : 0;
                $claim_count += $claimed_amount;
                echo "Claimed Amount: " . $claimed_amount . " ,Total Earned: " . $claim_count . "\n";
            } else {
                echo "Claim failed\n";
            }
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Input
$initdata = readline("Initdata: ");
$tg_id = readline("Tg-Id: ");

// Headers
$headers = [
    'Accept: application/json, text/plain, */*',
    'Cache-Control: no-cache',
    'Initdata: ' . $initdata,
    'Origin: https://sexyzbot.pxlvrs.io',
    'Pragma: no-cache',
    'Referer: https://sexyzbot.pxlvrs.io/',
    'Sec-Ch-Ua: "Google Chrome";v="125", "Chromium";v="125", "Not.A/Brand";v="24"',
    'Sec-Fetch-Site: cross-site',
    'Tg-Id: ' . $tg_id,
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'
];

mainLoop($headers);
?>