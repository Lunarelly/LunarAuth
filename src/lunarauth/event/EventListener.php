<?php

/*
 *  _							    _ _	   
 * | |   _   _ _ __   __ _ _ __ ___| | |_   _ 
 * | |  | | | | '_ \ / _` | '__/ _ \ | | | | |
 * | |__| |_| | | | | (_| | | |  __/ | | |_| |
 * |_____\__,_|_| |_|\__,_|_|  \___|_|_|\__, |
 *									    |___/ 
 * 
 * Author: Lunarelly
 * 
 * GitHub: https://github.com/Lunarelly
 * 
 * Telegram: https://t.me/lunarellyy
 * 
 */

namespace lunarauth\event;

use pocketmine\event\{
    Listener,
    player\PlayerJoinEvent,
    player\PlayerQuitEvent,
    player\PlayerMoveEvent,
    player\PlayerChatEvent,
    player\PlayerCommandPreprocessEvent,
    player\PlayerDropItemEvent,
    player\PlayerInteractEvent,
    player\PlayerItemConsumeEvent,
    player\PlayerPickupExpOrbEvent,
    player\PlayerBedEnterEvent,
    player\PlayerAchievementAwardedEvent,
    player\PlayerExhaustEvent,
    player\PlayerPreLoginEvent,
    block\BlockBreakEvent,
    block\BlockPlaceEvent,
    entity\EntityDamageEvent
};
use pocketmine\{
    Server,
    Player
};
use pocketmine\entity\Effect;
use lunarauth\LunarAuth;

use function strtolower;
use function str_replace;
use function in_array;

class EventListener implements Listener {

    public function __construct(LunarAuth $main) {
        $this->main = $main;
    }

    public function playerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $username = strtolower($player->getName());
        $config = $this->main->getConfig();
        $this->main->authenticateUser($player, false);
        $player->sendMessage(str_replace("{USER}", $player->getName(), $config->getNested("messages.joinMessage")));
        if($config->getNested("settings.ipLogin") == true) {
            if($player->getAddress() == $this->main->getUserAddress($username)) {
                $this->main->authenticateUser($player, true);
                return $player->sendMessage($config->getNested("messages.successfulAuthorization"));
            }
        }
        if($config->getNested("settings.effects") == true) {
            $invisibility = Effect::getEffect(Effect::INVISIBILITY);
            $blindness = Effect::getEffect(Effect::BLINDNESS);
            $invisibility->setAmplifier(0);
            $invisibility->setDuration(Effect::MAX_DURATION);
            $invisibility->setVisible(false);
            $blindness->setAmplifier(0);
            $blindness->setDuration(Effect::MAX_DURATION);
            $blindness->setVisible(false);
            $player->addEffect($invisibility);
            $player->addEffect($blindness);
        }
        if($this->main->isUserRegistred($username) == false) {
            if($config->getNested("settings.chatAuth") == true) {
                $player->sendMessage($config->getNested("messages.userChatRegistration"));
            } else {
                $player->sendMessage($config->getNested("messages.userRegistration"));
            }
        } else {
            if($config->getNested("settings.chatAuth") == true) {
                $player->sendMessage($config->getNested("messages.userChatLogin"));
            } else {
                $player->sendMessage($config->getNested("messages.userLogin"));
            }
        }
    }

    public function playerQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $this->main->removeAuthenticatedUser($player);
        $this->main->removeUserLoginAttempts($player);
    }

    public function playerMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        if($this->main->isUserAuthenticated($player) == false) {
            $event->setCancelled(true);
        }
    }

    public function playerChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();
        if($config->getNested("settings.chatAuth") == false) {
            if($this->main->isUserAuthenticated($player) == false) {
                $event->setCancelled(true);
            }
        }
    }

    public function playerCommandPreprocess(PlayerCommandPreprocessEvent $event) {
        $player = $event->getPlayer();
        $message = strtolower($event->getMessage());
        if($this->main->isUserAuthenticated($player) == false) {
            if($message{0} == "/") {
                $command = explode(" ", $message);
                $commands = ["/login", "/l", "/log", "/register", "/r", "/reg"];
                if(!(in_array($command[0], $commands))) {
                    $event->setCancelled(true);
                }
            }
        }
    }

    public function playerDropItem(PlayerDropItemEvent $event) {
        $player = $event->getPlayer();
        if($this->main->isUserAuthenticated($player) == false) {
            $event->setCancelled(true);
        }
    }

    public function playerInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        if($this->main->isUserAuthenticated($player) == false) {
            $event->setCancelled(true);
        }
    }

    public function playerItemConsume(PlayerItemConsumeEvent $event) {
        $player = $event->getPlayer();
        if($this->main->isUserAuthenticated($player) == false) {
            $event->setCancelled(true);
        }
    }

    public function playerPickupExpOrb(PlayerPickupExpOrbEvent $event) {
        $player = $event->getPlayer();
        if($this->main->isUserAuthenticated($player) == false) {
            $event->setCancelled(true);
        }
    }

    public function playerBedEnter(PlayerBedEnterEvent $event) {
        $player = $event->getPlayer();
        if($this->main->isUserAuthenticated($player) == false) {
            $event->setCancelled(true);
        }
    }

    public function playerAchievementAwarded(PlayerAchievementAwardedEvent $event) {
        $player = $event->getPlayer();
        if($this->main->isUserAuthenticated($player) == false) {
            $event->setCancelled(true);
        }
    }

    public function playerExhaust(PlayerExhaustEvent $event) {
        $player = $event->getPlayer();
        if($this->main->isUserAuthenticated($player) == false) {
            $event->setCancelled(true);
        }
    }

    public function playerPreLogin(PlayerPreLoginEvent $event) {
        $player = $event->getPlayer();
        $count = 0;
        foreach(Server::getInstance()->getOnlinePlayers() as $players) {
            if(strtolower($player->getName()) == strtolower($players->getName())) {
                $count++;
            }
        }
        if($count > 0) {
            $player->kick($config->getNested("kicks.userAlreadyOnline"), false);
            $event->setCancelled(true);
        }
    }

    public function blockBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        if($this->main->isUserAuthenticated($player) == false) {
            $event->setCancelled(true);
        }
    }

    public function blockPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        if($this->main->isUserAuthenticated($player) == false) {
            $event->setCancelled(true);
        }
    }

    public function entityDamage(EntityDamageEvent $event) {
        $entity = $event->getEntity();
        if($entity instanceof Player) {
            if($this->main->isUserAuthenticated($entity) == false) {
                $event->setCancelled(true);
            }
        }
    }
}