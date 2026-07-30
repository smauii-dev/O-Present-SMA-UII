<?php

namespace App\Controllers\Api;

trait ApiResponse
{
    protected function success($data = null, string $message = 'OK', int $code = 200)
    {
        return $this->response->setJSON([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ])->setStatusCode($code);
    }

    protected function error(string $message = 'Error', int $code = 400, $errors = null)
    {
        $payload = [
            'status' => 'error',
            'message' => $message,
        ];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        return $this->response->setJSON($payload)->setStatusCode($code);
    }

    protected function getInput(string $key, $default = null)
    {
        // Try JSON body first (works for POST, PUT, PATCH)
        $rawBody = $this->request->getBody();
        if ($rawBody) {
            $json = json_decode($rawBody, true);
            if (is_array($json) && array_key_exists($key, $json)) {
                return $json[$key];
            }
        }

        // Try POST form data
        $post = $this->request->getPost($key);
        if ($post !== null) {
            return $post;
        }

        // Try query string
        $get = $this->request->getGet($key);
        if ($get !== null) {
            return $get;
        }

        return $default;
    }

    private function getAllInput(): array
    {
        $rawBody = $this->request->getBody();
        if ($rawBody) {
            $json = json_decode($rawBody, true);
            if (is_array($json)) {
                return $json;
            }
        }
        return $this->request->getPost() ?? [];
    }

    protected function paginated($data, int $total, int $page, int $perPage, string $message = 'OK')
    {
        return $this->response->setJSON([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ])->setStatusCode(200);
    }
}
