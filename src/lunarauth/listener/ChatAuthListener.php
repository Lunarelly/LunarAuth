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

namespace lunarauth\listener;

use pocketmine\event\{
    Listener,
    player\PlayerChatEvent
};
use lunarauth\LunarAuth;

use function strtolower;
use function hash;
use function str_replace;
use function preg_match;

class ChatAuthListener implements Listener {

    public function __construct(LunarAuth $main) {
        $this->main = $main;
    }

    public function chatLogin(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $username = strtolower($player->getName());
        $config = $this->main->getConfig();
        if($config->getNested("settings.chatAuth") == true) {
            if($this->main->isUserAuthenticated($player) == true) {
                if($config->getNested("settings.noPasswordsInChat") == true) {
                    $message = explode(" ", $event->getMessage());
                    if($this->main->getConfig()->getNested("settings.encrypt") == true) {
                        $password = hash("sha512", $message[0]);
                    } else {
                        $password = $message[0];
                    }
                    if($password === $this->main->getUserPassword($username)) {
                        $event->setCancelled(true);
                        $player->sendMessage($config->getNested("messages.userAlreadyLoggedIn"));
                    }
                }
            } elseif($this->main->isUserAuthenticated($player) == false) {
                $event->setCancelled(true);
                $message = explode(" ", $event->getMessage());
                if($this->main->isUserRegistred($username) == false) {
                    if(empty($message) or !(isset($message[0])) or !(isset($message[1]))) {
                        return $player->sendMessage($config->getNested("usages.chatRegister"));
                    }
                    if(preg_match("/^[\x{0020}-\x{007E}]*$/", $message[0]) == 0 or preg_match("/^[\x{0020}-\x{007E}]*$/", $message[1]) == 0) {
                        return $player->sendMessage($config->getNested("messages.invalidPasswordSymbols"));
                    }
                    $minLength = $config->getNested("settings.minPasswordLength");
                    $maxLength = $config->getNested("settings.maxPasswordLength");
                    if(strlen($message[0]) < $minLength or strlen($message[0]) > $maxLength or strlen($message[1]) < $minLength or strlen($message[1]) > $maxLength) {
                        return $player->sendMessage($config->getNested("messages.invalidPasswordLength"));
                    }
                    if(!($message[0] === $message[1])) {
                        return $player->sendMessage($config->getNested("messages.passwordsDoesNotMatch"));
                    }
                    $password = $message[0];
                    $this->main->registerUser($player, $password);
                    $player->sendMessage(str_replace("{PASSWORD}", $password, $config->getNested("messages.successfulRegistration")));
                } else {
                    if(empty($message) or !(isset($message[0]))) {
                        return $player->sendMessage($config->getNested("usages.chatLogin"));
                    }
                    if($this->main->getConfig()->getNested("settings.encrypt") == true) {
                        $password = hash("sha512", $message[0]);
                    } else {
                        $password = $message[0];
                    }
                    if(!($password === $this->main->getUserPassword($username))) {
                        if($this->main->getUserLoginAttempts($player) >= $config->getNested("settings.maxLoginAttempts")) {
                            $this->main->removeUserLoginAttempts($player);
                            return $player->kick($config->getNested("kicks.tooManyLoginAttempts"), false);
                        } else {
                            $this->main->addUserLoginAttempt($player, 1);
                            return $player->sendMessage($config->getNested("messages.incorrectPassword"));
                        }
                    }
                    $this->main->loginUser($player);
                    $player->sendMessage($config->getNested("messages.successfulLogin"));
                    $this->main->removeUserLoginAttempts($player);
                }
            }
        }
    }
}