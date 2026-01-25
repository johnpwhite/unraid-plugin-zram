# Agent Skill: Unraid Plugin Development (Target: Unraid 7.2)

## Role Definition
You are an expert Unraid Plugin Developer. You specialize in creating plugins for Unraid 7.2+, utilizing the latest conventions, the `.plg` XML installer system, and the Unraid webGUI (PHP/HTML/JS).

## Core Knowledge Base

### 1. File Structure & Locations
*   **Local Structure**: `src/plugin-name/`, `plugin-name.plg`.
*   **Runtime Structure**:
    *   **EMHTTP**: `/usr/local/emhttp/plugins/plugin-name/` (Web files).
    *   **Persistence**: `/boot/config/plugins/plugin-name/` (Settings/Flash).
    *   **Logging**: `/tmp/plugin-name/` (Volatile logs).

### 2. The `.plg` Installer (XML)

#### The "Hybrid XML" Strategy (CRITICAL)
Unraid's bootstrapper parser is strict.
*   **Hardcode Header Attributes**: `pluginURL`, `version`, `name` in the `<PLUGIN>` tag MUST be literal strings.
*   **Use Entities for Payload**: Use `&gitURL;` and `&emhttp;` only inside `<FILE>` and script blocks.
*   **Escape Ampersands**: NEVER use a raw `&` in the `<CHANGES>` or script blocks. Use `&amp;`.

#### Robust Uninstallation (The "Golden Path")
**Always use the `<FILE Method="remove">` pattern.**
```xml
<FILE Run="/bin/bash" Method="remove">
<INLINE>
    # 1. Capture output for both User and Logs
    LOG="/tmp/&name;/uninstall.log"
    echo "Starting removal..." | tee "$LOG"
    
    # 2. Cleanup Logic
    rm -rf /usr/local/emhttp/plugins/&name;
    removepkg &name;
    
    # 3. Refresh UI
    /usr/local/sbin/update_plugin_cache
    exit 0
</INLINE>
</FILE>
```

### 3. Dashboard Integration (Unraid 7.x)

#### Critical: Avoid Nested Tables
**Do NOT use `<table>` tags inside your dashboard card.**
*   **Reason**: Unraid's `dynamix.js` scans all `tbody` elements for drag-and-drop logic. A nested table triggers a JS crash: `TypeError: Cannot read properties of undefined (reading 'md5')`.
*   **Solution**: Use **CSS Grid** or **Flexbox** with `<div>` elements for tabular data.

#### The "Function Pattern"
Wrap all PHP card logic in a unique function returned via `ob_get_clean()` to prevent variable collisions.

### 4. Failure Patterns (What NOT to do)

| Feature | Pattern that FAILS | Why it fails |
| :--- | :--- | :--- |
| **Uninstall** | `<REMOVE Script="remove.sh">` | Unraid often ignores the Script attribute or looks for a file that was just deleted. |
| **Uninstall** | `exec > /tmp/log 2>&1` | Redirecting all output hides progress from the WebUI, causing the uninstall dialogue to hang. |
| **Uninstall** | `rc.nginx reload` | Reloading the web server during an uninstall request drops the connection and hangs the UI. |
| **XML** | `pluginURL="&pluginURL;"` | Recursive entity resolution in the root tag often causes "XML Parse Error". |
| **ZRAM** | `zramctl --algo ...` then `zramctl --size ...` | Some kernels require size to be initialized before the algorithm can be changed. |
| **ZRAM** | Multiple `zramctl` calls | Best practice is a single combined call: `zramctl --find --size X --algo Y`. |

### 5. Deployment & Persistence
*   **Hybrid Installer**: Support local-only servers by checking `/boot/config/plugins/plugin-name/` for files before attempting a `wget` from GitLab.
*   **Persistence**: Trigger a boot-script (`zram_init.sh`) via the `.plg` `<INSTALL>` phase to re-apply settings without modifying the system `go` file.
