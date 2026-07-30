<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

/**
 * Smoke test: logs in as admin and GETs every parameter-less route.
 * Any 5xx response or "Whoops" page means a runtime error (undefined
 * variable, missing method, TypeError, etc.) that would otherwise only
 * surface when clicked manually in the browser.
 *
 * Routes render against the seeded in-memory test database (DatabaseTrait),
 * which is enough to catch render-time errors without touching production.
 */
class RouteSmokeTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTrait;
    use DatabaseTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
        $this->loginAsAdmin();
    }

    protected function tearDown(): void
    {
        $this->truncateAll();
        parent::tearDown();
    }

    public function testAllGetRoutesRenderWithoutError(): void
    {
        $routes = service('routes');
        $routes->loadRoutes();
        $routes = $routes->getRoutes('GET');

        $failures = [];
        $visited  = 0;

        foreach ($routes as $route => $handler) {
            // Routes with placeholders (e.g. {id} or regex like ([^/]+)) are
            // covered by dedicated CRUD tests; skip literal visits here.
            if (preg_match('/[{}()\[\]\^]/', $route)) {
                continue;
            }

            try {
                $result = $this->get($route);
                $status = $result->getStatusCode();
                $body   = $result->getBody();
            } catch (\Throwable $e) {
                $visited++;
                $failures[$route] = 'EXCEPTION: ' . $e->getMessage();

                continue;
            }

            $visited++;

            if (
                $status >= 500
                || stripos($body, 'We seem to have hit a snag') !== false
                || stripos($body, 'Whoops!') !== false
            ) {
                $failures[$route] = $status;
            }
        }

        $this->assertNotEmpty($routes, 'No routes discovered');
        $this->assertEmpty(
            $failures,
            sprintf(
                "Visited %d GET routes, %d failed (route => status):\n%s",
                $visited,
                count($failures),
                json_encode($failures, JSON_PRETTY_PRINT)
            )
        );
    }
}
