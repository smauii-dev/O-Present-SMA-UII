<?php
namespace Tests\Support\Traits;

trait FileUploadTrait
{
    protected function createTestFile(string $filename, int $size = 1024): string
    {
        $testDir = WRITEPATH . 'uploads/test/';
        if (!is_dir($testDir)) {
            mkdir($testDir, 0775, true);
        }

        $filepath = $testDir . $filename;
        file_put_contents($filepath, str_repeat('x', $size));

        return $filepath;
    }

    protected function createTestPdf(string $filename = 'test.pdf', int $size = 1024): string
    {
        return $this->createTestFile($filename, $size);
    }
}
