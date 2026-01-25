<?php
// zram_swap.php
// Backend logic with detailed execution logging

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$size = $_POST['size'] ?? '1G';
$algo = $_POST['algo'] ?? 'zstd';
$device = $_POST['device'] ?? '';

$configFile = "/boot/config/plugins/unraid-zram-card/settings.ini";
$logDir = "/tmp/unraid-zram-card";
if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
$debugLog = $logDir . "/debug.log";

$logs = [];

function run_cmd($cmd, &$logs, $debugLog) {
    exec($cmd . " 2>&1", $out, $ret);
    $entry = [
        'cmd' => $cmd,
        'output' => implode(" ", $out),
        'status' => $ret
    ];
    $logs[] = $entry;
    @file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "CMD: $cmd | Status: $ret | Output: " . $entry['output'] . "\n", FILE_APPEND);
    return $ret;
}

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
    run_cmd('modprobe zram', $logs, $debugLog);
    
    // Combined command
    $cmd = "zramctl --find --size " . escapeshellarg($size) . " --algorithm " . escapeshellarg($algo);
    exec($cmd . " 2>&1", $find_out, $ret);
    
    $logs[] = [
        'cmd' => $cmd,
        'output' => implode(" ", $find_out),
        'status' => $ret
    ];
    @file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "CREATE: $cmd | Status: $ret\n", FILE_APPEND);

    if ($ret === 0) {
        $dev = trim(end($find_out));
        run_cmd("mkswap $dev", $logs, $debugLog);
        $s_ret = run_cmd("swapon $dev -p 100", $logs, $debugLog);
        
        if ($s_ret === 0) {
            $settings = get_zram_settings($configFile);
            $devices = array_filter(explode(',', $settings['zram_devices']));
            $devices[] = "$size:$algo";
            $settings['zram_devices'] = implode(',', $devices);
            save_zram_settings($configFile, $settings);
            echo json_encode(['success' => true, 'message' => "Created $dev", 'logs' => $logs]);
        } else {
            echo json_encode(['success' => false, 'message' => "Swap activation failed", 'logs' => $logs]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "No free ZRAM device", 'logs' => $logs]);
    }
} 

elseif ($action === 'remove') {
    if (empty($device)) {
        exec('zramctl --noheadings --raw --output NAME', $devs);
        foreach ($devs as $d) {
            $d = trim($d);
            if ($d) {
                $devPath = (strpos($d, '/dev/') === 0) ? $d : "/dev/$d";
                run_cmd("swapoff $devPath", $logs, $debugLog);
                run_cmd("zramctl --reset $devPath", $logs, $debugLog);
            }
        }
        $settings = get_zram_settings($configFile);
        $settings['zram_devices'] = '';
        save_zram_settings($configFile, $settings);
        echo json_encode(['success' => true, 'message' => "Cleared all", 'logs' => $logs]);
    } else {
        $devPath = (strpos($device, '/dev/') === 0) ? $device : "/dev/$device";
        run_cmd("swapoff $devPath", $logs, $debugLog);
        run_cmd("zramctl --reset $devPath", $logs, $debugLog);
        
        $settings = get_zram_settings($configFile);
        $devices = array_filter(explode(',', $settings['zram_devices']));
        array_pop($devices); 
        $settings['zram_devices'] = implode(',', $devices);
        save_zram_settings($configFile, $settings);
        
        echo json_encode(['success' => true, 'message' => "Removed $device", 'logs' => $logs]);
    }
}
?>
