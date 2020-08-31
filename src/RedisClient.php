<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

use Palicao\PhpRebloom\Exception\RedisAuthenticationException;
use Palicao\PhpRebloom\Exception\RedisClientException;
use Redis;
use RedisException;

class RedisClient
{
    /** @var Redis */
    private $redis;

    /** @var RedisConnectionParams */
    private $connectionParams;

    public function __construct(Redis $redis, RedisConnectionParams $connectionParams)
    {
        $this->redis = $redis;
        $this->connectionParams = $connectionParams;
    }

    /**
     * @throws RedisClientException
     * @throws RedisAuthenticationException
     */
    private function connectIfNeeded(): void
    {
        if ($this->redis->isConnected()) {
            return;
        }

        $params = $this->connectionParams;

        if ($params->isPersistentConnection()) {
            /** @psalm-suppress TooManyArguments */
            $result = $this->redis->pconnect(
                $params->getHost(),
                $params->getPort(),
                $params->getTimeout(),
                gethostname(),
                $params->getRetryInterval(),
                $params->getReadTimeout()
            );
        } else {
            $result = $this->redis->connect(
                $params->getHost(),
                $params->getPort(),
                $params->getTimeout(),
                null,
                $params->getRetryInterval(),
                $params->getReadTimeout()
            );
        }

        if ($result === false) {
            throw new RedisClientException(sprintf(
                'Unable to connect to redis server %s:%s: %s',
                $params->getHost(),
                $params->getPort(),
                $this->redis->getLastError() ?? 'unknown error'
            ));
        }

        $this->authenticate($params->getUsername(), $params->getPassword());
    }

    /**
     * @noinspection PhpDocRedundantThrowsInspection
     *
     * @param array $params
     * @return mixed
     * @throws RedisException
     * @throws RedisClientException
     */
    public function executeCommand(array $params)
    {
        $this->connectIfNeeded();

        $value = (PHP_VERSION_ID < 70300) ? '1' : 1;
        $this->redis->setOption(Redis::OPT_REPLY_LITERAL, $value);

        return $this->redis->rawCommand(...$params);
    }

    /**
     * @param string|null $username
     * @param string|null $password
     * @throws RedisAuthenticationException
     */
    private function authenticate(?string $username, ?string $password): void
    {
        try {
            if ($password) {
                if ($username) {
                    // Calling auth() with an array throws a TypeError in some cases
                    /** @noinspection PhpMethodParametersCountMismatchInspection */
                    $result = $this->redis->rawCommand('AUTH', $username, $password);
                } else {
                    /** @psalm-suppress PossiblyNullArgument */
                    $result = $this->redis->auth($password);
                }
                if ($result === false) {
                    throw new RedisAuthenticationException(sprintf(
                        'Failure authenticating user %s', $username ?: 'default'
                    ));
                }
            }
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (RedisException $e) {
            throw new RedisAuthenticationException(sprintf(
                'Failure authenticating user %s: %s', $username ?: 'default', $e->getMessage()
            ));
        }
    }
}
