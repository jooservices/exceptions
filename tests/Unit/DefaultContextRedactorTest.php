<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use JOOservices\Exceptions\Support\DefaultContextRedactor;
use JOOservices\Exceptions\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

final class DefaultContextRedactorTest extends TestCase
{
    private DefaultContextRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redactor = new DefaultContextRedactor();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function sensitiveKeys(): iterable
    {
        yield from [
            'access-token' => ['access-token'],
            'access_token' => ['access_token'],
            'api-key' => ['api-key'],
            'api_key' => ['api_key'],
            'apikey' => ['apikey'],
            'auth_token' => ['auth_token'],
            'authorization' => ['authorization'],
            'bearer' => ['bearer'],
            'client_secret' => ['client_secret'],
            'cookie' => ['cookie'],
            'credential' => ['credential'],
            'credentials' => ['credentials'],
            'csrf_token' => ['csrf_token'],
            'jwt' => ['jwt'],
            'password' => ['password'],
            'password_confirmation' => ['password_confirmation'],
            'passwd' => ['passwd'],
            'private_key' => ['private_key'],
            'pwd' => ['pwd'],
            'refresh-token' => ['refresh-token'],
            'refresh_token' => ['refresh_token'],
            'secret' => ['secret'],
            'session' => ['session'],
            'session_id' => ['session_id'],
            'set-cookie' => ['set-cookie'],
            'token' => ['token'],
            'x-api-key' => ['x-api-key'],
        ];
    }

    #[Test]
    #[DataProvider('sensitiveKeys')]
    public function masksSensitiveKeys(string $key): void
    {
        $result = $this->redactor->redact([$key => 'top-secret']);

        self::assertSame([$key => DefaultContextRedactor::REDACTED_VALUE], $result);
    }

    #[Test]
    #[DataProvider('sensitiveKeys')]
    public function masksSensitiveKeysCaseInsensitively(string $key): void
    {
        $upper = strtoupper($key);

        $result = $this->redactor->redact([$upper => 'top-secret']);

        self::assertSame([$upper => DefaultContextRedactor::REDACTED_VALUE], $result);
    }

    #[Test]
    public function passesSafeKeysThrough(): void
    {
        $context = ['user_id' => 42, 'entity' => 'user'];

        self::assertSame($context, $this->redactor->redact($context));
    }

    #[Test]
    public function masksNestedSensitiveKeysRecursively(): void
    {
        $result = $this->redactor->redact([
            'headers' => [
                'Authorization' => 'Bearer abc',
                'Accept' => 'application/json',
            ],
            'auth' => [
                'user' => [
                    'csrf_token' => 'xyz',
                    'name' => 'Viet',
                ],
            ],
        ]);

        self::assertSame([
            'headers' => [
                'Authorization' => DefaultContextRedactor::REDACTED_VALUE,
                'Accept' => 'application/json',
            ],
            'auth' => [
                'user' => [
                    'csrf_token' => DefaultContextRedactor::REDACTED_VALUE,
                    'name' => 'Viet',
                ],
            ],
        ], $result);
    }

    #[Test]
    public function describesResourcesWithoutDroppingSiblings(): void
    {
        $resource = fopen('php://memory', 'r+');

        if ($resource === false) {
            self::fail('Could not open memory stream.');
        }

        try {
            $result = $this->redactor->redact(['stream' => $resource, 'safe' => 'kept']);

            self::assertSame('[RESOURCE:stream]', $result['stream']);
            self::assertSame('kept', $result['safe']);
        } finally {
            fclose($resource);
        }
    }

    #[Test]
    public function describesObjectsWithoutDroppingSiblings(): void
    {
        $result = $this->redactor->redact(['object' => new stdClass(), 'safe' => 1]);

        self::assertSame('[OBJECT:stdClass]', $result['object']);
        self::assertSame(1, $result['safe']);
    }

    #[Test]
    public function sanitisesNonFiniteFloats(): void
    {
        $result = $this->redactor->redact([
            'nan' => NAN,
            'inf' => INF,
            'neg_inf' => -INF,
            'ok' => 1.5,
        ]);

        self::assertSame('[NON_FINITE_FLOAT]', $result['nan']);
        self::assertSame('[NON_FINITE_FLOAT]', $result['inf']);
        self::assertSame('[NON_FINITE_FLOAT]', $result['neg_inf']);
        self::assertSame(1.5, $result['ok']);
    }

    #[Test]
    public function stringifiesIntegerKeys(): void
    {
        $result = $this->redactor->redact([3 => 'three']);

        self::assertSame(['3' => 'three'], $result);
    }

    #[Test]
    public function capsDeeplyNestedContext(): void
    {
        $deep = ['level' => 'bottom'];

        for ($i = 0; $i < 69; $i++) {
            $deep = ['level' => $deep];
        }

        $result = $this->redactor->redact($deep);

        $node = $result;

        while (true) {
            self::assertIsArray($node);

            if (!isset($node['level']) || !is_array($node['level'])) {
                break;
            }

            $node = $node['level'];
        }

        self::assertSame(['_context' => '[MAX_DEPTH]'], $node);
    }

    #[Test]
    public function terminatesOnSelfReferencingArrays(): void
    {
        $selfReferencing = ['safe' => 1];
        $selfReferencing['self'] = &$selfReferencing;

        $result = $this->redactor->redact(['outer' => $selfReferencing]);

        $node = $result['outer'];
        self::assertIsArray($node);

        while (true) {
            self::assertIsArray($node);

            if (!isset($node['self']) || !is_array($node['self'])) {
                break;
            }

            $node = $node['self'];
        }

        self::assertSame(['_context' => '[MAX_DEPTH]'], $node);
    }
}
