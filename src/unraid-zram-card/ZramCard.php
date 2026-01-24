<?php
// ZramCard.php
// Dashboard Card for ZRAM Statistics

if (!function_exists('getZramDashboardCard')) {
    function getZramDashboardCard() {
        ob_start();
        
        // 1. Load Settings safely
        $zram_configFile = "/boot/config/plugins/unraid-zram-card/settings.ini";
        $zram_settings = [
            'enabled' => 'yes',
            'refresh_interval' => 3000
        ];

        if (file_exists($zram_configFile)) {
            $zram_loaded = @parse_ini_file($zram_configFile);
            if ($zram_loaded && is_array($zram_loaded)) {
                $zram_settings = array_merge($zram_settings, $zram_loaded);
            }
        }

        // 2. Check if enabled
        if (($zram_settings['enabled'] ?? 'yes') !== 'yes') {
            ob_end_clean();
            return ''; // Should hide the card
        }

        // 3. Debug Output
        $debug_info = "Enabled: " . $zram_settings['enabled'] . "<br>";
        $debug_info .= "Refresh: " . $zram_settings['refresh_interval'] . "ms<br>";
        $debug_info .= "Config File: " . (file_exists($zram_configFile) ? "Found" : "Missing");

?>
<tbody title='ZRAM Debug' id='zram-debug-card'>
  <tr>
    <td>
      <span class='tile-header'>
        <span class='tile-header-left'>
          <i class='fa fa-bug f32'></i>
          <div class='section'>
            <h3 class='tile-header-main'>ZRAM Settings Test</h3>
            <span>Logic Verification</span>
          </div>
        </span>
        <span class='tile-header-right'>
          <span class='tile-ctrl'>
             <a href="/Settings/UnraidZramCard" title="Settings"><i class="fa fa-cog"></i></a>
          </span>
        </span>
      </span>
    </td>
  </tr>
  <tr>
    <td>
        <div style="padding: 10px; font-family: monospace;">
            <strong>Settings Loaded:</strong><br>
            <?php echo $debug_info; ?>
        </div>
    </td>
  </tr>
</tbody>
<?php
        return ob_get_clean();
    }
}
?>