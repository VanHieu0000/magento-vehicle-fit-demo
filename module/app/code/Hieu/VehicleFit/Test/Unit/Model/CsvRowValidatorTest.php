<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Test\Unit\Model;

use Hieu\VehicleFit\Model\CsvRowValidator;
use PHPUnit\Framework\TestCase;

class CsvRowValidatorTest extends TestCase
{
    private CsvRowValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CsvRowValidator();
    }

    private function row(array $override = []): array
    {
        return array_merge([
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year_from' => '2018',
            'year_to' => '2022',
            'engine' => '1.8 Hybrid',
            'sku' => 'BRK-DSC-280',
        ], $override);
    }

    public function testValidRowHasNoErrors(): void
    {
        $this->assertSame([], $this->validator->validate($this->row(), 2026));
    }

    public function testEngineIsOptional(): void
    {
        $this->assertSame([], $this->validator->validate($this->row(['engine' => '']), 2026));
    }

    public function testMissingSkuIsReported(): void
    {
        $this->assertSame(['missing sku'], $this->validator->validate($this->row(['sku' => '  ']), 2026));
    }

    public function testYearsMustBeNumbers(): void
    {
        $this->assertSame(['years must be numbers'], $this->validator->validate($this->row(['year_to' => '20x2']), 2026));
    }

    public function testYearFromAfterYearTo(): void
    {
        $this->assertContains(
            'year_from is after year_to',
            $this->validator->validate($this->row(['year_from' => '2023', 'year_to' => '2019']), 2026)
        );
    }

    public function testFutureYearOutOfRange(): void
    {
        $this->assertContains(
            'years must be between 1950 and 2028',
            $this->validator->validate($this->row(['year_to' => '2031']), 2026)
        );
    }

    public function testToVehicleTrimsAndCasts(): void
    {
        $this->assertSame(
            ['make' => 'Toyota', 'model' => 'Corolla', 'year_from' => 2018, 'year_to' => 2022, 'engine' => '1.8 Hybrid'],
            $this->validator->toVehicle($this->row(['make' => ' Toyota ', 'engine' => ' 1.8 Hybrid']))
        );
    }
}
