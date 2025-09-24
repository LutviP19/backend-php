<?php

namespace App\Core\Security\Middleware;

use App\Core\Support\Config;
use App\Core\Http\{Request,Response};
use Maba\GentleForce\RateLimit\UsageRateLimit;
use Maba\GentleForce\RateLimitProvider;
use Maba\GentleForce\Throttler;
use Maba\GentleForce\Exception\RateLimitReachedException;
use Predis\Client as PredisClient;
use Exception;

/**
 * RateLimiter class
 * @author Lutvi <lutvip19@gmail.com>
 */
class RateLimiter
{
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

    /**
     * Bucket size, measured in tokens.
     *
     * @var float
     */
    private $bucketSize;

    /**
     * Unique name for specific module.
     *
     * @var string
     */
    protected $rateLimitName;

    // Object class
    protected PredisClient $redisClient;

    public function __construct($rateLimitName)
    {
        $this->rateLimitName = (string) $rateLimitName ?: 'request_'.clientIP().'_'.date('Ymd');

        $this->redisClient = new PredisClient([
                                'host' => Config::get('redis.cache.host'),
                                'port' => Config::get('redis.cache.port'),
                                'database' => Config::get('redis.cache.database')
                            ]);
    }

    /**
     * Setup rate limited for api access
     *
     * @params :
     * $id : Client idetifier.
     * $maxUsage : Count of usages available in specified period.
     * $period : Period in seconds for max usages to be "spent".
     * $bucketSize : Bucket size, measured in tokens.
     *
     * @return Response
     */
    public function setup(string $id = '', int $maxUsages = 30, int $period = 60, int $bucketSize = 1800)
    {
        $id = $id ?: clientIP();
        $response = new Response();

        $this->rateLimitName = (string) 'api_'.$this->rateLimitName;

        $rateLimitProvider = new RateLimitProvider();
        $rateLimitProvider->registerRateLimits($this->rateLimitName, [
            (new UsageRateLimit($maxUsages, $period))->setBucketedPeriod($bucketSize),
        ]);

        $throttler = new Throttler($this->redisClient, $rateLimitProvider);

        try {
            $result = $throttler->checkAndIncrease($this->rateLimitName, $id);
            $response->header('Requests-Available', $result->getUsagesAvailable());
        } catch (RateLimitReachedException $exception) {
            $response->header('Wait-For', $exception->getWaitForInSeconds(), 429);

            throw new Exception('Many request try again after: '.$exception->getWaitForInSeconds().' seconds.');
        }
    }

    /**
     * Setup rate limited for post form data
     *
     * @params :
     * $id : Client idetifier.
     * $callback : function validate form
     * $maxUsage : Count of usages available in specified period.
     * $period : Period in seconds for max usages to be "spent".
     * $bucketSize : Bucket size, measured in tokens.
     *
     * @return Response
     */
    public function setupForm(string $id = '', $callback = false, int $maxUsages = 5, int $maxErrorPerDay = 10, int $bucketSize = 3600)
    {
        $response = new Response();
        $request = new Request();

        $id = $id ?: clientIP();

        $this->rateLimitName = (string) 'credentials_'.$this->rateLimitName;

        $rateLimitProvider = new RateLimitProvider();
        $rateLimitProvider->registerRateLimits($this->rateLimitName, [
            // allow $maxUsages errors per hour; 2 additional errors if no errors were made during last hour
            (new UsageRateLimit($maxUsages, $bucketSize))->setBucketedUsages(($maxUsages - 1)),
            // allow $maxErrorPerDay errors per day
            new UsageRateLimit($maxErrorPerDay, $bucketSize * 24),
        ]);

        $throttler = new Throttler($this->redisClient, $rateLimitProvider);

        // brute-force limiting:
        try {
            // we must increase error count in-advance before even checking credentials
            // this avoids race-conditions with lots of requests
            $credentialsResult = $throttler->checkAndIncrease($this->rateLimitName, $id);

        } catch (RateLimitReachedException $exception) {

            die(
                $response->json(
                    [
                    'status' => false,
                    'statusCode' => 500,
                    'message' => 'Too much tries, Please try after: '.$exception->getWaitForInSeconds().' seconds.',
                ],
                    500
                )
            );
        }

        if ($callback) {
            // as we've increased error count in advance, we need to decrease it if everything went fine
            $credentialsResult->decrease();

            // passed into system
        }
    }
}
