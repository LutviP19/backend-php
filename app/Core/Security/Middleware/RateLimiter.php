<?php

namespace App\Core\Security\Middleware;

use App\Core\Support\Config;
use App\Core\Http\Response;
use Maba\GentleForce\RateLimit\UsageRateLimit;
use Maba\GentleForce\RateLimitProvider;
use Maba\GentleForce\Throttler;
use Maba\GentleForce\Exception\RateLimitReachedException;
use Exception;

class RateLimiter 
{
    /**
     * Bucket size, measured in tokens.
     *
     * @var float
     */
    private $bucketSize;

    /**
     * Count of usages available in specified period.
     *
     * @var float
     */
    private $maxUsages;

    /**
     * Period in seconds for max usages to be "spent".
     *
     * @var float
     */
    private $period;

    protected $rateLimitName;
    protected $rateLimitProvider;
    protected $throttler;

    public function __construct($rateLimitName, $maxUsages, $period, $bucketSize) 
    {
        $this->maxUsages = (int) $maxUsages ?: 3;
        $this->period = (int) $period ?: 900;
        $this->bucketSize = (int) $bucketSize ?: 1200;
        
        $this->rateLimitName = (string) $rateLimitName ?: 'api_request_'.date('Ymd');

        $this->rateLimitProvider = new RateLimitProvider();
        $this->rateLimitProvider->registerRateLimits($this->rateLimitName, [
            (new UsageRateLimit($this->maxUsages, $this->period))->setBucketedPeriod($this->bucketSize),
        ]);

        $this->throttler = new Throttler(new \Predis\Client([
            'host' => Config::get('redis.cache.host'),
            'port' => Config::get('redis.cache.port'),
            'database' => Config::get('redis.cache.database')
        ]), $this->rateLimitProvider);
    }

    // setup rate limited
    public function setup($id = '')
    {
        $id = $id ?: clientIP();
        $response = new Response();

        try {
            $result = $this->throttler->checkAndIncrease($this->rateLimitName, $id);
            $response->header('Requests-Available', $result->getUsagesAvailable());
        } catch (RateLimitReachedException $exception) {
            $response->header('Wait-For', $exception->getWaitForInSeconds(), 429);

            throw new Exception('Many request try again after: '.$exception->getWaitForInSeconds().' seconds.');
        }
    }
}
