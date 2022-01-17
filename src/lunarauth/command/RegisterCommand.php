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

class RegisterCommand extends Command implements PluginIdentifiableCommand {

    private $main;

    private $aliases;

    public function __construct(LunarAuth $main) {
        $this->main = $main;
        $this->setDescription("Register command");
        $this->setPermission("lunarauth.command.register");
        $this->setUsage($this->main->getConfig()->getNested("usages.register"));
        $this->aliases = ["r", "reg"];
        parent::__construct("register", $this->description, $this->usageMessage, $this->aliases);
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
        if($this->main->isUserRegistered($username) == true) {
            return $sender->sendMessage($config->getNested("messages.userAlreadyRegistered"));
        }
        if($this->main->isUserAuthenticated($sender) == true) {
            return $sender->sendMessage($config->getNested("messages.userAlreadyLoggedIn"));
        }
        if(empty($args) or !(isset($args[0])) or !(isset($args[1]))) {
            return $sender->sendMessage($this->usageMessage);
        }
        if(preg_match("/^[\x{0020}-\x{007E}]*$/", $args[0]) == 0 or preg_match("/^[\x{0020}-\x{007E}]*$/", $args[1]) == 0) {
            return $sender->sendMessage($config->getNested("messages.invalidPasswordSymbols"));
        }
        $minLength = $config->getNested("settings.minPasswordLength");
        $maxLength = $config->getNested("settings.maxPasswordLength");
        if(strlen($args[0]) < $minLength or strlen($args[0]) > $maxLength or strlen($args[1]) < $minLength or strlen($args[1]) > $maxLength) {
            return $sender->sendMessage($config->getNested("messages.invalidPasswordLength"));
        }
        if(!($args[0] === $args[1])) {
            return $sender->sendMessage($config->getNested("messages.passwordsDoesNotMatch"));
        }
        $password = $args[0];
        $this->main->registerUser($sender, $password);
        $sender->sendMessage(str_replace("{PASSWORD}", $password, $config->getNested("messages.successfulRegistration")));
        return true;
    }

    public function getPlugin() {
        return $this->main;
    }
}