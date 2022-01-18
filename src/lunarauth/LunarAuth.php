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

namespace lunarauth;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
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
    task\LoginTask,
    task\MessageTask
};

use SQLite3;

use function mysqli_connect_errno;
use function mysqli_connect_error;
use function mysqli_connect;
use function mysqli_query;
use function mysqli_close;
use function mysqli_fetch_array;

use function strtolower;
use function hash;

final class LunarAuth extends PluginBase
{

    public static $instance;

    private $databaseMySQL;

    private $connectedToMySQL;

    private $provider;

    private $usersDatabase;

    public $hash = "sha512";

    public $prefix = "[LunarAuth]";

    public $authenticated = array();

    public $loginAttempts = array();

    public $loginTime = array();

    public $loginMessageTime = array();

    public static function getInstance(): LunarAuth
    {
        return self::$instance;
    }

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

    private function registerListeners()
    {
        Server::getInstance()->getPluginManager()->registerEvents(new ChatAuthListener($this), $this);
        Server::getInstance()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    private function scheduleTasks()
    {
        Server::getInstance()->getScheduler()->scheduleRepeatingTask(new LoginTask($this), 20);
        Server::getInstance()->getScheduler()->scheduleRepeatingTask(new MessageTask($this), 20);
    }

    private function connectToMySQL()
    {
        $config = $this->getConfig();
        $this->databaseMySQL = mysqli_connect($config->getNested("mysql.ip"), $config->getNested("mysql.user"), $config->getNested("mysql.password"), $config->getNested("mysql.database"), $config->getNested("mysql.port"));
        if (mysqli_connect_errno()) {
            $this->connectedToMySQL = false;
            $this->getLogger()->critical("Can't connect to database: " . mysqli_connect_error());
        } else {
            $this->connectedToMySQL = true;
            $this->getLogger()->info("Successfully connected to MySQL database!");
        }
    }

    public function onEnable()
    {
        self::$instance = $this;
        $this->saveDefaultConfig();
        $this->registerCommands();
        $this->registerListeners();
        $this->scheduleTasks();
        if (!(is_dir($this->getDataFolder() . "data"))) {
            @mkdir($this->getDataFolder() . "data");
        }
        if ($this->getConfig()->getNested("settings.provider") == "sqlite3") {
            $this->provider = "SQLite3";
            $this->usersDatabase = new SQLite3($this->getDataFolder() . "data/users.db");
            $this->usersDatabase->query("CREATE TABLE IF NOT EXISTS `users` (`username` VARCHAR(16) NOT NULL, `password` TEXT NOT NULL, `address` TEXT NOT NULL);");
        } elseif ($this->getConfig()->getNested("settings.provider") == "mysql") {
            if ($this->getConfig()->getNested("mysql.enabled") == true) {
                $this->provider = "MySQL";
                $this->connectToMySQL();
                if ($this->connectedToMySQL == true) {
                    $this->usersDatabase = $this->databaseMySQL;
                    mysqli_query($this->usersDatabase, "CREATE TABLE IF NOT EXISTS `users` (`username` VARCHAR(16) NOT NULL, `password` TEXT NOT NULL, `address` TEXT NOT NULL);");
                } else {
                    $this->setEnabled(false);
                    return;
                }
            } else {
                $this->getLogger()->critical("You have selected MySQL as provider, but not enabled it. Disabling plugin.");
                $this->setEnabled(false);
                return;
            }
        } elseif ($this->getConfig()->getNested("settings.provider") == "json") {
            $this->provider = "JSON";
            $this->usersDatabase = new Config($this->getDataFolder() . "data/users.json", Config::JSON);
        } elseif ($this->getConfig()->getNested("settings.provider") == "yaml") {
            $this->provider = "YAML";
            $this->usersDatabase = new Config($this->getDataFolder() . "data/users.yml", Config::YAML);
        } else {
            $this->getLogger()->critical("Undefined provider: " . $this->getConfig()->getNested("settings.provider") . " Disabling plugin.");
            $this->setEnabled(false);
            return;
        }
        $this->getLogger()->debug("Using provider: " . $this->provider);
    }

    private function getUsersDatabase()
    {
        return $this->usersDatabase;
    }

    private function getProvider(): string
    {
        return $this->provider;
    }

    private function isConnectedToMySQL(): bool
    {
        return $this->connectedToMySQL;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function checkUserData(string $username)
    {
        $username = strtolower($username);
        $database = $this->getUsersDatabase();
        if ($this->getProvider() == "SQLite3") {
            $query = $database->query("SELECT * FROM `users` WHERE `username` = '" . $username . "';");
            $result = $query->fetchArray(SQLITE3_ASSOC);
            if (!($result)) {
                $database->exec("INSERT INTO `users` VALUES ('" . $username . "', '0', '0')");
            }
        } elseif ($this->getProvider() == "MySQL") {
            $query = mysqli_query($database, "SELECT * FROM `users` WHERE `username` = '" . $username . "';");
            $result = mysqli_fetch_array($query);
            if (!($result)) {
                mysqli_query($database, "INSERT INTO `users` VALUES ('" . $username . "', '0', '0')");
            }
        }
    }

    public function setUserPassword(string $username, string $password)
    {
        if ($this->getConfig()->getNested("settings.encrypt") == true) {
            $password = hash($this->getHash(), $password);
        }
        $username = strtolower($username);
        $this->checkUserData($username);
        $database = $this->getUsersDatabase();
        if ($this->getProvider() == "SQLite3") {
            $database->exec("UPDATE `users` SET `password` = '" . $password . "' WHERE `username` = '" . $username . "';");
        } elseif ($this->getProvider() == "MySQL") {
            mysqli_query($database, "UPDATE `users` SET `password` = '" . $password . "' WHERE `username` = '" . $username . "';");
        } elseif ($this->getProvider() == "JSON") {
            $database->setNested($username . ".password", $password);
            $database->save();
        } elseif ($this->getProvider() == "YAML") {
            $database->setNested($username . ".password", $password);
            $database->save();
        }
    }

    public function setUserAddress(string $username, string $address)
    {
        $username = strtolower($username);
        $this->checkUserData($username);
        $database = $this->getUsersDatabase();
        if ($this->getProvider() == "SQLite3") {
            $database->exec("UPDATE `users` SET `address` = '" . $address . "' WHERE `username` = '" . $username . "';");
        } elseif ($this->getProvider() == "MySQL") {
            mysqli_query($database, "UPDATE `users` SET `address` = '" . $address . "' WHERE `username` = '" . $username . "';");
        } elseif ($this->getProvider() == "JSON") {
            $database->setNested($username . ".address", $address);
            $database->save();
        } elseif ($this->getProvider() == "YAML") {
            $database->setNested($username . ".address", $address);
            $database->save();
        }
    }

    public function getUserPassword(string $username): string
    {
        $username = strtolower($username);
        $database = $this->getUsersDatabase();
        $password = "0";
        if ($this->getProvider() == "SQLite3") {
            $password = $database->querySingle("SELECT `password` FROM `users` WHERE `username` = '" . $username . "';");
        } elseif ($this->getProvider() == "MySQL") {
            $query = mysqli_query($database, "SELECT * FROM `users` WHERE `username` = '" . $username . "';");
            $result = mysqli_fetch_array($query);
            $password = $result["password"];
        } elseif ($this->getProvider() == "JSON") {
            $password = $database->getNested($username . ".password");
        } elseif ($this->getProvider() == "YAML") {
            $password = $database->getNested($username . ".password");
        }
        return $password;
    }

    public function getUserAddress(string $username): string
    {
        $username = strtolower($username);
        $database = $this->getUsersDatabase();
        $address = "0";
        if ($this->getProvider() == "SQLite3") {
            $address = $database->querySingle("SELECT `address` FROM `users` WHERE `username` = '" . $username . "';");
        } elseif ($this->getProvider() == "MySQL") {
            $query = mysqli_query($database, "SELECT * FROM `users` WHERE `username` = '" . $username . "';");
            $result = mysqli_fetch_array($query);
            $address = $result["address"];
        } elseif ($this->getProvider() == "JSON") {
            $address = $database->getNested($username . ".address");
        } elseif ($this->getProvider() == "YAML") {
            $address = $database->getNested($username . ".address");
        }
        return $address;
    }

    public function isUserRegistered(string $username): bool
    {
        $username = strtolower($username);
        $database = $this->getUsersDatabase();
        $bool = null;
        if ($this->getProvider() == "SQLite3") {
            $query = $database->query("SELECT * FROM `users` WHERE `username` = '" . $username . "';");
            $result = $query->fetchArray(SQLITE3_ASSOC);
            if (!($result) or $result["password"] == "0") {
                $bool = false;
            } else {
                $bool = true;
            }
        } elseif ($this->getProvider() == "MySQL") {
            $query = mysqli_query($database, "SELECT * FROM `users` WHERE `username` = '" . $username . "';");
            $result = mysqli_fetch_array($query);
            if (!($result) or $result["password"] == "0") {
                $bool = false;
            } else {
                $bool = true;
            }
        } elseif ($this->getProvider() == "JSON") {
            if (!($database->exists($username))) {
                $bool = false;
            } else {
                $bool = true;
            }
        } elseif ($this->getProvider() == "YAML") {
            if (!($database->exists($username))) {
                $bool = false;
            } else {
                $bool = true;
            }
        }
        return $bool;
    }

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

    public function authenticateUser(Player $player, bool $bool)
    {
        if ($bool == true) {
            $event = new PlayerAuthorizationEvent($player);
            Server::getInstance()->getPluginManager()->callEvent($event);
            if ($event->isCancelled()) {
                return;
            }
        }
        $username = strtolower($player->getName());
        $this->authenticated[$username] = $bool;
    }

    public function removeAuthenticatedUser(Player $player)
    {
        $username = strtolower($player->getName());
        if (isset($this->authenticated[$username])) {
            unset($this->authenticated[$username]);
        }
    }

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

    public function addUserLoginAttempt(Player $player, int $value)
    {
        $username = strtolower($player->getName());
        $attempts = $this->getUserLoginAttempts($player);
        $this->loginAttempts[$username] = $attempts + $value;
    }

    public function removeUserLoginAttempts(Player $player)
    {
        $username = strtolower($player->getName());
        if (isset($this->loginAttempts[$username])) {
            unset($this->loginAttempts[$username]);
        }
    }

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

    public function addUserLoginTime(Player $player, int $value)
    {
        $username = strtolower($player->getName());
        $time = $this->getUserLoginTime($player);
        $this->loginTime[$username] = $time + $value;
    }

    public function removeUserLoginTime(Player $player)
    {
        $username = strtolower($player->getName());
        if (isset($this->loginTime[$username])) {
            unset($this->loginTime[$username]);
        }
    }

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

    public function addUserLoginMessageTime(Player $player, int $value)
    {
        $username = strtolower($player->getName());
        $time = $this->getUserLoginMessageTime($player);
        $this->loginMessageTime[$username] = $time + $value;
    }

    public function removeUserLoginMessageTime(Player $player)
    {
        $username = strtolower($player->getName());
        if (isset($this->loginMessageTime[$username])) {
            unset($this->loginMessageTime[$username]);
        }
    }
    
    public function registerUser(Player $player, string $password)
    {
        if ($this->getConfig()->getNested("settings.encrypt") == true) {
            $password = hash($this->getHash(), $password);
        }
        $username = strtolower($player->getName());
        $address = $player->getAddress();
        $database = $this->getUsersDatabase();
        if ($this->getProvider() == "SQLite3") {
            $database->exec("INSERT INTO `users` VALUES ('" . $username . "', '" . $password . "', '" . $address . "');");
        } elseif ($this->getProvider() == "MySQL") {
            mysqli_query($database, "INSERT INTO `users` VALUES ('" . $username . "', '" . $password . "', '" . $address . "');");
        } elseif ($this->getProvider() == "JSON") {
            $database->setNested($username . ".password", $password);
            $database->setNested($username . ".address", $address);
            $database->save();
        } elseif ($this->getProvider() == "YAML") {
            $database->setNested($username . ".password", $password);
            $database->setNested($username . ".address", $address);
            $database->save();
        }
        $this->authenticateUser($player, true);
    }

    public function loginUser(Player $player)
    {
        $username = strtolower($player->getName());
        $address = $player->getAddress();
        $this->setUserAddress($username, $address);
        $this->authenticateUser($player, true);
    }

    public function changeUserPassword(Player $player, string $password)
    {
        $username = strtolower($player->getName());
        $this->setUserPassword($username, $password);
    }

    public function removeUser(string $username)
    {
        $username = strtolower($username);
        $database = $this->getUsersDatabase();
        if ($this->getProvider() == "SQLite3") {
            $database->exec("DELETE FROM `users` WHERE `username` = '" . $username . "';");
        } elseif ($this->getProvider() == "MySQL") {
            mysqli_query($database, "DELETE FROM `users` WHERE `username` = '" . $username . "';");
        } elseif ($this->getProvider() == "JSON") {
            $database->remove($username);
            $database->save();
        } elseif ($this->getProvider() == "YAML") {
            $database->remove($username);
            $database->save();
        }
    }

    public function onDisable()
    {
        $database = $this->getUsersDatabase();
        if ($this->getProvider() == "SQLite3") {
            $database->close();
        } elseif ($this->getProvider() == "MySQL") {
            if ($this->isConnectedToMySQL() == true) {
                mysqli_close($database);
            }
        } elseif ($this->getProvider() == "JSON") {
            $database->save();
        } elseif ($this->getProvider() == "YAML") {
            $database->save();
        }
    }
}