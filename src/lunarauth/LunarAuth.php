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

namespace lunarauth;

use pocketmine\plugin\PluginBase;
use pocketmine\{
    Player,
    Server
};
use lunarauth\{
    command\ChangePasswordCommand,
    command\LoginCommand,
    command\LunarAuthCommand,
    command\RegisterCommand,
    command\RemoveUserCommand,
    command\UserInfoCommand,
    event\PlayerAuthorizationEvent,
    listener\ChatAuthListener,
    listener\EventListener,
    provider\DataProvider,
    provider\JSONDataProvider,
    provider\MySQLDataProvider,
    provider\NullDataProvider,
    provider\SQLite3DataProvider,
    provider\YAMLDataProvider,
    task\LoginTask,
    task\MessageTask
};

use function strtolower;
use function hash;

final class LunarAuth extends PluginBase
{

    /**
     * @var mixed
     */
    public static $instance;

    /**
     * @var DataProvider
     */
    private $dataProvider;

    /**
     * @var string
     */
    public $prefix = "[LunarAuth]";

    /**
     * @var array
     */
    public $authenticated = array();

    /**
     * @var array
     */
    public $loginAttempts = array();

    /**
     * @var array
     */
    public $loginTime = array();

    /**
     * @var array
     */
    public $loginMessageTime = array();

    /**
     * @return LunarAuth
     */
    public static function getInstance(): LunarAuth
    {
        return self::$instance;
    }

    /**
     * @return void
     */
    private function registerCommands()
    {
        Server::getInstance()->getCommandMap()->registerAll("LunarAuth", array
        (
            new ChangePasswordCommand($this),
            new LoginCommand($this),
            new LunarAuthCommand($this),
            new RegisterCommand($this),
            new RemoveUserCommand($this),
            new UserInfoCommand($this)
        ));
    }

