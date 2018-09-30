<?php

class DbConfig
{

    private $dbConnection = null;

    /**
     * @param $username
     * @param $password
     * @param null $host
     * @param null $port
     * @param null $dbname
     * @param null $unix_socket
     * @return null|PDO|void
     */
    public function createDbConnection(
        $username,
        $password,
        $host = null,
        $port = null,
        $dbname = null,
        $unix_socket = null
    )
    {
        $dsn = "mysql:charset=utf8mb4;";
        $dsn .= $this->getDsnFieldIfNotNull('dbname', $dbname);
        if (!is_null($unix_socket)) {
            $dsn .= "unix_socket=" . $unix_socket . ";";
        } else {
            $dsn .= $this->getDsnFieldIfNotNull('host', $host);
            $dsn .= $this->getDsnFieldIfNotNull('port', $port);
        }
        try {
            $this->dbConnection = new PDO($dsn, $username, $password);
        } catch (PDOException $e) {
        }
    }

    /**
     * @param $name
     * @param $value
     * @return string
     */
    private function getDsnFieldIfNotNull($name, $value)
    {
        if (!is_null($value)) {
            return $name . "=" . $value . ";";
        }
        return "";
    }

    /**
     * Return database connection
     * @return null|PDO
     */
    public function getDbConn()
    {
        if ($this->dbConnection instanceof PDO) {
            return $this->dbConnection;
        }
        return;
    }
}