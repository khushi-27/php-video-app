<?php

class VideoController
{
    public function __construct(private VideoGateway $videoGateway)
    {
    }

    public function processRequest(string $method, ?string $id): void
    {
        if ($id) {

            $this->processResourceRequest($method, $id);
        } else {

            $this->processCollectionRequest($method);
        }
    }

    private function processResourceRequest(string $method, string $id): void
    {
        $video = $this->videoGateway->get($id);

        if (!$video) {
            http_response_code(404);
            echo json_encode(["message" => "Video not found"]);
            return;
        }

        switch ($method) {
            case "GET":
                echo json_encode($video);
                break;

            case "PUT":

                $input = file_get_contents("php://input");

                if (empty($input)) {
                    http_response_code(422);
                    echo json_encode(["message" => "Enter some description to update!"]);
                    break;
                }

                $boundary = substr($input, 0, strpos($input, "\r\n"));
                $parts = array_slice(explode($boundary, $input), 1);

                $description = '';
                foreach ($parts as $part) {
                    if ($part == "--\r\n") {
                        break;
                    }

                    $part = ltrim($part, "\r\n");
                    list($headers, $body) = explode("\r\n\r\n", $part, 2);
                    $body = substr($body, 0, strlen($body) - 2); // Remove the trailing "\r\n"

                    // Parse the headers
                    $headers = explode("\r\n", $headers);
                    foreach ($headers as $header) {
                        if (strpos($header, 'Content-Disposition:') !== false) {
                            if (preg_match('/name="([^"]+)"/', $header, $matches)) {
                                $name = $matches[1];
                                if ($name === 'description') {
                                    $description = $body;
                                }
                            }
                        }
                    }
                }

                if (empty($description)) {
                    http_response_code(422);
                    echo json_encode(["message" => "Enter some description to update!", "description" => $description]);
                    break;
                }

                $rows = $this->videoGateway->update($id, $description);

                echo json_encode([
                    "message" => "Video description updated",
                    "rows" => $rows
                ]);
                break;

            case "DELETE":
                $rows = $this->videoGateway->delete($id);

                echo json_encode([
                    "message" => "Video deleted!",
                ]);
                break;

            default:
                http_response_code(405);
                header("Allow: GET, PUT, DELETE");
        }
    }

    private function processCollectionRequest(string $method): void
    {
        switch ($method) {
            case "POST":
                $file = $_FILES['userfile'];
                $fileName =  $_POST['name'] ?? "";
                $description =  $_POST['description'] ?? "";

                $errors = $this->getValidationErrors($file, $fileName, $description);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                if ($_FILES['userfile']['error'] > 0) {
                    http_response_code(400);
                    echo json_encode([
                        "message" => $_FILES['userfile']['error'],
                    ]);
                    break;
                } else {
                    $uploadDir = 'uploads/';
                    $uploadFile = $uploadDir . basename($fileName);

                    if (file_exists($uploadFile)) {

                        http_response_code(400);
                        echo json_encode([
                            "message" =>  $fileName . " already exists. ",
                        ]);
                    } else {
                        move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadFile);

                        $id = $this->videoGateway->create($fileName, $file['size'], $description);

                        http_response_code(201);
                        echo json_encode([
                            "message" => "Video Added Successfully!",
                            "id" => $id
                        ]);
                    }
                }
                break;

            default:
                http_response_code(405);
                header("Allow: POST");
        }
    }

    private function getValidationErrors(array $file, ?string $name, ?string $description): array
    {
        $errors = [];
        $allowedTypes = ['audio/mpeg', 'video/mp4', 'video/x-matroska'];
        $maxFileSize = 2000000; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Invalid file type! Allowed types are: " . implode(', ', $allowedTypes);
        }

        if (empty($name)) {
            $errors[] = 'File name is required!';
        }

        if ($file['size'] > $maxFileSize) {
            $errors[] = "File size exceeds the limit of " . ($maxFileSize / 1000000) . " MB.";
        }

        if (strlen($description) > 1000) {
            $errors[] = "Description exceeds the maximum length of 1000 characters.";
        }


        return $errors;
    }
}
