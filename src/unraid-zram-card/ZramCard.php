<?php
// ZramCard.php - Settings Enabled Version

if (!function_exists('getZramDashboardCard')) {
    function getZramDashboardCard() {
        // 1. Settings Loading
        $zram_defaults = [
            'enabled' => 'yes',
            'refresh_interval' => 3000
        ];
        $zram_settings = $zram_defaults;
        $zram_iniFile = '/boot/config/plugins/unraid-zram-card/settings.ini';
        
        if (file_exists($zram_iniFile)) {
            $zram_loaded = @parse_ini_file($zram_iniFile);
            if (is_array($zram_loaded)) {
                $zram_settings = array_merge($zram_settings, $zram_loaded);
            }
        }

        // 2. Check Enabled Status
        if (($zram_settings['enabled'] ?? 'yes') !== 'yes') {
            return ''; // Hide card if disabled
        }

        // 3. Unraid Version Check (7.2+)
        $zram_isResponsive = false;
        if (file_exists('/etc/unraid-version')) {
            $zram_ver_arr = @parse_ini_file('/etc/unraid-version');
            if (isset($zram_ver_arr['version'])) {
                $zram_isResponsive = version_compare($zram_ver_arr['version'], '7.2.0-beta', '>=');
            }
        }
        
        // 4. Output
        ob_start();
?>
        <tbody title='ZRAM Settings Test'>
            <tr>
                <td>
                    <span class='tile-header'>
                        <span class='tile-header-left'>
                            <i class='fa fa-cogs'></i>
                            <div class='section'>
                                <h3 class='tile-header-main'>ZRAM Settings</h3>
                                <span>Status: Loaded</span>
                            </div>
                        </span>
                    </span>
                </td>
            </tr>
            <tr>
                <td>
                    <div style="padding: 10px;">
                        <strong>Settings Loaded:</strong><br>
                        Enabled: <?php echo htmlspecialchars($zram_settings['enabled']); ?><br>
                        Refresh: <?php echo htmlspecialchars($zram_settings['refresh_interval']); ?><br>
                        Responsive GUI: <?php echo $zram_isResponsive ? 'Yes' : 'No'; ?>
                    </div>
                </td>
            </tr>
        </tbody>
<?php
        return ob_get_clean();
    }
}
?>