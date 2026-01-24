# Agent Skill: Unraid Plugin Development (Target: Unraid 7.2)

## Role Definition
You are an expert Unraid Plugin Developer. You specialize in creating plugins for Unraid 7.2+, utilizing the latest conventions, the `.plg` XML installer system, and the Unraid webGUI (PHP/HTML/JS).

## Core Knowledge Base

### 1. File Structure & Locations
*   **Repository Structure** (Local Development):
    ```text
    plugin-name/
    ├── plugin-name.plg      # The installer manifest (XML + Bash)
    ├── src/                 # Source files (PHP, scripts, icons)
    │   ├── plugin-name/     # Directory matching the plugin name
    │   │   ├── ZramCard.php # Main Card Logic (HTML generator)
    │   │   ├── UnraidZramDash.page # Dashboard Registration
    │   │   ├── icon.png     # Plugin icon
    │   │   └── ...
    └── README.md
    ```
*   **On-Device Structure** (Runtime):
    *   **Config/Installer**: `/boot/config/plugins/plugin-name.plg`
    *   **Plugin Directory**: `/usr/local/emhttp/plugins/plugin-name/`
        *   Contains the actual PHP, JS, and asset files.
    *   **State/Settings**: `/boot/config/plugins/plugin-name/` (Persistent config usually goes here).

### 2. The `.plg` File (The Heart)
The `.plg` file is an XML document containing bash scripts for lifecycle events.

**CRITICAL: XML Parsing & Entities**
*   **Flatten Entities:** Do NOT use recursive entities (e.g., `<!ENTITY pluginURL "&gitURL;/file.plg">`) in the `pluginURL` attribute. Unraid's pre-installer often fails to expand them, causing "XML parse error". Use the full literal URL string for `pluginURL`.
*   **MD5 Checksums:** Always recommended for remote files to ensure integrity.

### 3. Unraid 7.2 Specifics & Dashboard Cards (The "Trailblazer" Method)

**Dashboard Integration has changed significantly.** The old method of just including a PHP file often leads to **Blank Page Crashes** due to variable scope collisions or buffer leaks.

#### The Safe "Function Pattern" (Required for Stability)
Do not write raw HTML/PHP logic at the top level of your included card file. Instead, wrap everything in a function.

**1. The Card Logic File (`MyCard.php`):**
```php
<?php
// Check for function existence to avoid redeclaration crashes
if (!function_exists('myPluginGetDashCard')) {
    function myPluginGetDashCard() {
        // 1. Safe Settings Loading (Use unique variable prefixes!)
        $my_config = parse_ini_file('/boot/config/plugins/my-plugin/settings.ini');
        
        // 2. Logic & Checks
        if ($my_config['enabled'] !== 'yes') return '';

        // 3. Output Generation (Buffer Capture)
        ob_start();
?>
        <!-- INLINE STYLES ONLY - Do not use <style> blocks inside tiles -->
        <tbody title="My Plugin">
            <tr>
                <td>
                    <div style="display: grid; ...">...</div>
                </td>
            </tr>
        </tbody>
<?php
        return ob_get_clean();
    }
}
?>
```

**2. The Dashboard Registration File (`MyPluginDash.page`):**
*   **Menu Attribute:** MUST be `Menu="Dashboard:0"` (The `:0` is crucial for ordering).
*   **Logic:** Require the file, check for the function, and assign the result.

```php
Menu="Dashboard:0"
Icon="server"
---
<?php
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
$file = "{$docroot}/plugins/my-plugin/MyCard.php";

if (file_exists($file)) {
    require_once $file;
    if (function_exists('myPluginGetDashCard')) {
        // Assign to the standard tiles array
        $mytiles['my-plugin']['column2'] = myPluginGetDashCard();
    }
}
?>
```

**Common Crashes (The "Blank Page" Symptom):**
*   **Cause:** Using `(function(){ ... })();` closures containing mixed HTML/PHP. Unraid's loader dislikes this structure.
*   **Cause:** `<style>` tags inside the dashboard tile HTML. Unraid 7's parser may choke on them. **Use Inline Styles** or load an external CSS file via a proper hook.
*   **Cause:** PHP Fatal Errors (e.g., `parse_ini_file` on missing files) inside the rendering loop. Always use `@` suppression or `file_exists` checks.

### 4. Robust Installation & Cleanup (Preventing "File Already Exists")

Unraid does not clean up old files during an update. You MUST handle this manually in **two** places.

**1. Pre-Install Cleanup (Updates):**
*   **Crucial:** You must assign a `Name` attribute (e.g., `Name="/tmp/cleanup"`) to the `<FILE>` tag containing your script. If omitted, the script is ignored.

```xml
<!-- Run this FIRST -->
<FILE Run="/bin/bash" Name="/tmp/plugin-cleanup">
<INLINE>
#!/bin/bash
# Nuke the old directory to ensure a clean install
rm -rf /usr/local/emhttp/plugins/&name;
echo "Cleanup complete."
</INLINE>
</FILE>
```

**2. Uninstall Cleanup (Removal):**
Duplicate the cleanup logic in the `<REMOVE>` script.

```xml
<REMOVE Script="remove.sh">
#!/bin/bash
removepkg &name;-&version;
rm -rf /usr/local/emhttp/plugins/&name;
# Force WebGUI refresh to remove ghost pages
if [ -f /usr/local/sbin/update_plugin_cache ]; then
    /usr/local/sbin/update_plugin_cache
fi
/etc/rc.d/rc.nginx reload
</REMOVE>
```

### 5. UI Development (PHP/HTML)
*   **Page Files**: A `.page` file in your plugin dir registers a menu item.
    *   **Settings Page:** `Menu="Utilities"` (or `Settings/Utilities`), `Icon="database"` (font awesome name without `fa-` prefix).
*   **DOM Structure Changes (Unraid 7.x)**:
    *   **Dashboard Tiles**: Use the new flexbox structure with `.tile-header`, `.tile-header-left/right`.
    *   **Grid**: Use CSS Grid for content layout inside the tile.

### 6. Development Workflow
1.  **Draft**: Edit files locally.
2.  **Package**: Create the `.plg` (and optional `.txz` archive of sources).
3.  **Deploy**:
    *   *Option A (Dev)*: SCP files directly to `/usr/local/emhttp/plugins/unraid-zram-card/` on the server for instant UI updates.
    *   *Option B (Install)*: Copy `.plg` to `/boot/config/plugins/` and reboot or run `installplg /boot/config/plugins/my.plg`.
4.  **Debug**:
    *   **System Log**: `tail -f /var/log/syslog` (for installer scripts).
    *   **Nginx Error Log**: `tail -f /var/log/nginx/error.log` (for "Blank Page" PHP fatal errors).

### 7. Diagnostics Framework
Unraid allows plugins to hook into the system diagnostics zip.
*   **Method**: Place a `diagnostics.json` file in your plugin dir (`/usr/local/emhttp/plugins/my-plugin/`).