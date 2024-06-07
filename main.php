<?php
function fetch_pet_info($headers) {
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api-clicker.pixelverse.xyz/api/pets");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $pet_response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status_code == 200) {
            $pet_data = json_decode($pet_response, true);
            $pets = isset($pet_data['data']) ? $pet_data['data'] : [];
            echo "Pet Information:\n";
            foreach ($pets as $pet) {
                $name = isset($pet['name']) ? $pet['name'] : null;
                $user_pet = isset($pet['userPet']) ? $pet['userPet'] : [];
                $level = isset($user_pet['level']) ? $user_pet['level'] : null;
                $stats = isset($user_pet['stats']) ? $user_pet['stats'] : [];

                // Initialize default values
                $max_energy = $power = $recharge_speed = null;

                // Extract specific stats
                foreach ($stats as $stat) {
                    $stat_name = isset($stat['petsStat']['name']) ? $stat['petsStat']['name'] : null;
                    $current_value = isset($stat['currentValue']) ? $stat['currentValue'] : null;
                    if ($stat_name == 'Max energy') {
                        $max_energy = $current_value;
                    } elseif ($stat_name == 'Damage') {
                        $power = $current_value;
                    } elseif ($stat_name == 'Energy restoration') {
                        $recharge_speed = $current_value;
                    }
                }

                echo "Name: $name, Level: $level, Max energy: $max_energy, Power: $power, Recharge speed: $recharge_speed\n";
            }
            return $pets;  // Return the pet data for further processing
        } else {
            echo "Failed to fetch pet information. Status code: $status_code\n";
        }
    } catch (Exception $e) {
        echo "Error fetching pet information: " . $e->getMessage() . "\n";
    }
    return [];
}

function upgrade_pet($headers, $pet) {
    try {
        $user_pet = isset($pet['userPet']) ? $pet['userPet'] : [];
        $pet_id = isset($user_pet['id']) ? $user_pet['id'] : null;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api-clicker.pixelverse.xyz/api/pets/user-pets/$pet_id/level-up");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $upgrade_response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status_code == 201) {
            echo "Pet with ID $pet_id upgraded successfully.\n";
            // Fetch and display the updated pet information
            fetch_pet_info($headers);
        } else {
            echo "Failed to upgrade pet with ID $pet_id. Status code: $status_code\n";
        }
    } catch (Exception $e) {
        echo "Error upgrading pet with ID $pet_id: " . $e->getMessage() . "\n";
    }
}

function mainLoop($headers, $auto_upgrade) {
    $claim_count = 0;
    try {
        // Login and get user information
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api-clicker.pixelverse.xyz/api/users");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $user_response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status_code == 200) {
            $user_data = json_decode($user_response, true);
            $telegram_user = isset($user_data["telegramUserId"]) ? $user_data["telegramUserId"] : null;
            $claim_count = isset($user_data["clicksCount"]) ? $user_data["clicksCount"] : 0;
            if ($telegram_user) {
                echo "Login successful!\n";
            } else {
                echo "Login successful! But Telegram User ID not found.\n";
            }
        } else {
            echo "Login failed. Status code: $status_code\n";
            return;
        }

        // Fetch and display pet information after login
        $pets = fetch_pet_info($headers);
        $num_claims = 0;

        while (true) {
            for ($remaining = 300; $remaining > 0; $remaining--) {  // 5 minutes delay
                echo "Next claim in $remaining seconds\r";
                sleep(1);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api-clicker.pixelverse.xyz/api/mining/claim");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $claim_response = curl_exec($ch);
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status_code == 201) {
                $claim_data = json_decode($claim_response, true);
                $claimed_amount = isset($claim_data["claimedAmount"]) ? $claim_data["claimedAmount"] : 0;
                $claim_count += $claimed_amount;
                $num_claims++;
                echo "Claimed Amount: $claimed_amount ,Total Earned: $claim_count\n";

                if ($auto_upgrade && $num_claims % 10 == 0) {
                    echo "Auto-upgrading pets...\n";
                    foreach ($pets as $pet) {
                        upgrade_pet($headers, $pet);
                    }

                    // Re-fetch pet information after upgrades
                    $pets = fetch_pet_info($headers);
                }
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
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'
];

// Ask the user if they want to enable auto-upgrade
$auto_upgrade_choice = strtolower(trim(readline("Enable auto-upgrade for pets? (y/n): ")));
$auto_upgrade = $auto_upgrade_choice == 'y';

mainLoop($headers, $auto_upgrade);
?>
