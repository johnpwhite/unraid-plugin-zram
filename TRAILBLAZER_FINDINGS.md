# Trailblazer Findings: Unraid 7.2 Plugin Development (ZRAM Card)

**Date:** Jan 25, 2026
**Context:** Migrating/Creating a Dashboard Card plugin for Unraid 7.2.
**Status:** Dashboard Card Registration and Installation stability achieved.

---

## 🏆 The "Golden Path" (What Works)

### 1. Dashboard Registration: The "Function Pattern"
The most stable way to register a dashboard card in Unraid 7.x without crashing the page.

*   **Logic:** Do NOT execute code directly in the included file. Wrap it in a function.
*   **Variable Scope:** Prefix all variables (e.g., `$zram_settings`) to avoid colliding with Unraid's global scope.
*   **Return Type:** The function MUST return a string (HTML).

**Boilerplate (`ZramCard.php`):**
```php
<?php
if (!function_exists('getZramDashboardCard')) {
    function getZramDashboardCard() {
        ob_start();
        // Load settings safely...
        // HTML Output...
        return ob_get_clean();
    }
}
?>
```

**Boilerplate (`UnraidZramDash.page`):**
```php
Menu="Dashboard:0"
Icon="server"
---
<?php
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
$file = "{$docroot}/plugins/unraid-zram-card/ZramCard.php";
if (file_exists($file)) {
    require_once $file;
    if (function_exists('getZramDashboardCard')) {
        $mytiles['unraid-zram-card']['column2'] = getZramDashboardCard();
    }
}
?>
```

### 2. Styling: Inline Only
*   **Critical Finding:** Unraid 7's dashboard renderer (CSS Grid/Flexbox) **crashes or renders blank** if it encounters `<style>...</style>` blocks inside a dashboard tile.
*   **Solution:** Use **inline styles** (`style="display: grid; ..."`) for all elements within the PHP output. Do not rely on internal style sheets.

### 3. Robust Installation: The "Pre-Install Nuke"
*   **Problem:** Unraid does not clear the old plugin directory during an update (overwrite). This causes "skipping: file already exists" errors.
*   **Solution:** You MUST have a pre-install script with a `Name` attribute.

```xml
<FILE Run="/bin/bash" Name="/tmp/cleanup">
<INLINE>
rm -rf /usr/local/emhttp/plugins/unraid-zram-card
</INLINE>
</FILE>
```

---

## ❌ Failure Log (What NOT to do)

### 1. The "Closure" Pattern
*   **Attempt:** `(function(){ ... })();` inside `ZramCard.php`.
*   **Result:** **Blank Dashboard / Crash.**
*   **Why:** Unraid's loader likely has issues parsing or executing anonymous closures in this context, or it messes with the output buffer expectation.

### 2. Recursive XML Entities in `.plg`
*   **Attempt:** `<!ENTITY pluginURL "&gitURL;/file.plg">` inside the `<!DOCTYPE>` header.
*   **Result:** `XML file doesn't exist or xml parse error` during install.
*   **Why:** Unraid's simple XML parser does not always resolve recursive entities in attributes.
*   **Fix:** Hardcode the full URL in the `<PLUGIN>` attributes.

### 3. Direct Include (Legacy Method)
*   **Attempt:** Just `include 'ZramCard.php';` where the file echos HTML.
*   **Result:** Unreliable. Often leads to "Header already sent" or variable pollution issues.

### 4. Chart.js Integration (Pending)
*   **Status:** Currently disabled to verify layout stability.
*   **Next Step:** Re-introduce `chart.js` carefully. Ensure scripts are loaded *after* the DOM element exists, or use `defer`.

---

## 🛠 Recommended Workflow for Next Session

1.  **Start with the `.18` codebase:** It has the correct XML structure and the safe Function Pattern.
2.  **Verify Layout:** Ensure the "Inline Styles" card renders correctly.
3.  **Re-enable Chart.js:**
    *   Do NOT use `<style>` blocks for the chart.
    *   Load the script.
    *   Ensure the canvas ID is unique (`zramChart`).
    *   Initialize the chart inside a `DOMContentLoaded` event or a script tag at the *end* of the returned HTML string.
