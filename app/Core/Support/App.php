<?php

/**
 * App class
 * @author LutviP19 <lutvip19@gmail.com>
 * @package Backend-PHP
 */

namespace App\Core\Support;


use Closure;
use Throwable;
use Exception;
use ReflectionClass;
use ReflectionException;

/**
 * App Container.
 */
class App
{
    /**
     * All registered keys.
     *
     * @var array
     */
    protected static $registry = [];

    /**
     * Get a value from the registry.
     *
     * @param string $key
     * @return mixed
     */
    public static function get($key): mixed
    {
        if (array_key_exists($key, self::$registry)) {
            if (self::$registry[$key] instanceof Closure) {
                self::$registry[$key] = self::$registry[$key]();
            }
            return self::$registry[$key];
        }

        if (class_exists($key)) {
            try {
                return self::resolve($key);
            } catch (ReflectionException $e) {
                throw new Exception("Failed to autowiring for class [{$key}]: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Check if a value exists in the registry.
     *
     * @param string $key
     * @return bool
     */
    public static function has($key): bool
    {
        return array_key_exists($key, self::$registry);
    }

    /**
     * Register a value into the App container.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function register($key, $value)
    {
        self::$registry[$key] = $value;
    }

    /**
     * Menghapus service tertentu dari container jika dibutuhkan (Opsional/Utilitas)
     * 
     * @param string $key
     * @return void
     */
    public static function unregister(string $key): void
    {
        if (self::has($key)) {
            unset(self::$registry[$key]);
        }
    }

    /**
     * Fitur Baru: Mendaftarkan service dengan skema Singleton (Lazy Loading)
     * Objek tidak akan dibuat sebelum fungsi App::get() dipanggil pertama kali.
     * 
     * @param string $key Nama alias service
     * @param Closure $callback Fungsi pembuat objek instansiasi
     * @return void
     */
    public static function singleton(string $key, Closure $callback): void
    {
        self::$registry[$key] = $callback;
    }

    /**
     * Autowiring using Reflection API (Automatic Dependency Resolution)
     */
    private static function resolve(string $className): mixed
    {
        $reflector = new ReflectionClass($className);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class [{$className}] is not an instantiable class.");
        }

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new Exception("Cannot resolve parameter [{$parameter->getName()}] in class [{$className}] because it does not have a valid class type.");
            }

            $dependencyClassName = $type->getName();
            $dependencies[] = self::get($dependencyClassName);
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Execute (Invoke) a method on a specific object by autowiring parameters.
     * 
     * @param array|callable $callback Format: [$objectInstance, 'methodName']
     * @param array $routeParams Dynamic parameters of the URL (e.g. ['id' => 77])
     * @return mixed The result of executing the method
     * @throws Exception
     */
    public static function call(array|callable $callback, array $routeParams = []): mixed
    {
        if (!is_array($callback)) {
            return call_user_func($callback, $routeParams);
        }

        [$instance, $method] = $callback;
        
        try {
            $reflectionMethod = new \ReflectionMethod($instance, $method);
            $parameters = $reflectionMethod->getParameters();
            $arguments = [];

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                $paramName = $parameter->getName();
                
                if ($type && !$type->isBuiltin()) {
                    $dependencyClass = $type->getName();
                    $arguments[] = self::get($dependencyClass);
                    continue;
                }
                
                if (array_key_exists($paramName, $routeParams)) {
                    $arguments[] = $routeParams[$paramName];
                    continue;
                }

                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new \Exception("Could not resolve parameter [\${$paramName}] on method [{$method}].");
            }

            return $reflectionMethod->invokeArgs($instance, $arguments);

        } catch (\ReflectionException $e) {
            throw new \Exception("Failed to autowiring on method: " . $e->getMessage());
        }
    }

    /**
     * Resolve External API Config for GoHttpClient.
     * * @param string $key eg: 'external_api_1'
     * @param array $dynamicOptions Dynamic parameters such as ['body' => [...], 'headers' => [...]]
     * @return array
     */
    public static function externalApi(string $key, array $dynamicOptions = []): array
    {
        try {
            // Get all routing config (config/external-api.php)
            $configs = self::get("routing_external_api");

            if (!$configs) {
                $messageErr = "App::registy[routing_external_api] was not registered." . PHP_EOL;
                if (config("app.debug")) {
                    \write_log(
                        "error",
                        [
                            "key" => $key,
                            "message" => $messageErr,
                        ],
                        "App\Core\Support\App.externalApi",
                        false
                    );
                }
                throw new \Exception($messageErr);
            }

            if (!isset($configs[$key])) {
                $messageErr = "External API configuration with key [$key] was not found." . PHP_EOL;
                if (config("app.debug")) {
                    \write_log(
                        "error",
                        [
                            "key" => $key,
                            "message" => $messageErr,
                        ],
                        "App\Core\Support\App.externalApi",
                        false
                    );
                }
                throw new \Exception($messageErr);
            }

            $base = $configs[$key];

            // 2. Satukan Headers (Config Base + Dinamis dari argumen)
            $headers = array_merge($base["headers"] ?? [], $dynamicOptions["headers"] ?? []);

            // 3. Build Result untuk GoHttpClient
            return [
                "method" => strtoupper($base["method"] ?? "GET"),
                "url" => $base["url"] ?? "",
                "headers" => $headers,
                "body" => isset($dynamicOptions["body"])
                    ? (is_array($dynamicOptions["body"])
                        ? json_encode($dynamicOptions["body"])
                        : $dynamicOptions["body"])
                    : $base["body"] ?? "",
                "timeout" => $dynamicOptions["timeout"] ?? ($base["timeout"] ?? 30),
            ];
        } catch (Throwable $e) {
            // Re-throw agar error detail (seperti typo function) muncul di log global
            throw $e;
        }
    }
}
