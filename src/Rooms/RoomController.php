<?php
namespace Api\Rooms;

class RoomController
{
    private $gateway;

    public function __construct(RoomGateway $gateway)
    {
        $this->gateway = $gateway;
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
        $room = $this->gateway->get($id);
        if (!$room) {
            http_response_code(404);
            echo json_encode(["message" => "Room not found!"]);
            return;
        }
        switch ($method) {
            case "GET":
                echo json_encode($room);
                break;
            case "PATCH":
                $data = (array) json_decode(file_get_contents('php://input'), true);
                $errors = $this->getValidationErrors($data, false);
                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }
                $rows = $this->gateway->update($room, $data);
                echo json_encode([
                    "message" => "room $id updated.",
                    "rows" => $rows,
                ]);
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                echo json_encode([
                    "message" => "room $id deleted.",
                    "rows" => $rows,
                ]);
                break;
            case "POST":

                if (isset($_FILES['image'])) {
                    $this->gateway->deleteImage($id);
                    $imageUploadResult = $this->gateway->uploadImage($id, $_FILES['image']);
                    if ($imageUploadResult) {
                        $room['image_path'] = $imageUploadResult;
                    }
                } else {
                    http_response_code(422);
                    echo json_encode(["error" => "No valid image file provided"]);
                    exit;
                }
                echo json_encode([
                    "message" => "room $id updated.",

                ]);
                break;

            default:
                http_response_code(405);
                header("Allow: GET,PATCH,DELETE");
                break;
        }
    }

    private function processCollectionRequest(string $method): void
    {
        switch ($method) {
            case "GET":
                echo json_encode($this->gateway->getAll());
                break;
            case "POST":
                $data = (array) json_decode(file_get_contents('php://input'), true);
                $errors = $this->getValidationErrors($data);
                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }
                $id = $this->gateway->create($data);
                http_response_code(201);
                echo json_encode([
                    "message" => "room added.",
                    "id" => $id,
                ]);
                break;
            default:
                http_response_code(405);
                header("Allow: GET,POST");
                break;
        }
    }

    private function getValidationErrors(array $data, bool $is_new = true): array
    {
        $errors = [];
        if ($is_new && empty($data['name'])) {
            $errors[] = 'name is required';
        }
        if (array_key_exists("size", $data)) {
            if (filter_var($data["size"], FILTER_VALIDATE_INT) === false) {
                $errors[] = 'size must be an integer';
            }
        }
        return $errors;
    }
}
