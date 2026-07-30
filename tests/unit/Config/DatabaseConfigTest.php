<?php
namespace Tests\Unit\Config;

use CodeIgniter\Test\CIUnitTestCase;

final class DatabaseConfigTest extends CIUnitTestCase
{
    public function testConfigClassExists()
    {
        $config = new \Config\Database();
        $this->assertInstanceOf(\Config\Database::class, $config);
    }

    public function testDefaultGroupIsSet()
    {
        $config = new \Config\Database();
        $this->assertNotEmpty($config->defaultGroup);
    }

    public function testTestsGroupExists()
    {
        $config = new \Config\Database();
        $this->assertArrayHasKey('hostname', $config->tests);
        $this->assertArrayHasKey('DBDriver', $config->tests);
        $this->assertEquals('SQLite3', $config->tests['DBDriver']);
    }

    public function testDefaultDriverIsPostgre()
    {
        $config = new \Config\Database();
        $this->assertEquals('Postgre', $config->default['DBDriver']);
    }

    public function testSwitchesToTestsWhenTesting()
    {
        $config = new \Config\Database();
        // In testing env, defaultGroup should be 'tests'
        if (ENVIRONMENT === 'testing') {
            $this->assertEquals('tests', $config->defaultGroup);
        }
    }
}
