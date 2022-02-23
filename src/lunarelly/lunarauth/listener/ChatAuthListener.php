<?php

/**
 *  _                               _ _
 * | |   _   _ _ __   __ _ _ __ ___| | |_   _
 * | |  | | | |  _ \ / _  |  __/ _ \ | | | | |
 * | |__| |_| | | | | (_| | | |  __/ | | |_| |
 * |_____\____|_| |_|\____|_|  \___|_|_|\___ |
 *                                      |___/
 *
 * @author Lunarelly
 * @link https://github.com/Lunarelly
 *
 */

namespace lunarelly\lunarauth\listener;

use pocketmine\event\{
    Listener,
    player\PlayerChatEvent
};
use lunarelly\lunarauth\LunarAuth;

use function strtolower;
use function str_replace;
use function preg_match;

class ChatAuthListener implements Listener
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
     * @param PlayerChatEvent $event
     * @return void
     */
    public function chatLogin(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        $username = strtolower($player->getName());
        $config = $this->main->getConfig();

        if ($config->getNested("settings.chatAuth")) {
            if ($this->main->isUserAuthenticated($player)) {
                if ($config->getNested("settings.noPasswordsInChat")) {
                    $message = explode(" ", $event->getMessage());

                    if ($this->main->getConfig()->getNested("settings.encrypt")) {
                        $password = $this->main->hash($message[0]);
                    } else {
                        $password = $message[0];
                    }

                    if ($password === $this->main->getUserPassword($username)) {
                        $event->setCancelled(true);
                        $player->sendMessage($config->getNested("messages.userAlreadyLoggedIn"));
                    }
                }
            } elseif(!($this->main->isUserAuthenticated($player))) {
                $event->setCancelled(true);
                $message = explode(" ", $event->getMessage());
                if (!($this->main->isUserRegistered($username))) {
                    if (empty($message) or !(isset($message[0])) or !(isset($message[1]))) {
                        $player->sendMessage($config->getNested("usages.chatRegister"));
                        return;
                    }

                    if (preg_match("/^[\x{0020}-\x{007E}]*$/", $message[0]) == 0 or preg_match("/^[\x{0020}-\x{007E}]*$/", $message[1]) == 0) {
                        $player->sendMessage($config->getNested("messages.invalidPasswordSymbols"));
                        return;
                    }

                    $minLength = $config->getNested("settings.minPasswordLength");
                    $maxLength = $config->getNested("settings.maxPasswordLength");
                    if (strlen($message[0]) < $minLength or strlen($message[0]) > $maxLength or strlen($message[1]) < $minLength or strlen($message[1]) > $maxLength) {
                        $player->sendMessage($config->getNested("messages.invalidPasswordLength"));
                        return;
                    }

                    if (!($message[0] === $message[1])) {
                        $player->sendMessage($config->getNested("messages.passwordsDoesNotMatch"));
                        return;
                    }

                    $password = $message[0];
                    $this->main->registerUser($player, $password);
                    $player->sendMessage(str_replace("{PASSWORD}", $password, $config->getNested("messages.successfulRegistration")));
                } else {
                    if (empty($message) or !(isset($message[0]))) {
                        $player->sendMessage($config->getNested("usages.chatLogin"));
                        return;
                    }

                    if ($this->main->getConfig()->getNested("settings.encrypt")) {
                        $password = $this->main->hash($message[0]);
                    } else {
                        $password = $message[0];
                    }

                    if (!($password === $this->main->getUserPassword($username))) {
                        if ($this->main->getUserLoginAttempts($player) >= $config->getNested("settings.maxLoginAttempts")) {
                            $this->main->removeUserLoginAttempts($player);
                            $player->kick($config->getNested("kicks.tooManyLoginAttempts"), false);
                            return;
                        }
                        $this->main->addUserLoginAttempt($player, 1);
                        $player->sendMessage($config->getNested("messages.incorrectPassword"));
                        return;
                    }
                    $this->main->loginUser($player);
                    $player->sendMessage($config->getNested("messages.successfulLogin"));
                    $this->main->removeUserLoginAttempts($player);
                }
            }
        }
    }
}