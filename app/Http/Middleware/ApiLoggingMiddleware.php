<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {
        // Генерируем уникальный ID запроса, если его нет
        if (!$request->headers->has('X-Request-ID')) {
            $requestId = uniqid('req_', true);
            $request->headers->set('X-Request-ID', $requestId);
        }

        // Запускаем таймер
        $startTime = microtime(true);

        // Выполняем запрос
        $response = $next($request);

        // Добавляем X-Request-ID в ответ для отслеживания
        $response->headers->set('X-Request-ID', $request->header('X-Request-ID'));

        // Определяем, нужно ли логировать данный запрос
        if ($this->shouldLogRequest($request)) {
            // Рассчитываем время выполнения
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Формируем данные для лога
            $logData = $this->prepareLogData($request, $response, $duration, $startTime);

            // Записываем лог в нужный канал в зависимости от типа операции и кода ответа
            $this->writeLog($request, $response, $logData);
        }
        return $response;
    }

    /**
     * Определяет, нужно ли логировать запрос
     */
    protected function shouldLogRequest(Request $request): bool {
        // Логируем только API-запросы
        if (strpos($request->path(), 'api/') === 0) {
            // Можно добавить исключения для определенных эндпоинтов
            $excludedEndpoints = [
                'get-info/'
            ];

            if (in_array($request->path(), $excludedEndpoints)) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Определяет тип операции (создание, обновление, удаление или чтение)
     */
    protected function getOperationType(Request $request): string {
        $method = $request->method();

        if ($method === 'POST')
            return 'creation';
        else if ($method === 'PUT')
            return 'update';
        else if ($method === 'DELETE')
            return 'deletion';
        else
            return 'general';
    }

    /**
     * Готовит данные для лога
     */
    protected function prepareLogData(Request $request, $response, float $duration, float $startTime): array {
        return [
            // Данные запроса
            'request' => [
                'id' => $request->header('X-Request-ID'),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'route' => $request->route() ? $request->route()->getName() : null,
                'action' => $request->route() ? $request->route()->getActionName() : null,
                'headers' => $this->filterSensitiveData($request->headers->all(), 'headers'),
                'body' => $this->filterSensitiveData($request->all(), 'request'),
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ],

            // Данные ответа
            'response' => [
                'status_code' => $response->getStatusCode(),
                'status_text' => $this->getStatusText($response->getStatusCode()),
                'headers' => $this->filterSensitiveData($response->headers->all(), 'headers'),
                'body' => $request->method() == 'GET' ? null : $this->getResponseContent($response),
            ],

            // Данные о времени выполнения
            'performance' => [
                'start_time' => date('Y-m-d H:i:s.', (int)$startTime) . substr(fmod($startTime, 1), 2, 6),
                'end_time' => date('Y-m-d H:i:s.', time()) . substr(microtime(), 2, 6),
                'duration_ms' => $duration,
            ],

            // Данные о пользователе
            'user' => [
                'id' => auth()->user() ? auth()->user()->id : null,
                'email' => auth()->user() ? auth()->user()->email : null,
                'name' => auth()->user() ? auth()->user()->name : null,
            ],

            // Технические данные
            'technical' => [
                'memory_usage' => $this->getMemoryUsage(),
                'environment' => app()->environment(),
            ],

            // Общие метаданные лога
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Записывает лог в соответствующий канал
     */
    protected function writeLog(Request $request, $response, array $logData): void {
        $operationType = $this->getOperationType($request);
        $statusCode = $response->getStatusCode();
        $routeName = $request->route() ? ($request->route()->getName() ?? $request->path()) : $request->path();

        // Формируем сообщение для лога
        $message = sprintf(
            "[%s] %s %s - %d %s (%d ms)",
            strtoupper($request->method()),
            $routeName,
            $operationType,
            $statusCode,
            $this->getStatusText($statusCode),
            $logData['performance']['duration_ms']
        );

        // Выбираем канал в зависимости от типа операции и статуса ответа
        if ($statusCode >= 500) {
            // Ошибки сервера
            Log::channel('errors')->error($message, $logData);
        } elseif ($statusCode >= 400) {
            // Ошибки клиента
            Log::channel('errors')->warning($message, $logData);
        } else {
            // Успешные запросы по типу операции
            if ($operationType === 'creation') {
                Log::channel('creation')->info($message, $logData);
            } elseif ($operationType === 'update') {
                Log::channel('update')->info($message, $logData);
            } elseif ($operationType === 'deletion') {
                Log::channel('deletion')->info($message, $logData);
            } else {
                Log::channel('general')->info($message, $logData);
            }
        }
    }

    /**
     * Фильтрует чувствительные данные
     */
    protected function filterSensitiveData($data, string $context = 'general'): array {
        if (!is_array($data)) {
            return ['value' => $data];
        }

        $result = [];

        // Определяем чувствительные поля в зависимости от контекста
        $sensitiveFields = [
            'headers' => [
                'authorization',
                'cookie',
                'set-cookie',
                'x-xsrf-token',
                'x-csrf-token',
                'password',
                'token',
                'api-key',
            ],
            'request' => [
                'password',
                'password_confirmation',
                'current_password',
                'secret',
                'token',
                'api_key',
                'access_token',
                'refresh_token',
                'credit_card',
                'card_number',
                'cvv',
                'pin',
                'ssn',
            ],
            'general' => [
                'password',
                'token',
                'secret',
            ],
        ];

        $fieldsToFilter = $sensitiveFields[$context] ?? $sensitiveFields['general'];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            if (in_array($lowerKey, $fieldsToFilter) || strpos($lowerKey, 'password') !== false || strpos($lowerKey, 'token') !== false) {
                $result[$key] = '[СКРЫТО]';
            } elseif (is_array($value)) {
                $result[$key] = $this->filterSensitiveData($value, $context);
            } elseif (is_array($value) && count($value) === 1) {
                // Обработка массивов заголовков, где значения находятся в массиве с одним элементом
                $result[$key] = $this->filterSensitiveData($value, $context);
            } else {
                $result[$key] = is_array($value) && count($value) === 1 ? $value[0] : $value;
            }
        }

        return $result;
    }

    /**
     * Получает текстовое описание HTTP статус кода
     */
    protected function getStatusText(int $statusCode): string {
        $statusTexts = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
        ];

        return $statusTexts[$statusCode] ?? 'Unknown Status';
    }

    /**
     * Получает содержимое ответа для логирования
     */
    protected function getResponseContent($response) {
        // Получаем содержимое ответа
        if ($response instanceof SymfonyResponse) {
            $content = $response->getContent();
        } else {
            return '[Неизвестный формат ответа]';
        }

        // Для JSON-ответов декодируем и фильтруем
        $contentType = $response->headers->get('Content-Type', '');
        if (strpos($contentType, 'application/json') !== false) {
            try {
                $decoded = json_decode($content, true);
                return $decoded ? $this->filterSensitiveData($decoded, 'request') : '[JSON: ' . substr($content, 0, 100) . '...]';
            } catch (\Exception $e) {
                return '[Не удалось декодировать JSON]';
            }
        }

        // Для бинарных ответов возвращаем только тип
        if (in_array($contentType, [
            'application/pdf',
            'application/octet-stream',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/zip',
            'application/x-gzip',
        ]) || strpos($contentType, 'application/') === 0) {
            return '[Бинарное содержимое типа: ' . $contentType . ']';
        }

        // Для больших текстовых ответов возвращаем только часть
        if (strlen($content) > 1000) {
            return '[Текстовое содержимое, первые 1000 символов]: ' . substr($content, 0, 1000) . '...';
        }

        return $content;
    }

    /**
     * Получает использование памяти в читабельном формате
     */
    protected function getMemoryUsage(): string {
        $bytes = memory_get_usage(true);

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
