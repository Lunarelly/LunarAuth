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

namespace lunarelly\lunarauth\command;

use pocketmine\command\{
    Command,
    CommandSender,
    PluginIdentifiableCommand
};
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use lunarelly\lunarauth\LunarAuth;

use function strtolower;
use function preg_match;
use function str_replace;

class RegisterCommand extends Command implements PluginIdentifiableCommand
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

        $this->setDescription("Register command");
        $this->setPermission("lunarauth.command.register");
        $this->setUsage($this->main->getConfig()->getNested("usages.register"));
        $this->setAliases(["r", "reg"]);

        parent::__construct("register", $this->getDescription(), $this->getUsage(), $this->getAliases());
    }

    /**
     * @param CommandSender $sender
     * @param $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, $commandLabel, array $args): bool
    {
        if (!($this->testPermission($sender))) {
            return false;
        }

        if (!($sender instanceof Player)) {
            $sender->sendMessage(TextFormat::RED . "Only in-game!");
            return false;
        }

        $username = strtolower($sender->getName());
        $config = $this->main->getConfig();

        if ($this->main->isUserRegistered($username)) {
            $sender->sendMessage($config->getNested("messages.userAlreadyRegistered"));
            return false;
        }

        if ($this->main->isUserAuthenticated($sender)) {
            $sender->sendMessage($config->getNested("messages.userAlreadyLoggedIn"));
            return false;
        }

        if (empty($args) or !(isset($args[0])) or !(isset($args[1]))) {
            $sender->sendMessage($this->usageMessage);
            return false;
        }

        if (preg_match("/^[\x{0020}-\x{007E}]*$/", $args[0]) == 0 or preg_match("/^[\x{0020}-\x{007E}]*$/", $args[1]) == 0) {
            $sender->sendMessage($config->getNested("messages.invalidPasswordSymbols"));
            return false;
        }

        $minLength = $config->getNested("settings.minPasswordLength");
        $maxLength = $config->getNested("settings.maxPasswordLength");
        if (strlen($args[0]) < $minLength or strlen($args[0]) > $maxLength or strlen($args[1]) < $minLength or strlen($args[1]) > $maxLength) {
            $sender->sendMessage($config->getNested("messages.invalidPasswordLength"));
            return false;
        }

        if (!($args[0] === $args[1])) {
            $sender->sendMessage($config->getNested("messages.passwordsDoesNotMatch"));
            return false;
        }

        $password = $args[0];
        $this->main->registerUser($sender, $password);
        $sender->sendMessage(str_replace("{PASSWORD}", $password, $config->getNested("messages.successfulRegistration")));
        return true;
    }

    /**
     * @return LunarAuth
     */
    public function getPlugin(): LunarAuth
    {
        return $this->main;
    }
}