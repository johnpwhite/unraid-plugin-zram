# Agent Skill: Unraid Plugin Development (Target: Unraid 7.2+)

## Role Definition
You are an expert Unraid Plugin Developer. You specialize in creating robust, visually integrated plugins for Unraid 7.2+, utilizing the `.plg` XML installer system and the Unraid webGUI (PHP/HTML/JS).

---

## 1. The Installer (`.plg` XML)

### The "Hybrid XML" Strategy (Installation Stability)
Unraid's pre-installer parser is strict and can fail with "XML parse error" if the root tag is complex.
*   **Header Attributes**: Always **hardcode** `pluginURL`, `name`, and `version` inside the `<PLUGIN>` tag as literal strings. Do not use recursive entities here.
*   **Payload Entities**: Use entities (`&gitURL;`, `&emhttp;`) for the `<FILE>` tags to keep the file list maintainable.
*   **Ampersands**: NEVER use a raw `&` in `<CHANGES>` or script blocks. Use `&amp;`.

### Hybrid Deployment (Air-Gap Support)
Unraid 7 has **removed the `installplg` command**. Manual installation is now done by browsing to the `.plg` file via the WebUI. To support air-gapped servers:
*   **Multi-Path Detection**: Your installer script should check for local source files in multiple locations (e.g., root of the flash folder, `js/` subfolders, or repository structure) to be resilient to how a user might have manually copied the files.
*   **Fallbacks**: Only attempt `wget` if the local file is absolutely missing.

---

## 2. Dashboard Integration

### Critical: Avoid Nested Tables
**NEVER use `<table>` or `<tbody>` tags inside a dashboard card.**
*   **Reason**: Unraid’s `dynamix.js` recursively scans the DOM for `tbody` elements for drag-and-drop logic. A nested table triggers a JS crash: `TypeError: Cannot read properties of undefined (reading 'md5')`.
*   **Solution**: Use **CSS Grid** or **Flexbox** with `<div>` elements for all tabular data.

### The "Function Pattern"
Wrap card logic in a unique function returned via `ob_get_clean()`. This prevents variable scope pollution and "Header already sent" errors.

---

## 3. UI/UX and Charting

### Chart.js Visual Precision
When charting human-readable units (Bytes, GB, etc.) on the Y-Axis:
*   **Decimal Precision**: Ensure labels have at least 1 decimal place (e.g., `1.3 GB`) for values > 1MB. Using whole numbers only (`1 GB`) causes visual misalignment where data points appear to "float" above their labels.
*   **Grace/Headroom**: Add a `grace: '10%'` factor to the Y-axis. This prevents the data line from touching the top edge of the card, providing "breathing room" for a better UX.

### Theme Integration
*   **Button Elements**: Prefer `<button>` over `<input type="submit">`. System styles target `input` more heavily, making them harder to override.
*   **Input Visibility**: Explicitly set `background` and `color` for `:focus` states to ensure users can see text in dark themes.

---

## 4. Backend and Persistence

### System Tools
*   **Prefer JSON**: Use `--json` output flags for tools like `zramctl`. 
*   **Combined Commands**: If a tool requires multiple parameters to initialize (like `--size` and `--algo`), execute them in a **single combined call** to satisfy kernel state requirements.

### Boot Persistence
Trigger an initialization script via the `.plg` `<INSTALL>` phase to re-apply settings stored in `/boot/config/plugins/plugin-name/settings.ini`.

---

## 5. Robust Uninstallation

### The "Golden Path"
Use the `<FILE Run="/bin/bash" Method="remove">` pattern. 

| Action | Best Practice | Why? |
| :--- | :--- | :--- |
| **Output** | Plain Text | Complex redirection (`exec > log`) or silent scripts can cause the Unraid 7 uninstall dialogue to hang. Talkative scripts allow the UI to track progress. |
| **Nchan/Nginx** | Do NOT reload | Reloading the web server during an uninstall request drops the connection and hangs the dialogue. |
| **Cleanup** | `rm -rf` | Purge `/usr/local/emhttp/plugins/plugin-name/` and `/tmp/plugin-name/`. |

---

## 6. Troubleshooting Failure Patterns

| Symptom | Probable Cause | Fix |
| :--- | :--- | :--- |
| XML Parse Error | Raw `&` or recursive entities | Use `&amp;` and hardcode header attributes. |
| Dashboard Crashes | Nested `<tbody>` | Replace nested tables with `div` grids. |
| Chart misaligned | Rounding in Y-Axis labels | Add decimal precision to label formatting. |
| Uninstall hangs | Silent script or Nginx reload | Remove reload commands and ensure script echos progress. |
