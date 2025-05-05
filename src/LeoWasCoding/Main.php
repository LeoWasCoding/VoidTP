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
    
        $worlds = $this->cfg->get("worlds", []);
        if (!isset($worlds[$worldName]["void"])) {
            return;
        }
    
        $voidCfg = $worlds[$worldName]["void"];
        $threshold = (float)($voidCfg["y"] ?? PHP_FLOAT_MAX);
        if ($pos->getY() >= $threshold) {
            unset($this->teleported[$p->getUniqueId()->toString()]);
            return;
        }
    
        if (!$p->hasPermission("voidtp.use")) {
            $p->kill();
            $p->sendMessage("§cYou fell into the void!");
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
        $this->teleported[$id] = true;
    }    

    public function onCommand(CommandSender $s, Command $c, string $lbl, array $args): bool {
        if (!$s->hasPermission("voidtp.admin")) {
            $s->sendMessage("§cYou lack permission voidtp.admin");
            return true;
        }
    
        $wm = $this->getServer()->getWorldManager();
        $name = strtolower($c->getName());
    
        switch ($name) {
            case "void":
                if (count($args) !== 2) {
                    $s->sendMessage("§eUsage: /void <world> <y-level>");
                    return true;
                }
    
                [$worldName, $y] = $args;
                if (!$wm->isWorldGenerated($worldName)) {
                    $s->sendMessage("§cWorld '{$worldName}' does not exist.");
                    return true;
                }
    
                if (!is_numeric($y)) {
                    $s->sendMessage("§cY-level must be a number.");
                    return true;
                }
    
                $cfg = $this->cfg->get("worlds", []);
                $cfg[$worldName]["void"]["y"] = (float)$y;
                $this->cfg->set("worlds", $cfg);
                $this->cfg->save();
                $s->sendMessage("§aSet void Y-threshold for '{$worldName}' to {$y}.");
                return true;
    
            case "voidtp":
                if (count($args) < 2) {
                    $s->sendMessage("§eUsage: /voidtp <world> <x,y,z> OR /voidtp <world> <x> <y> <z>");
                    return true;
                }
    
                $worldName = $args[0];
                if (!$wm->isWorldGenerated($worldName)) {
                    $s->sendMessage("§cWorld '{$worldName}' does not exist.");
                    return true;
                }
    
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
    
                $cfg = $this->cfg->get("worlds", []);
                $cfg[$worldName]["void"]["tp"] = [(float)$x, (float)$y, (float)$z];
                $this->cfg->set("worlds", $cfg);
                $this->cfg->save();
                $s->sendMessage("§aSet void‑TP coords for '{$worldName}' to {$x},{$y},{$z}.");
                return true;
    
            case "voiddel":
                if (count($args) !== 1) {
                    $s->sendMessage("§eUsage: /voiddel <world>");
                    return true;
                }
                $worldName = $args[0];
                $cfg = $this->cfg->get("worlds", []);
                if (!isset($cfg[$worldName])) {
                    $s->sendMessage("§cNo void settings exist for '{$worldName}'.");
                    return true;
                }
                unset($cfg[$worldName]);
                $this->cfg->set("worlds", $cfg);
                $this->cfg->save();
                $s->sendMessage("§aDeleted void config for '{$worldName}'.");
                return true;
    
            case "voidlist":
                $cfg = $this->cfg->get("worlds", []);
                if (empty($cfg)) {
                    $s->sendMessage("§eNo void configs are currently set.");
                    return true;
                }
    
                $s->sendMessage("§aVoid Configured Worlds:");
                foreach ($cfg as $world => $data) {
                    $y = $data["void"]["y"] ?? "N/A";
                    $tp = isset($data["void"]["tp"]) ? implode(",", $data["void"]["tp"]) : "N/A";
                    $s->sendMessage("§7- §b{$world}§f: Y={$y}, TP=§7{$tp}");
                }
                return true;
        }
    
        return false;
    }               
}
