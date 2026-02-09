# Unraid Plugin: ZRAM Status & Management

## 1. Context & Goals
**Project**: Unraid ZRAM Status & Management Plugin.
**Goal**: Maintain a high-quality dashboard card and swap management utility for Unraid 7.2+.

**Generic Development Guide:**
For general Unraid Plugin development workflow (`.plg` XML, Dashboard safety, etc.), refer to the **Master Index**:
[unraid-community-applications-index/unraid-development-guide.md](../../unraid-community-applications-index/unraid-development-guide.md)

---

## 2. Project Architecture
*   **GitLab (Private)**: The "Factory". `origin` -> `https://gitlab.johnpwhite.com/johner/unraid-plg-zram.git`
*   **GitHub (Public)**: The "Storefront". `public` -> `https://github.com/johnpwhite/unraid-plg-zram.git`

### Key File Map
*   `unraid-zram-card.plg`: The heart of the plugin.
*   `src/unraid-zram-card/ZramCard.php`: Dashboard logic (Function Pattern).
*   `src/unraid-zram-card/UnraidZramCard.page`: Settings UI (Card-based).
*   `src/unraid-zram-card/zram_swap.php`: Backend device manager.
*   `src/unraid-zram-card/zram_init.sh`: Boot persistence script.

---

## 3. Workflow

### Phase 1: Development & Local Testing
1.  **Modify Source**: Edit files in `src/unraid-zram-card/`.
2.  **Sync PLG**: Update version & changelog in `unraid-zram-card.plg`.
    *   *Reminder*: Use `&amp;` for `&`.
3.  **GitLab Push**: `git push origin master`
4.  **Local Install**: Test via WebUI or raw GitLab URL.

### Phase 2: Public Release (GitHub)
**Action**: Only when explicitly requested.
1.  **Update Public Assets**: `README.public.md` and `CHANGES.public.xml`.
2.  **Run Automation**: Execute `./publish-to-github.ps1`.
3.  **CA Update**: Copy the `.plg` URL or template updates to the **Index Repo** if necessary.
