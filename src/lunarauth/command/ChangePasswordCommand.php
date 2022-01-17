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
use function preg_match;
use function str_replace;

class ChangePasswordCommand extends Command implements PluginIdentifiableCommand {

    private $main;

    private $aliases;

    public function __construct(LunarAuth $main) {
        $this->main = $main;
        $this->setDescription("Change password command");
        $this->setPermission("lunarauth.command.changepassword");
        $this->setUsage($this->main->getConfig()->getNested("usages.changepassword"));
        $this->aliases = ["cp", "chp", "ch"];
        parent::__construct("changepassword", $this->description, $this->usageMessage, $this->aliases);
    }

    public function execute(CommandSender $sender, $commandLabel, array $args) {
        if(!($sender instanceof Player)) {
            return $sender->sendMessage("Only in-game!");
        }
        if(!($this->testPermission($sender))) {
            return false;
        }
        $username = strtolower($sender->getName());
        $config = $this->main->getConfig();
        if($this->main->isUserRegistered($username) == false) {
            return $sender->sendMessage($config->getNested("messages.userNotRegistered"));
        }
        if($this->main->isUserAuthenticated($sender) == false) {
            return $sender->sendMessage($config->getNested("messages.userNotLoggedIn"));
        }
        if(empty($args) or !(isset($args[0])) or !(isset($args[1]))) {
            return $sender->sendMessage($this->usageMessage);
        }
        if($this->main->getConfig()->getNested("settings.encrypt") == true) {
            $oldPassword = hash($this->main->getHash(), $args[0]);
        } else {
            $oldPassword = $args[0];
        }
        $newPassword = $args[1];
        if(!($oldPassword === $this->main->getUserPassword($username))) {
            return $sender->sendMessage($config->getNested("messages.incorrectPassword"));
        }
        if(preg_match("/^[\x{0020}-\x{007E}]*$/", $newPassword) == 0) {
            return $sender->sendMessage($config->getNested("messages.invalidPasswordSymbols"));
        }
        $minLength = $this->main->getConfig()->getNested("settings.minPasswordLength");
        $maxLength = $this->main->getConfig()->getNested("settings.maxPasswordLength");
        if(strlen($newPassword) < $minLength or strlen($newPassword) > $maxLength) {
            return $sender->sendMessage($config->getNested("messages.invalidPasswordLength"));
        }
        $this->main->changeUserPassword($sender, $newPassword);
        $sender->sendMessage(str_replace("{PASSWORD}", $newPassword, $config->getNested("messages.successfulPasswordChange")));
        return true;
    }

    public function getPlugin() {
        return $this->main;
    }
}