<?php

declare(strict_types=1);

namespace LeoWasCoding;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\world\World;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\world\Position;

class Main extends PluginBase implements Listener {

    /** @var Config */
    private $cfg;

    /** @var array<string,bool> */
    private $teleported = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->cfg = $this->getConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onMove(PlayerMoveEvent $e): void {
        $p = $e->getPlayer();
        $worldName = $p->getWorld()->getFolderName();
        $pos = $e->getTo();

        if (!$p->hasPermission("voidtp.use")) {
            return;
        }

        $worlds = $this->cfg->get("worlds", []);
        if (!isset($worlds[$worldName]["void"])) {
            return;
        }

        $voidCfg = $worlds[$worldName]["void"];
        $threshold = (float) ($voidCfg["y"] ?? PHP_FLOAT_MAX);
        if ($pos->getY() >= $threshold) {
            unset($this->teleported[$p->getUniqueId()->toString()]);
            return;
        }

        $id = $p->getUniqueId()->toString();
        if (!empty($this->teleported[$id])) {
            return;
        }

        if (!isset($voidCfg["tp"]) || count($voidCfg["tp"]) !== 3) {
            $this->getLogger()->warning("Invalid TP coords for world {$worldName}");
            return;
        }
        [$x, $y, $z] = array_map('floatval', $voidCfg["tp"]);

        $world = $this->getServer()->getWorldManager()->getWorldByName($worldName);
        if (!$world instanceof World) {
            $this->getLogger()->warning("World '{$worldName}' not loaded on void‑tp");
            return;
        }

        $p->teleport(new Position($x, $y, $z, $world));
        $p->sendMessage("§eYou fell below Y={$threshold} and were void‑TPed.");
        $this->teleported[$id] = true;
    }

    public function onCommand(CommandSender $s, Command $c, string $lbl, array $args): bool {
        $name = strtolower($c->getName());
        if (!$s->hasPermission("voidtp.admin")) {
            $s->sendMessage("§cYou lack permission voidtp.admin");
            return true;
        }
    
        // Grab world name from args[0] when possible
        $worldName = $args[0] ?? null;
        if ($worldName !== null) {
            $wm = $this->getServer()->getWorldManager();
            // Check if a world folder exists (generated) AND is loaded (optional)
            if (!$wm->isWorldGenerated($worldName)) {
                $s->sendMessage("§cWorld '{$worldName}' does not exist.");
                return true;
            }
        }
    
        switch ($name) {
            case "void":
                // /void <world> <y-level>
                if (count($args) !== 2) {
                    $s->sendMessage("§eUsage: /void <world> <y-level>");
                    return true;
                }
                [, $y] = $args;
                if (!is_numeric($y)) {
                    $s->sendMessage("§cY-level must be a number.");
                    return true;
                }
                // update config
                $cfg = $this->cfg->get("worlds", []);
                $cfg[$worldName]["void"]["y"] = (float)$y;
                $this->cfg->set("worlds", $cfg);
                $this->cfg->save();
                $s->sendMessage("§aSet void Y-threshold for '{$worldName}' to {$y}.");
                return true;
    
            case "voidtp":
                // /voidtp <world> <x,y,z> OR /voidtp <world> <x> <y> <z>
                if (count($args) === 1) {
                    $s->sendMessage("§eUsage: /voidtp <world> <x,y,z> OR /voidtp <world> <x> <y> <z>");
                    return true;
                }
    
                // parse coords
                if (count($args) === 2 && strpos($args[1], ",") !== false) {
                    [$x, $y, $z] = explode(",", $args[1]);
                } elseif (count($args) === 4) {
                    [, $x, $y, $z] = $args;
                } else {
                    $s->sendMessage("§eUsage: /voidtp <world> <x,y,z> OR /voidtp <world> <x> <y> <z>");
                    return true;
                }
    
                if (!is_numeric($x) || !is_numeric($y) || !is_numeric($z)) {
                    $s->sendMessage("§cAll coordinates must be numbers.");
                    return true;
                }
    
                $coords = [(float)$x, (float)$y, (float)$z];
                $cfg    = $this->cfg->get("worlds", []);
                $cfg[$worldName]["void"]["tp"] = $coords;
                $this->cfg->set("worlds", $cfg);
                $this->cfg->save();
                $s->sendMessage("§aSet void‑TP coords for '{$worldName}' to " . implode(",", $coords) . ".");
                return true;
        }
    
        return true;
    }        
}
