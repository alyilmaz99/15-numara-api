<?php
namespace Api\Rooms;

use Api\Database;
use PDO;

class RoomGateway
{
    private PDO $connection;
    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM room";
        $stmt = $this->connection->query($sql);

        $data = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['is_available'] = (bool) $row['is_available'];
            $data[] = $row;
        }
        return $data;

    }
    public function create(array $data): string
    {
        $sql = "INSERT INTO room (name,size,is_available)VALUES (:name, :size, :is_available)";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(":name", $data["name"], PDO::PARAM_STR);
        $stmt->bindValue(":size", $data['size'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':is_available', $data['is_available'] ?? false, PDO::PARAM_BOOL);

        $stmt->execute();
        return $this->connection->lastInsertId();
    }
    public function get(string $id)
    {
        $sql = "SELECT * FROM room WHERE id=:id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data !== false) {
            $data["is_available"] = (bool) $data["is_available"];
        } else {
            $data = false; // Set to false explicitly when no data is found
        }

        return $data;
    }

    public function update(array $current, array $new): int
    {
        $sql = "UPDATE room SET name= :name, size= :size, is_available= :is_available WHERE id = :id";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(":name", $new["name"] ?? $current["name"], PDO::PARAM_STR);
        $stmt->bindValue(":size", $new["size"] ?? $current["size"], PDO::PARAM_INT);
        $stmt->bindValue(":is_available", $new["is_available"] ?? $current["is_available"], PDO::PARAM_BOOL);
        $stmt->bindValue(":id", $current["id"], PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function delete(string $id): int
    {
        $sql = "DELETE FROM room WHERE id = :id";
        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
    public function uploadImage(string $id, array $file): ?string
    {
        $uploadDir = 'uploads/';

        $fileName = uniqid() . '_' . $id . "_" . basename($file['name']);

        $destination = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $destination)) {

            $sql = "UPDATE room SET image_path = :image WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(":image", $destination, PDO::PARAM_STR);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            return $destination;
        }

        return null;
    }
    public function deleteImage($id)
    {
        $sql = "SELECT * FROM room WHERE id=:id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data['image_path'] != null) {
            unlink($data["image_path"]);
            echo json_encode([
                "message" => "old image deleted",
                "id" => $id,

            ]);
        }
    }

}