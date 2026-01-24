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
            return '';
        }

        // 3. Check for Unraid 7.2+ Responsive GUI
        $zram_isResponsiveWebgui = false;
        if (file_exists('/etc/unraid-version')) {
            $zram_ver = @parse_ini_file('/etc/unraid-version');
            if ($zram_ver && isset($zram_ver['version'])) {
                $zram_isResponsiveWebgui = version_compare($zram_ver['version'], '7.2.0-beta', '>=');
            }
        }

        $zram_cardId = 'zram-dashboard-card';
?>
<!-- No Style Block - Inline Styles Only -->
<tbody title='ZRAM Usage' id='<?php echo $zram_cardId; ?>'>
  <tr>
    <td>
      <span class='tile-header'>
        <span class='tile-header-left'>
          <i class='fa fa-compress f32'></i>
          <div class='section'>
            <?php if ($zram_isResponsiveWebgui): ?>
              <h3 class='tile-header-main'>ZRAM Status</h3>
              <span id="zram-subtitle">Initializing...</span>
            <?php else: ?>
              ZRAM Status<br>
              <span id="zram-subtitle-legacy">Initializing...</span><br>
            <?php endif; ?>
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
        <div class="zram-content">
            <!-- Stats Grid with Inline Styles -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-bottom: 10px;">
                <div style="background-color: rgba(255, 255, 255, 0.05); padding: 10px; border-radius: 4px; text-align: center;">
                    <span id="zram-saved" style="font-size: 1.2em; font-weight: bold; display: block;">--</span>
                    <span style="font-size: 0.8em; opacity: 0.7;">RAM Saved</span>
                </div>
                <div style="background-color: rgba(255, 255, 255, 0.05); padding: 10px; border-radius: 4px; text-align: center;">
                    <span id="zram-ratio" style="font-size: 1.2em; font-weight: bold; display: block;">--</span>
                    <span style="font-size: 0.8em; opacity: 0.7;">Ratio</span>
                </div>
                <div style="background-color: rgba(255, 255, 255, 0.05); padding: 10px; border-radius: 4px; text-align: center;">
                    <span id="zram-used" style="font-size: 1.2em; font-weight: bold; display: block;">--</span>
                    <span style="font-size: 0.8em; opacity: 0.7;">Actual Used</span>
                </div>
            </div>
            
            <!-- Chart Placeholder -->
            <div style="padding: 10px; text-align: center; color: #888;">
                [Inline Style Test]
            </div>
            
            <!-- Table with Inline Styles -->
            <div class="TableContainer">
                <table id="zram-device-table" style="width: 100%; font-size: 0.9em; margin-top: 10px; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="text-align: left; opacity: 0.6; border-bottom: 1px solid rgba(255,255,255,0.1);">Device</th>
                            <th style="text-align: left; opacity: 0.6; border-bottom: 1px solid rgba(255,255,255,0.1);">Disk Size</th>
                            <th style="text-align: left; opacity: 0.6; border-bottom: 1px solid rgba(255,255,255,0.1);">Orig Data</th>
                            <th style="text-align: left; opacity: 0.6; border-bottom: 1px solid rgba(255,255,255,0.1);">Compr Data</th>
                            <th style="text-align: left; opacity: 0.6; border-bottom: 1px solid rgba(255,255,255,0.1);">Algorithm</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        
        <!-- Scripts (Still Disabled) -->
        <!--
        <script> ... </script>
        -->
    </td>
  </tr>
</tbody>
<?php
        return ob_get_clean();
    }
}
?>