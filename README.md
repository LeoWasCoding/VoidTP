# VoidTP

A **PocketMine-MP** plugin that automatically teleports players back to a safe location when they fall below a configurable Y‑level.

## [Download Latest Stable Release]

---

## 📦 Features

* **Configurable void threshold** per world (Y‑level at which to trigger a teleport)
* **Custom teleportation coordinates** per world
* **Permissions** for control over who can be void‑teleported and who can administer settings
* Simple **commands** to set thresholds and teleport points in‑game

---

## 🚀 Installation

1. Download the latest `VoidTP.phar` and place it in your server's `plugins/` folder.
2. Start or restart your PocketMine-MP server to generate the default `config.yml`.
3. Adjust configuration values in `plugin_data/VoidTP/config.yml` as desired.

---

## ⚙️ Configuration

The plugin’s configuration file is located at `plugins/VoidTP/config.yml`. It uses the following structure:

```yaml
worlds:
  world_name:
    void:
      y: 0.0         # Y-level threshold; falling below this triggers teleport
      tp: [0.0, 64.0, 0.0]  # Safe X, Y, Z coordinates to teleport to
```

* **`world_name`**: Folder name of the world (e.g., `world`, `world_nether`).
* **`y`**: The Y‑coordinate below which a player is considered "in the void." Defaults to `0.0`.
* **`tp`**: Array of three floats `[x, y, z]` defining where the player will be sent.

> **Note:** If either value is omitted or misconfigured, warnings will be logged to the console.

---

## 🛠 Commands

> Requires permission `voidtp.admin`.

| Command                       | Description                                                      | Usage                        |
| ----------------------------- | ---------------------------------------------------------------- | ---------------------------- |
| `/void <world> <y-level>`     | Set the void-Y threshold for a specific world                    | `/void myworld 5`            |
| `/voidtp <world> <x,y,z>`     | Set the safe teleport coordinates as comma-separated values      | `/voidtp myworld 100,70,100` |
| `/voidtp <world> <x> <y> <z>` | Alternate syntax: set teleport coordinates as separate arguments | `/voidtp myworld 100 70 100` |

---

## 🔐 Permissions

| Permission     | Default | Description                                     |
| -------------- | ------- | ----------------------------------------------- |
| `voidtp.use`   | `false`  | Allows a player to be void‑teleported           |
| `voidtp.admin` | `op`    | Grants access to `/void` and `/voidtp` commands |
