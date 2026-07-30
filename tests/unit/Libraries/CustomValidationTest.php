<?php
namespace Tests\Unit\Libraries;

use CodeIgniter\Test\CIUnitTestCase;

final class CustomValidationTest extends CIUnitTestCase
{
    public function testCustomValidationLibraryExists()
    {
        $this->assertTrue(class_exists('App\Validation\PegawaiRules'));
    }

    public function testValidTimezone()
    {
        $this->assertTrue(in_array('Asia/Jakarta', timezone_identifiers_list()));
        $this->assertFalse(in_array('Invalid/Timezone', timezone_identifiers_list()));
    }
}
