<?php
namespace Tests\Unit\Config;

use CodeIgniter\Test\CIUnitTestCase;

final class EmailConfigTest extends CIUnitTestCase
{
    public function testConfigClassExists()
    {
        $config = new \Config\Email();
        $this->assertInstanceOf(\Config\Email::class, $config);
    }

    public function testDefaultProtocolIsSmtp()
    {
        $config = new \Config\Email();
        $this->assertEquals('smtp', $config->protocol);
    }

    public function testDefaultSmtpPort()
    {
        $config = new \Config\Email();
        $expected = env('email.SMTPPort') ?: 465;
        $this->assertEquals((int)$expected, $config->SMTPPort);
    }

    public function testDefaultSmtpHost()
    {
        $config = new \Config\Email();
        $expected = env('email.SMTPHost') ?: 'smtp.gmail.com';
        $this->assertEquals($expected, $config->SMTPHost);
    }

    public function testMailTypeIsHtml()
    {
        $config = new \Config\Email();
        $this->assertEquals('html', $config->mailType);
    }
}
