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

namespace lunarauth\command;

use pocketmine\command\{
    Command,
    CommandSender,
    PluginIdentifiableCommand
};
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use lunarauth\LunarAuth;

use function strtolower;
use function preg_match;
use function str_replace;

class ChangePasswordCommand extends Command implements PluginIdentifiableCommand
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

        $this->setDescription("Change password command");
        $this->setPermission("lunarauth.command.changepassword");
        $this->setUsage($this->main->getConfig()->getNested("usages.changepassword"));
        $this->setAliases(["cp", "chp", "ch"]);

        parent::__construct("changepassword", $this->description, $this->usageMessage, $this->getAliases());
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

        if (!($this->main->isUserRegistered($username))) {
            $sender->sendMessage($config->getNested("messages.userNotRegistered"));
            return false;
        }

        if (!($this->main->isUserAuthenticated($sender))) {
            $sender->sendMessage($config->getNested("messages.userNotLoggedIn"));
            return false;
        }

        if (empty($args) or !(isset($args[0])) or !(isset($args[1]))) {
            $sender->sendMessage($this->usageMessage);
            return false;
        }

        if ($this->main->getConfig()->getNested("settings.encrypt")) {
            $oldPassword = $this->main->hash($args[0]);
        } else {
            $oldPassword = $args[0];
        }
        $newPassword = $args[1];

        if (!($oldPassword === $this->main->getUserPassword($username))) {
            $sender->sendMessage($config->getNested("messages.incorrectPassword"));
            return false;
        }

        if (preg_match("/^[\x{0020}-\x{007E}]*$/", $newPassword) == 0) {
            $sender->sendMessage($config->getNested("messages.invalidPasswordSymbols"));
            return false;
        }

        $minLength = $this->main->getConfig()->getNested("settings.minPasswordLength");
        $maxLength = $this->main->getConfig()->getNested("settings.maxPasswordLength");

        if (strlen($newPassword) < $minLength or strlen($newPassword) > $maxLength) {
            $sender->sendMessage($config->getNested("messages.invalidPasswordLength"));
            return false;
        }

        $this->main->changeUserPassword($sender, $newPassword);
        $sender->sendMessage(str_replace("{PASSWORD}", $newPassword, $config->getNested("messages.successfulPasswordChange")));
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