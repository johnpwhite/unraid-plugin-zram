<?php
// zram_swap.php
// Backend logic for managing ZRAM swap devices with algorithm support

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$size = $_POST['size'] ?? '1G';
$algo = $_POST['algo'] ?? 'zstd';
$device = $_POST['device'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

$configFile = "/boot/config/plugins/unraid-zram-card/settings.ini";

function get_zram_settings($file) {
    $defaults = ['enabled' => 'yes', 'refresh_interval' => '3000', 'zram_devices' => '', 'swap_size' => '1G', 'compression_algo' => 'zstd'];
    if (file_exists($file)) {
        $loaded = @parse_ini_file($file);
        return array_merge($defaults, $loaded ?: []);
    }
    return $defaults;
}

function save_zram_settings($file, $settings) {
    $res = [];
    foreach($settings as $key => $val) {
        $res[] = "$key=\"$val\"";
    }
    if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
    file_put_contents($file, implode("\n", $res));
}

if ($action === 'create') {
    exec('modprobe zram 2>&1', $out, $ret);
    
    // 1. Find device
    exec('zramctl --find', $out, $ret);
    if ($ret === 0) {
        $dev = trim(end($out));
        
        // 2. Set Size FIRST (Initialize device)
        exec("zramctl --size " . escapeshellarg($size) . " $dev 2>&1", $out, $ret);
        if ($ret !== 0) file_put_contents('/tmp/zram_debug.log', "Size Fail: " . implode(" ", $out) . "\n", FILE_APPEND);

        // 3. Set Algorithm (While device is un-formatted but sized)
        exec("zramctl --algorithm " . escapeshellarg($algo) . " $dev 2>&1", $out, $ret);
        if ($ret !== 0) file_put_contents('/tmp/zram_debug.log', "Algo Fail: " . implode(" ", $out) . "\n", FILE_APPEND);
        
        // 4. Swap
        exec("mkswap $dev 2>&1", $out, $ret);
        exec("swapon $dev -p 100 2>&1", $out, $ret);
        
        // Update Persistence (Format: size:algo,size:algo)
        $settings = get_zram_settings($configFile);
        $devices = array_filter(explode(',', $settings['zram_devices']));
        $devices[] = "$size:$algo";
        $settings['zram_devices'] = implode(',', $devices);
        save_zram_settings($configFile, $settings);
        
        $response = ['success' => true, 'message' => "Created ZRAM ($size, $algo) on $dev"];
    } else {
        $response = ['success' => false, 'message' => "Failed to find zram device"];
    }
} 

elseif ($action === 'remove') {
    if (empty($device)) {
        // Remove ALL
        exec('zramctl --noheadings --raw --output NAME', $devs);
        foreach ($devs as $d) {
            $d = trim($d);
            if ($d) {
                exec("swapoff /dev/$d 2>/dev/null");
                exec("zramctl --reset /dev/$d 2>/dev/null");
            }
        }
        $settings = get_zram_settings($configFile);
        $settings['zram_devices'] = '';
        save_zram_settings($configFile, $settings);
        $response = ['success' => true, 'message' => "Removed all ZRAM devices"];
    } else {
        // Remove SPECIFIC
        $devPath = (strpos($device, '/dev/') === false) ? "/dev/$device" : $device;
        exec("swapoff $devPath 2>&1");
        exec("zramctl --reset $devPath 2>&1");
        
        $settings = get_zram_settings($configFile);
        $devices = array_filter(explode(',', $settings['zram_devices']));
        array_pop($devices); 
        $settings['zram_devices'] = implode(',', $devices);
        save_zram_settings($configFile, $settings);
        
        $response = ['success' => true, 'message' => "Removed $device"];
    }
}

echo json_encode($response);
?>