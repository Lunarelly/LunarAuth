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

namespace lunarauth\command;

use pocketmine\command\{
    Command,
    CommandSender,
    PluginIdentifiableCommand
};
use pocketmine\Player;
use lunarauth\LunarAuth;

use function strtolower;
use function hash;

class LoginCommand extends Command implements PluginIdentifiableCommand {

    public function __construct(LunarAuth $main) {
        $this->main = $main;
        $this->setDescription("Login command");
        $this->setPermission("lunarauth.command.login");
        $this->setUsage($this->main->getConfig()->getNested("usages.login"));
        $this->aliases = ["l", "log"];
        parent::__construct("login", $this->description, $this->usageMessage, $this->aliases);
    }

    public function execute(CommandSender $sender, $alias, array $args) {
        if(!($sender instanceof Player)) {
            return $sender->sendMessage("Only in-game!");
        }
        if(!($this->testPermission($sender))) {
            return false;
        }
        $username = strtolower($sender->getName());
        $config = $this->main->getConfig();
        if($this->main->isUserRegistred($username) == false) {
            return $sender->sendMessage($config->getNested("messages.userNotRegistred"));
        }
        if($this->main->isUserAuthenticated($sender) == true) {
            return $sender->sendMessage($config->getNested("messages.userAlreadyLoggedIn"));
        }
        if(empty($args) or !(isset($args[0]))) {
            return $sender->sendMessage($this->usageMessage);
        }
        if($this->main->getConfig()->getNested("settings.encrypt") == true) {
            $password = hash("sha512", $args[0]);
        } else {
            $password = $args[0];
        }
        if(!($password === $this->main->getUserPassword($username))) {
            if($this->main->getUserLoginAttempts($sender) >= $config->getNested("settings.maxLoginAttempts")) {
                $this->main->removeUserLoginAttempts($sender);
                return $sender->kick($config->getNested("kicks.tooManyLoginAttempts"), false);
            } else {
                $this->main->addUserLoginAttempt($sender, 1);
                return $sender->sendMessage($config->getNested("messages.incorrectPassword"));
            }
        }
        $this->main->loginUser($sender);
        $sender->sendMessage($config->getNested("messages.successfulLogin"));
        $this->main->removeUserLoginAttempts($sender);
        return true;
    }

    public function getPlugin() {
        return $this->main;
    }
}