    /**
     * @return void
     */
    private function registerListeners()
    {
        Server::getInstance()->getPluginManager()->registerEvents(new ChatAuthListener($this), $this);
        Server::getInstance()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    /**
     * @return void
     */
    private function scheduleTasks()
    {
        Server::getInstance()->getScheduler()->scheduleRepeatingTask(new LoginTask($this), 20);
        Server::getInstance()->getScheduler()->scheduleRepeatingTask(new MessageTask($this), 20);
    }

    /**
     * @return void
     */
    public function setDataProvider(DataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * @return void
     */
    public function onEnable()
    {
        self::$instance = $this;

        $this->saveDefaultConfig();
        $this->registerCommands();
        $this->registerListeners();
        $this->scheduleTasks();

        if (!(is_dir($this->getDataFolder()))) {
            @mkdir($this->getDataFolder());
        }

        switch (strtolower($this->getConfig()->getNested("settings.provider"))) {
            case "sqlite3":
                $this->dataProvider = new SQLite3DataProvider($this);
                $this->getLogger()->debug("Using provider: SQLite3");
                break;

            case "mysql":
                if ($this->getConfig()->getNested("mysql.enabled")) {
                    $this->dataProvider = new MySQLDataProvider($this);
                    $this->getLogger()->debug("Using provider: MySQL");
                } else {
                    $this->getLogger()->critical("You have selected MySQL as provider, but not enabled it. Disabling plugin.");
                    $this->setEnabled(false);
                    return;
                }
                break;

            case "json":
                $this->dataProvider = new JSONDataProvider($this);
                $this->getLogger()->debug("Using provider: JSON");
                break;

            case "yaml":
                $this->dataProvider = new YAMLDataProvider($this);
                $this->getLogger()->debug("Using provider: YAML");
                break;

            default:
                $this->dataProvider = new NullDataProvider($this);
                $this->getLogger()->critical("Unknown provider specified. Disabling plugin.");
                $this->setEnabled(false);
                break;
        }
    }

    /**
     * @return DataProvider
     */
    private function getDataProvider(): DataProvider
    {
        return $this->dataProvider;
    }

    /**
     * @param string $password
     * @return string
     */
    public function hash(string $password): string
    {
        return hash("sha512", $password);
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $username
     * @param string $password
     * @return void
     */
    public function setUserPassword(string $username, string $password)
    {
        if ($this->getConfig()->getNested("settings.encrypt")) {
            $password = $this->hash($password);
        }

        $username = strtolower($username);
        $provider = $this->getDataProvider();

        $provider->setUserPassword($username, $password);
    }

    /**
     * @param string $username
     * @param string $address
     * @return void
     */
    public function setUserAddress(string $username, string $address)
    {
        $username = strtolower($username);
        $provider = $this->getDataProvider();

        $provider->setUserAddress($username, $address);
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserPassword(string $username): string
    {
        $username = strtolower($username);
        $provider = $this->getDataProvider();

        return $provider->getUserPassword($username);
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserAddress(string $username): string
    {
        $username = strtolower($username);
        $provider = $this->getDataProvider();

        return $provider->getUserAddress($username);
    }

    /**
     * @param string $username
     * @return bool
     */
    public function isUserRegistered(string $username): bool
    {
        $username = strtolower($username);
        $provider = $this->getDataProvider();

        return $provider->isUserRegistered($username);
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isUserAuthenticated(Player $player): bool
    {
        $username = strtolower($player->getName());

        if (!(isset($this->authenticated[$username]))) {
            $bool = false;
        } else {
            $bool = $this->authenticated[$username];
        }
        return $bool;
    }

    /**
     * @param Player $player
     * @return void
     */
    public function authenticateUser(Player $player)
    {
        $event = new PlayerAuthorizationEvent($player);
        Server::getInstance()->getPluginManager()->callEvent($event);
        if ($event->isCancelled()) {
            return;
        }

        $username = strtolower($player->getName());
        $this->authenticated[$username] = true;
    }

    /**
     * @param Player $player
     * @return void
     */
    public function deauthenticateUser(Player $player)
    {
        $username = strtolower($player->getName());
        $this->authenticated[$username] = false;
    }

    /**
     * @param Player $player
     * @return void
     */
    public function removeAuthenticatedUser(Player $player)
    {
        $username = strtolower($player->getName());

        if (isset($this->authenticated[$username])) {
            unset($this->authenticated[$username]);
        }
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getUserLoginAttempts(Player $player): int
    {
        $username = strtolower($player->getName());

        if (!(isset($this->loginAttempts[$username]))) {
            $attempts = 0;
        } else {
            $attempts = $this->loginAttempts[$username];
        }
        return $attempts;
    }

    /**
     * @param Player $player
     * @param int $value
     * @return void
     */
    public function addUserLoginAttempt(Player $player, int $value)
    {
        $username = strtolower($player->getName());
        $attempts = $this->getUserLoginAttempts($player);
        $this->loginAttempts[$username] = $attempts + $value;
    }

    /**
     * @param Player $player
     * @return void
     */
    public function removeUserLoginAttempts(Player $player)
    {
        $username = strtolower($player->getName());

        if (isset($this->loginAttempts[$username])) {
            unset($this->loginAttempts[$username]);
        }
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getUserLoginTime(Player $player): int
    {
        $username = strtolower($player->getName());

        if (!(isset($this->loginTime[$username]))) {
            $time = 0;
        } else {
            $time = $this->loginTime[$username];
        }
        return $time;
    }

    /**
     * @param Player $player
     * @param int $value
     * @return void
     */
    public function addUserLoginTime(Player $player, int $value)
    {
        $username = strtolower($player->getName());
        $time = $this->getUserLoginTime($player);
        $this->loginTime[$username] = $time + $value;
    }

    /**
     * @param Player $player
     * @return void
     */
    public function removeUserLoginTime(Player $player)
    {
        $username = strtolower($player->getName());

        if (isset($this->loginTime[$username])) {
            unset($this->loginTime[$username]);
        }
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getUserLoginMessageTime(Player $player): int
    {
        $username = strtolower($player->getName());

        if (!(isset($this->loginMessageTime[$username]))) {
            $time = 0;
        } else {
            $time = $this->loginMessageTime[$username];
        }
        return $time;
    }

    /**
     * @param Player $player
     * @param int $value
     * @return void
     */
    public function addUserLoginMessageTime(Player $player, int $value)
    {
        $username = strtolower($player->getName());
        $time = $this->getUserLoginMessageTime($player);
        $this->loginMessageTime[$username] = $time + $value;
    }

    /**
     * @param Player $player
     * @return void
     */
    public function removeUserLoginMessageTime(Player $player)
    {
        $username = strtolower($player->getName());

        if (isset($this->loginMessageTime[$username])) {
            unset($this->loginMessageTime[$username]);
        }
    }

    /**
     * @param Player $player
     * @param string $password
     * @return void
     */
    public function registerUser(Player $player, string $password)
    {
        if ($this->getConfig()->getNested("settings.encrypt")) {
            $password = $this->hash($password);
        }

        $username = strtolower($player->getName());
        $address = $player->getAddress();
        $provider = $this->getDataProvider();

        $provider->registerUser($username, $password, $address);
        $this->authenticateUser($player);
    }

    /**
     * @param Player $player
     * @return void
     */
    public function loginUser(Player $player)
    {
        $username = strtolower($player->getName());
        $address = $player->getAddress();

        $this->setUserAddress($username, $address);
        $this->authenticateUser($player);
    }

    /**
     * @param Player $player
     * @param string $password
     * @return void
     */
    public function changeUserPassword(Player $player, string $password)
    {
        $username = strtolower($player->getName());
        $this->setUserPassword($username, $password);
    }

    /**
     * @param string $username
     * @return void
     */
    public function removeUser(string $username)
    {
        $username = strtolower($username);
        $provider = $this->getDataProvider();

        $provider->removeUser($username);
    }

    /**
     * @return void
     */
    public function onDisable()
    {
        $this->getDataProvider()->closeDatabase();
    }
}