<?php

/*
 *  _                               _ _
 * | |   _   _ _ __   __ _ _ __ ___| | |_   _
 * | |  | | | |  _ \ / _  |  __/ _ \ | | | | |
 * | |__| |_| | | | | (_| | | |  __/ | | |_| |
 * |_____\____|_| |_|\____|_|  \___|_|_|\___ |
 *                                      |___/
 *
 * Author: Lunarelly
 *
 * GitHub: https://github.com/Lunarelly
 *
 * Telegram: https://t.me/lunarellyy
 *
 */

namespace lunarauth\listener;

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
    entity\EntityDamageEvent,
    entity\EntityDamageByEntityEvent
};
use pocketmine\{
    Server,
    Player
};
use lunarauth\{
    LunarAuth,
    event\PlayerAuthorizationEvent
};
use pocketmine\entity\Effect;

use function strtolower;
use function str_replace;
use function in_array;

class EventListener implements Listener
{

    /**
     * @var LunarAuth
     */
    private $main;

    /**
     * @param LunarAuth $main
     */
    public function __construct(LunarAuth $main)
    {
        $this->main = $main;
    }

    /**
     * @param PlayerJoinEvent $event
     * @return void
     */
    public function playerJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $username = strtolower($player->getName());
        $config = $this->main->getConfig();

        $this->main->deauthenticateUser($player);
        $player->sendMessage(str_replace("{USER}", $player->getName(), $config->getNested("messages.joinMessage")));

        if ($config->getNested("settings.ipLogin")) {
            if ($this->main->isUserRegistered($username)) {
                if ($player->getAddress() == $this->main->getUserAddress($username)) {
                    $this->main->authenticateUser($player);
                    $player->sendMessage($config->getNested("messages.successfulAuthorization"));
                    return;
                }
            }
        }

        if ($config->getNested("settings.effects")) {
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

        if (!($this->main->isUserRegistered($username))) {
            if ($config->getNested("settings.chatAuth")) {
                $player->sendMessage($config->getNested("messages.userChatRegistration"));
            } else {
                $player->sendMessage($config->getNested("messages.userRegistration"));
            }
        } else {
            if ($config->getNested("settings.chatAuth")) {
                $player->sendMessage($config->getNested("messages.userChatLogin"));
            } else {
                $player->sendMessage($config->getNested("messages.userLogin"));
            }
        }
    }

    /**
     * @param PlayerAuthorizationEvent $event
     * @return void
     */
    public function playerAuthorization(PlayerAuthorizationEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if ($config->getNested("settings.effects")) {
            $player->removeEffect(Effect::INVISIBILITY);
            $player->removeEffect(Effect::BLINDNESS);
        }
    }

    /**
     * @param PlayerQuitEvent $event
     * @return void
     */
    public function playerQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();

        $this->main->removeAuthenticatedUser($player);
        $this->main->removeUserLoginAttempts($player);
        $this->main->removeUserLoginTime($player);
        $this->main->removeUserLoginMessageTime($player);
    }

    /**
     * @param PlayerMoveEvent $event
     * @return void
     */
    public function playerMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if (!($this->main->isUserAuthenticated($player))) {
            if (!($config->getNested("events.canMove"))) {
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param PlayerChatEvent $event
     * @return void
     */
    public function playerChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if (!($config->getNested("settings.chatAuth"))) {
            if (!($this->main->isUserAuthenticated($player))) {
                if (!($config->getNested("events.canUseChat"))) {
                    $event->setCancelled(true);
                }
            }
        }
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     * @return void
     */
    public function playerCommandPreprocess(PlayerCommandPreprocessEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();
        $message = strtolower($event->getMessage());

        if (!($this->main->isUserAuthenticated($player))) {
            if ($message{0} == "/") {
                $command = explode(" ", $message);
                $commands = ["/login", "/l", "/log", "/register", "/r", "/reg"];
                if (!(in_array($command[0], $commands))) {
                    if (!($config->getNested("events.canUseCommands"))) {
                        $event->setCancelled(true);
                    }
                }
            }
        }
    }

    /**
     * @param PlayerDropItemEvent $event
     * @return void
     */
    public function playerDropItem(PlayerDropItemEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if (!($this->main->isUserAuthenticated($player))) {
            if (!($config->getNested("events.canDropItems"))) {
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     * @return void
     */
    public function playerInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if (!($this->main->isUserAuthenticated($player))) {
            if (!($config->getNested("events.canInteract"))) {
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param PlayerItemConsumeEvent $event
     * @return void
     */
    public function playerItemConsume(PlayerItemConsumeEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if (!($this->main->isUserAuthenticated($player))) {
            if (!($config->getNested("events.canUseConsumableItems"))) {
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param PlayerPickupExpOrbEvent $event
     * @return void
     */
    public function playerPickupExpOrb(PlayerPickupExpOrbEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if (!($this->main->isUserAuthenticated($player))) {
            if (!($config->getNested("events.canPickupExperience"))) {
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param PlayerBedEnterEvent $event
     * @return void
     */
    public function playerBedEnter(PlayerBedEnterEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if (!($this->main->isUserAuthenticated($player))) {
            if (!($config->getNested("events.canUseBeds"))) {
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param PlayerAchievementAwardedEvent $event
     * @return void
     */
    public function playerAchievementAwarded(PlayerAchievementAwardedEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if (!($this->main->isUserAuthenticated($player))) {
            if (!($config->getNested("events.canGetAchievements"))) {
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param PlayerExhaustEvent $event
     * @return void
     */
    public function playerExhaust(PlayerExhaustEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if (!($this->main->isUserAuthenticated($player))) {
            if (!($config->getNested("events.canGetExhausted"))) {
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param PlayerPreLoginEvent $event
     * @return void
     */
    public function playerPreLogin(PlayerPreLoginEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if ($config->getNested("settings.singleAuth")) {
            $count = 0;

            foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                if (strtolower($players->getName()) == strtolower($player->getName())) {
                    $count++;
                }
            }

            if ($count > 0) {
                $event->setCancelled(true);
                $player->kick($config->getNested("kicks.userAlreadyOnline"), false);
            }
        }
    }

    /**
     * @param BlockBreakEvent $event
     * @return void
     */
    public function blockBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if (!($this->main->isUserAuthenticated($player))) {
            if (!($config->getNested("events.canBreakBlocks"))) {
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param BlockPlaceEvent $event
     * @return void
     */
    public function blockPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        $config = $this->main->getConfig();

        if (!($this->main->isUserAuthenticated($player))) {
            if (!($config->getNested("events.canPlaceBlocks"))) {
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     * @return void
     */
    public function entityDamage(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();
        $config = $this->main->getConfig();

        if ($entity instanceof Player) {
            if (!($this->main->isUserAuthenticated($entity))) {
                if (!($config->getNested("events.canBeDamaged"))) {
                    $event->setCancelled(true);
                }
            }
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            if ($damager instanceof Player) {
                if (!($this->main->isUserAuthenticated($damager))) {
                    if (!($config->getNested("events.canGiveDamage"))) {
                        $event->setCancelled(true);
                    }
                }
            }
        }
    }
}