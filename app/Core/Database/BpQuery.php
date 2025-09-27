<?php

namespace App\Core\Database;

class BpQuery {
    private $pdo; // PDO instance for database connection
    protected $query; // Stores the SQL query to be executed
    private $stmt; // Holds the prepared statement
    private $params = []; // Array of parameters for query binding
    private $cache = []; // Caches results of executed queries
    private $logFile = __DIR__ . "/../../storage/logs/query_log.log"; // File path for logging errors
    private $debugMode = false; // Debug mode flag
    private $adminEmails; // Stores emails for alert notifications
    private $adminIPs; // Stores IPs for admin-level access checks
    private $logTo = []; // Stores logging options (database, file, etc.)
    private $currentUserId; // Stores the current user ID for logs
    private $userRoles = []; // Stores user roles for permission checks
    private $lastInsertId; // Stores the last insert ID after insert queries
    private $affectedRows; // Number of affected rows from the last query
    private $transactionStarted = false; // Tracks transaction state
    private $executionTime; // Measures the time taken for query execution
    private $errorPointsThreshold = 1000; // Security points threshold for blocking
    private $errorDictionary = [ // Dictionary for security events and point values
        "SQL_INJECTION_ATTEMPT" => 90,
        "UNAUTHORIZED_ACCESS" => 80,
        "MISSING_PARAMETER" => 20,
        "INVALID_PARAMETER_FORMAT" => 50,
        "INVALID_PARAMETER_VALUE" => 25,
        "MULTIPLE_LOGIN_ATTEMPTS" => 70,
        "DATABASE_MANIPULATION_ATTEMPT" => 100,
        "INVALID_SQL_SYNTAX" => 40,
        "EXCESSIVE_QUERY_EXECUTION" => 60,
        "IP_BLACKLISTED" => 85,
        "CROSS_SITE_SCRIPTING_ATTEMPT" => 75,
        "TAMPERING_WITH_SESSION" => 80,
        "MALICIOUS_PARAMETER_LENGTH" => 50,
        "ACCESS_FROM_UNKNOWN_IP" => 65,
        "INVALID_FILE_ACCESS" => 85,
        "DEBUG_MODE_ENABLED_UNAUTHORIZED" => 70
    ];

    public function __construct(PDO $pdo = null) {

        try {
            $this->pdo = $pdo ?: Connection::make(); // Create PDO instance
        } catch (PDOException $e) {
            $this->logError("Connection failed: " . $e->getMessage()); // Log connection error
            throw new Exception("Database connection error."); // Throw general exception
        }

        // if ($this->isBlockedIP($_SERVER['REMOTE_ADDR'])) { // Check if IP is blocked
        //     throw new Exception("Access denied. Your IP has been blocked due to multiple security violations.");
        // }
    }

    private function isBlockedIP($ip) {
        $stmt = $this->pdo->prepare("SELECT SUM(points) as total_points FROM sf_events_log WHERE ip_address = :ip");
        $stmt->execute([':ip' => $ip]); // Execute with IP parameter
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['total_points'] >= $this->errorPointsThreshold; // Check if points exceed threshold
    }

    public function setQuery($query) {
        $this->resetParams(); // Clear parameters before setting a new query
        $this->query = $query;
        return $this;
    }

    private function resetParams() {
        $this->params = []; // Clear stored parameters
    }

    public function addParam($param, $value, $validate = null) {
        if ($validate && !$this->validateParam($value, $validate)) {
            // $this->logSecurityEvent("INVALID_PARAMETER_VALUE", $param, $value); // Log invalid parameter event
            throw new Exception("Validation failed for parameter: $param"); // Throw validation exception
        }
        $this->params[$param] = $value; // Add parameter to array
        return $this;
    }

    private function validateParam($value, $validate) {
        return preg_match($validate, $value); // Validate parameter using regex
    }

    private function logSecurityEvent($eventCode, $param = null, $value = null) {
        $userId = $this->currentUserId ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'];
        $host = gethostbyaddr($ip);
        $points = $this->errorDictionary[$eventCode] ?? 0;

        $stmt = $this->pdo->prepare("INSERT INTO sf_events_log (user_id, type, message, ip_address, host, points, timestamp) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $message = "Event: $eventCode; Param: $param; Value: $value";
        $stmt->execute([$userId, $eventCode, $message, $ip, $host, $points]);
    }

    public function execute() {
        if (isset($this->cache[$this->query])) {
            return $this->cache[$this->query]; // Return cached result if exists
        }

        try {
            $startTime = microtime(true); // Start timing for execution
            $this->stmt = $this->pdo->prepare($this->query); // Prepare query

            foreach ($this->params as $param => $value) {
                $this->stmt->bindValue($param, $value); // Bind each parameter
            }

            if (!$this->transactionStarted) {
                $this->pdo->beginTransaction(); // Begin transaction if not started
                $this->transactionStarted = true;
            }

            $this->stmt->execute(); // Execute the query
            $this->executionTime = microtime(true) - $startTime; // Calculate execution time
            $this->lastInsertId = $this->pdo->lastInsertId(); // Get last insert ID
            $this->affectedRows = $this->stmt->rowCount(); // Get affected rows count

            if ($this->transactionStarted) {
                $this->pdo->commit(); // Commit transaction
                $this->transactionStarted = false;
            }

            $result = [
                'success' => true,
                'executionTime' => $this->executionTime,
                'query' => $this->query
            ];

            if (stripos($this->query, 'INSERT') === 0) {
                $result['lastInsertId'] = $this->lastInsertId;
                $result['affectedRows'] = $this->affectedRows;
            } elseif (stripos($this->query, 'UPDATE') === 0 || stripos($this->query, 'DELETE') === 0) {
                $result['affectedRows'] = $this->affectedRows;
            } elseif (stripos($this->query, 'SELECT') === 0) {
                $result['result'] = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
                $result['rowCount'] = $this->affectedRows;
            }

            $this->cache[$this->query] = $result; // Cache the result
            return $result;

        } catch (PDOException $e) {
            if ($this->transactionStarted) {
                $this->pdo->rollBack(); // Roll back if transaction was started
                $this->transactionStarted = false;
            }
            $this->logError("Execution failed: " . $e->getMessage() . " | Query: " . $this->query); // Log the error
            throw new Exception("Query execution error: " . $e->getMessage()); // Throw execution exception
        }
    }

    private function logError($message) {
        $logEntry = date("Y-m-d H:i:s") . " - ERROR: $message\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND); // Write error to log file

        // if (in_array("database", $this->logTo)) {
        //     $this->logToDatabase($message); // Log error to database
        // }

        // if (in_array("email", $this->logTo)) {
        //     $this->sendEmail($message); // Send error via email
        // }
    }

    private function logToDatabase($message) {
        $stmt = $this->pdo->prepare("INSERT INTO sf_events_log (user_id, type, message, ip_address, host, points, timestamp) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$this->currentUserId ?? 'unknown', 'error', $message, $_SERVER['REMOTE_ADDR'], gethostbyaddr($_SERVER['REMOTE_ADDR']), 10]);
    }

    public function enableDebugMode($logTo = []) {
        $this->debugMode = true; // Enable debug mode
        $this->logTo = $logTo; // Set logging options
        return $this;
    }

    public function getLogs() {
        return file_get_contents($this->logFile); // Return contents of the log file
    }
}
