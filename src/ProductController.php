<?php

class ProductController
{
    private $gateway;

    public function __construct(ProductGateway $gateway)
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
        $product = $this->gateway->get($id);
        if (!$product) {
            http_response_code(404);
            echo json_encode(["message" => "Product not found!"]);
            return;
        }
        switch ($method) {
            case "GET":
                echo json_encode($product);
                break;
            case "PATCH":
                $data = (array) json_decode(file_get_contents('php://input'), true);
                $errors = $this->getValidationErrors($data, false);
                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }
                $rows = $this->gateway->update($product, $data);
                echo json_encode([
                    "message" => "Product $id updated.",
                    "rows" => $rows,
                ]);
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                echo json_encode([
                    "message" => "Product $id deleted.",
                    "rows" => $rows,
                ]);
                break;
            case "POST":

                if (isset($_FILES['image'])) {
                    $this->gateway->deleteImage($id);
                    $imageUploadResult = $this->gateway->uploadImage($id, $_FILES['image']);
                    if ($imageUploadResult) {
                        $product['image_path'] = $imageUploadResult;
                    }
                } else {
                    http_response_code(422);
                    echo json_encode(["error" => "No valid image file provided"]);
                    exit;
                }
                echo json_encode([
                    "message" => "Product $id updated.",

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
                    "message" => "Product added.",
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