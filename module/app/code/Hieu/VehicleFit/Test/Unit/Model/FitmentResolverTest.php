<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Test\Unit\Model;

use Hieu\VehicleFit\Model\Config;
use Hieu\VehicleFit\Model\FitmentResolver;
use Hieu\VehicleFit\Model\ResourceModel\Vehicle as VehicleResource;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FitmentResolverTest extends TestCase
{
    private VehicleResource&MockObject $resource;
    private CacheInterface&MockObject $cache;
    private FitmentResolver $resolver;

    protected function setUp(): void
    {
        $this->resource = $this->createMock(VehicleResource::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $config = $this->createMock(Config::class);
        $config->method('getCacheLifetime')->willReturn(3600);

        $this->resolver = new FitmentResolver($this->resource, $this->cache, new Json(), $config);
    }

    public function testCacheMissReadsDbAndSavesWithTag(): void
    {
        $this->cache->method('load')->willReturn(false);
        $this->resource->expects($this->once())->method('getProductIds')->with(42)->willReturn([7, 9]);
        $this->cache->expects($this->once())
            ->method('save')
            ->with('[7,9]', 'hieu_vehiclefit_products_42', [FitmentResolver::CACHE_TAG], 3600);

        $this->assertSame([7, 9], $this->resolver->getProductIds(42));
        // second call in same request: memo, no new DB or cache call
        $this->assertSame([7, 9], $this->resolver->getProductIds(42));
    }

    public function testCacheHitSkipsDb(): void
    {
        $this->cache->method('load')->willReturn('[3,5]');
        $this->resource->expects($this->never())->method('getProductIds');

        $this->assertTrue($this->resolver->fits(42, 5));
        $this->assertFalse($this->resolver->fits(42, 6));
    }

    public function testInvalidIdsNeverTouchStorage(): void
    {
        $this->cache->expects($this->never())->method('load');
        $this->resource->expects($this->never())->method('getProductIds');

        $this->assertSame([], $this->resolver->getProductIds(0));
        $this->assertFalse($this->resolver->fits(-1, 5));
    }
}
