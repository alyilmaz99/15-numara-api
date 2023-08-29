<?php
namespace Api\User;

use Api\Database;
use PDO;

class UserGateway
{
    private PDO $connection;
    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM user";
        $stmt = $this->connection->query($sql);

        $data = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['status'] = (bool) $row['status'];
            $data[] = $row;
        }
        return $data;

    }

}
