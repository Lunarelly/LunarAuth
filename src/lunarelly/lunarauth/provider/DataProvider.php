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

namespace lunarelly\lunarauth\provider;

interface DataProvider
{

    /**
     * @return mixed
     */
    public function getDatabase();

    /**
     * @param string $username
     * @return void
     */
    public function checkUserData(string $username);

    /**
     * @param string $username
     * @param string $password
     * @return void
     */
    public function setUserPassword(string $username, string $password);

    /**
     * @param string $username
     * @param string $address
     * @return mixed
     */
    public function setUserAddress(string $username, string $address);

    /**
     * @param string $username
     * @param string $clientSecret
     * @return void
     */
    public function setUserClientSecret(string $username, string $clientSecret);

    /**
     * @param string $username
     * @return string
     */
    public function getUserPassword(string $username): string;

    /**
     * @param string $username
     * @return string
     */
    public function getUserAddress(string $username): string;

    /**
     * @param string $username
     * @return string
     */
    public function getUserClientSecret(string $username): string;

    /**
     * @param string $username
     * @return boolean
     */
    public function isUserRegistered(string $username): bool;

    /**
     * @param string $username
     * @param string $password
     * @param string $address
     * @param string $clientSecret
     * @return void
     */
    public function registerUser(string $username, string $password, string $address, string $clientSecret);

    /**
     * @param string $username
     * @return void
     */
    public function removeUser(string $username);

    /**
     * @return void
     */
    public function closeDatabase();
}