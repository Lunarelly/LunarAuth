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

namespace lunarauth\provider;

use lunarauth\LunarAuth;

use function mysqli_connect_errno;
use function mysqli_connect_error;
use function mysqli_connect;
use function mysqli_query;
use function mysqli_close;
use function mysqli_fetch_array;

use function strtolower;

class MySQLDataProvider implements DataProvider
{

    /**
     * @var LunarAuth
     */
    private $main;

    /**
     * @var mixed
     */
    private $database;

    /**
     * @var boolean
     */
    private $connectedToMySQL;

    /**
     * @return void
     */
    private function connectToMySQL()
    {
        $config = $this->main->getConfig();
        $this->database = mysqli_connect
        (
            $config->getNested("mysql.ip"),
            $config->getNested("mysql.user"),
            $config->getNested("mysql.password"),
            $config->getNested("mysql.database"),
            $config->getNested("mysql.port")
        );

        if (mysqli_connect_errno()) {
            $this->connectedToMySQL = false;
            $this->main->getLogger()->critical("Can't connect to database: " . mysqli_connect_error());
        } else {
            $this->connectedToMySQL = true;
            $this->main->getLogger()->info("Successfully connected to MySQL database!");
        }
    }

    /**
     * @return bool
     */
    private function isConnectedToMySQL(): bool
    {
        return $this->connectedToMySQL;
    }

    /**
     * @param LunarAuth $main
     */
    public function __construct(LunarAuth $main)
    {
        $this->main = $main;

        $this->connectToMySQL();
        if ($this->isConnectedToMySQL()) {
            mysqli_query($this->database, "CREATE TABLE IF NOT EXISTS `users` (`username` VARCHAR(16) NOT NULL, `password` TEXT NOT NULL, `address` TEXT NOT NULL, `clientsecret` TEXT NOT NULL);");
        } else {
            $this->main->setDataProvider(new NullDataProvider($this->main));
            $this->main->getLogger()->critical("Connection to MySQL failed. Disabling plugin.");
            $this->main->setEnabled(false);
        }
    }

    /**
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param string $username
     * @return void
     */
    public function checkUserData(string $username)
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $query = mysqli_query($database, "SELECT * FROM `users` WHERE `username` = '" . $username . "';");
        $result = mysqli_fetch_array($query);
        if (!($result)) {
            mysqli_query($database, "INSERT INTO `users` VALUES ('" . $username . "', '0', '0', '0')");
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @return void
     */
    public function setUserPassword(string $username, string $password)
    {
        $username = strtolower($username);
        $this->checkUserData($username);
        $database = $this->getDatabase();

        mysqli_query($database, "UPDATE `users` SET `password` = '" . $password . "' WHERE `username` = '" . $username . "';");
    }

    /**
     * @param string $username
     * @param string $address
     * @return void
     */
    public function setUserAddress(string $username, string $address)
    {
        $username = strtolower($username);
        $this->checkUserData($username);
        $database = $this->getDatabase();

        mysqli_query($database, "UPDATE `users` SET `address` = '" . $address . "' WHERE `username` = '" . $username . "';");
    }

    /**
     * @param string $username
     * @param string $clientSecret
     * @return void
     */
    public function setUserClientSecret(string $username, string $clientSecret)
    {
        $username = strtolower($username);
        $this->checkUserData($username);
        $database = $this->getDatabase();

        mysqli_query($database, "UPDATE `users` SET `clientsecret` = '" . $clientSecret . "' WHERE `username` = '" . $username . "';");
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserPassword(string $username): string
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $query = mysqli_query($database, "SELECT * FROM `users` WHERE `username` = '" . $username . "';");
        $result = mysqli_fetch_array($query);

        return $result["password"];
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserAddress(string $username): string
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $query = mysqli_query($database, "SELECT * FROM `users` WHERE `username` = '" . $username . "';");
        $result = mysqli_fetch_array($query);

        return $result["address"];
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserClientSecret(string $username): string
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $query = mysqli_query($database, "SELECT * FROM `users` WHERE `username` = '" . $username . "';");
        $result = mysqli_fetch_array($query);

        return $result["clientsecret"];
    }

    /**
     * @param string $username
     * @return bool
     */
    public function isUserRegistered(string $username): bool
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $query = mysqli_query($database, "SELECT * FROM `users` WHERE `username` = '" . $username . "';");
        $result = mysqli_fetch_array($query);

        if (!($result) or $result["password"] == "0") {
            $bool = false;
        } else {
            $bool = true;
        }
        return $bool;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $address
     * @param string $clientSecret
     * @return void
     */
    public function registerUser(string $username, string $password, string $address, string $clientSecret)
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        mysqli_query($database, "INSERT INTO `users` VALUES ('" . $username . "', '" . $password . "', '" . $address . "', '" . $clientSecret . "');");
    }

    /**
     * @param string $username
     * @return void
     */
    public function removeUser(string $username)
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        mysqli_query($database, "DELETE FROM `users` WHERE `username` = '" . $username . "';");
    }

    /**
     * @return void
     */
    public function closeDatabase()
    {
        if ($this->isConnectedToMySQL()) {
            mysqli_close($this->getDatabase());
        }
    }
}