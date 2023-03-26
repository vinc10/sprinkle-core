<?php

/*
 * UserFrosting Core Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-core
 * @copyright Copyright (c) 2021 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-core/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\Core\Tests\Integration\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;
use Slim\Views\Twig;
use UserFrosting\Alert\AlertStream;
use UserFrosting\Config\Config;
use UserFrosting\Routes\RouteDefinitionInterface;
use UserFrosting\Session\Session;
use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\Core\Csrf\CsrfGuard;
use UserFrosting\Sprinkle\Core\Tests\CoreTestCase as TestCase;

/**
 * Tests CsrfGuardMiddleware & CsrfGuard class.
 */
class CsrfGuardMiddlewareTest extends TestCase
{
    protected string $mainSprinkle = CsrfSprinkle::class;

    public function testFailCsrf(): void
    {
        /** @var AlertStream */
        $ms = $this->ci->get(AlertStream::class);
        $ms->resetMessageStream();

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('POST', '/csrf');
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertResponseStatus(400, $response);
        $this->assertJsonResponse('Bad Request', $response, 'title');
        $this->assertJsonResponse('Missing CSRF token. Try refreshing the page and then submitting again?', $response, 'description');

        // Test message
        $messages = $ms->getAndClearMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('danger', end($messages)['type']); // @phpstan-ignore-line
    }

    public function testCsrfDisabled(): void
    {
        /** @var Config */
        $config = $this->ci->get(Config::class);
        $config->set('csrf.enabled', false);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('POST', '/csrf');
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertResponseStatus(200, $response);
        $this->assertJsonResponse(['foo' => 'bar'], $response);
    }

    public function testCsrfBlacklist(): void
    {
        /** @var Config */
        $config = $this->ci->get(Config::class);
        $config->set('csrf.blacklist', [
            '^/csrf' => ['POST'],
        ]);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('POST', '/csrf');
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertResponseStatus(200, $response);
        $this->assertJsonResponse(['foo' => 'bar'], $response);
    }

    public function testCsrfStorage(): void
    {
        /** @var AlertStream */
        $ms = $this->ci->get(AlertStream::class);
        $ms->resetMessageStream();

        /** @var Config */
        $config = $this->ci->get(Config::class);
        $csrfKey = $config->getString('session.keys.csrf');

        /** @var Session */
        $session = $this->ci->get(Session::class);
        $session->set($csrfKey, []);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('POST', '/csrf');
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertResponseStatus(400, $response);

        // Test message
        $messages = $ms->getAndClearMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('danger', end($messages)['type']); // @phpstan-ignore-line
    }

    public function testTwigCsrf(): void
    {
        /** @var CsrfGuard */
        $guard = $this->ci->get(CsrfGuard::class);

        $request = $this->createRequest('GET', '/csrf');
        $response = $this->handleRequest($request);

        $this->assertResponseStatus(200, $response);
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('<input type="hidden" name="" value="">', $body);
        $this->assertStringContainsString($guard->getTokenName(), $body);
        $this->assertStringContainsString($guard->getTokenValue(), $body);
    }
}

class TestRoute implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        $app->get('/csrf', function (Response $response, Twig $twig) {
            return $twig->render($response, 'forms/csrf.html.twig');
        });

        $app->post('/csrf', function (Response $response) {
            $payload = json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR);
            $response->getBody()->write($payload);

            return $response->withHeader('Content-Type', 'application/json');
        });
    }
}

class CsrfSprinkle extends Core
{
    public function getRoutes(): array
    {
        return [
            TestRoute::class,
        ];
    }
}
