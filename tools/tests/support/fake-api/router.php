<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/website Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

/*
 * Router of a fake East Website API, run by `php -S` in the end to end tests of the CLI. It records each received
 * request (one JSON document per line) in the file defined by the environment variable FAKE_API_LOG.
 */

$method = $_SERVER['REQUEST_METHOD'];
$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$body = (string) file_get_contents('php://input');
$headers = [];
foreach ($_SERVER as $name => $value) {
    if (str_starts_with($name, 'HTTP_')) {
        $headers[strtolower(str_replace('_', '-', substr($name, 5)))] = $value;
    }
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$log = getenv('FAKE_API_LOG');
if (is_string($log) && '' !== $log) {
    file_put_contents(
        $log,
        json_encode(
            [
                'method' => $method,
                'path' => $path,
                'query' => $_SERVER['QUERY_STRING'] ?? '',
                'headers' => $headers,
                'contentType' => $contentType,
                'body' => $body,
                'post' => $_POST,
                'files' => [
                    'name' => $_FILES['media']['name'] ?? null,
                    'size' => $_FILES['media']['size'] ?? null,
                    'type' => $_FILES['media']['type'] ?? null,
                ],
            ]
        ) . "\n",
        FILE_APPEND
    );
}

$respond = static function (int $status, array $data, array $extraHeaders = []): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    foreach ($extraHeaders as $name => $value) {
        header($name . ': ' . $value);
    }

    echo json_encode($data);
    exit;
};

$error = static fn (int $code, string $message): array => ['meta' => ['error' => true], 'data' => ['code' => $code, 'message' => $message]];
$b64 = static fn (string $data): string => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

if ('POST' === $method && '/api/v1/login' === $path) {
    $credentials = json_decode($body, true);
    if (!is_array($credentials) || 'key:me@example.com' !== ($credentials['username'] ?? null) || 'secret' !== ($credentials['token'] ?? null)) {
        $respond(401, $error(401, 'Invalid credentials.'), ['WWW-Authenticate' => 'Bearer']);
    }

    $jwt = $b64('{"alg":"none"}') . '.' . $b64((string) json_encode(['exp' => time() + 3600, 'username' => 'key:me@example.com'])) . '.sig';
    $respond(200, ['meta' => ['error' => false], 'data' => ['token' => $jwt]]);
}

$authorization = $headers['authorization'] ?? '';
$publicRoutes = ['/api/v1/content/default', '/api/v1/posts'];
if (!in_array($path, $publicRoutes, true) && !str_starts_with($authorization, 'Bearer ')) {
    $respond(401, $error(401, 'JWT Token not found'), ['WWW-Authenticate' => 'Bearer']);
}

// Only a body sent with the bare media type is read, like the real API
$json = 'application/json' === $contentType ? (json_decode($body, true) ?? []) : [];

switch (true) {
    case 'GET' === $method && '/api/v1/admin/tag/missing' === $path:
        $respond(404, $error(404, 'Tag not found'));
        // no break
    case 'POST' === $method && preg_match('#^/api/v1/admin/(tag|content|type|media)/new$#', $path, $m):
        $respond(302, [], ['Location' => '/api/v1/admin/' . $m[1] . '/' . $m[1] . '-1']);
        // no break
    case 'GET' === $method && preg_match('#^/api/v1/admin/(tag|content|type|media)/([a-z0-9-]+)$#', $path, $m):
        $respond(200, ['meta' => ['id' => $m[2]], 'data' => ['@class' => $m[1], 'id' => $m[2], 'name' => 'created']]);
        // no break
    case 'PUT' === $method && preg_match('#^/api/v1/admin/(tag|content|type|media)/([a-z0-9-]+)$#', $path, $m):
        $respond(200, ['meta' => ['id' => $m[2]], 'data' => ['@class' => $m[1], 'id' => $m[2], 'received' => $json]]);
        // no break
    case 'GET' === $method && '/api/v1/admin/tags' === $path:
        $respond(200, ['meta' => ['totalPages' => 1, 'page' => 1, 'count' => 1], 'data' => [['id' => 'tag-1', 'name' => 'created', 'slug' => 'created', 'isHighlighted' => false]]]);
        // no break
    case 'DELETE' === $method && preg_match('#^/api/v1/admin/tag/([a-z0-9-]+)/delete$#', $path, $m):
        $respond(200, ['meta' => ['id' => $m[1], 'deleted' => 'success'], 'data' => ['id' => $m[1]]]);
        // no break
    case 'GET' === $method && '/api/v1/content/default' === $path:
        $respond(200, ['meta' => ['id' => 'home'], 'data' => ['id' => 'home', 'title' => 'Home']]);
        // no break
    case 'POST' === $method && preg_match('#^/api/v1/post/([a-z0-9-]+)/comment$#', $path, $m):
        $respond(302, [], ['Location' => '/api/v1/post/' . $m[1] . '?id=comment-1']);
        // no break
    default:
        $respond(404, $error(404, 'Not found'));
}
