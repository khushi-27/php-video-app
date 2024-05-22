<?php

class VideoGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function create(string $name, int $size,  ?string $description): string
    {
        $sql = "INSERT INTO videos (fileName , size, description)
                VALUES (:fileName, :size, :description)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":fileName", $name, PDO::PARAM_STR);
        $stmt->bindValue(":size", $size, PDO::PARAM_INT);
        $stmt->bindValue(":description", $description, PDO::PARAM_STR);

        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    public function update(string $id, string $description): string
    {
        $sql = "UPDATE videos SET description = :description WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":description", $description, PDO::PARAM_STR);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount();
    }

    public function get(string $id): array | false
    {
        $sql = "SELECT *
                FROM videos
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    public function delete(string $id): int
    {
        $sql = "DELETE FROM videos
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount();
    }
}
