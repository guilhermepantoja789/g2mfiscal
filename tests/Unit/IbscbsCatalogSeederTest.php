<?php

namespace Tests\Unit;

use App\Models\ClassTrib;
use App\Models\IndOp;
use App\Models\NbsCode;
use App\Models\NbsCorrelacao;
use Database\Seeders\IbscbsCatalogSeeder;
use Tests\TestCase;

class IbscbsCatalogSeederTest extends TestCase
{
    public function test_seeds_catalogs_with_expected_minimums(): void
    {
        $this->seed(IbscbsCatalogSeeder::class);

        $this->assertGreaterThanOrEqual(26, IndOp::query()->count());
        $this->assertGreaterThanOrEqual(100, ClassTrib::query()->count());
        $this->assertGreaterThanOrEqual(1, ClassTrib::query()->where('destaque', true)->count());
        $this->assertGreaterThanOrEqual(500, NbsCode::query()->count());
        $this->assertGreaterThanOrEqual(100, NbsCorrelacao::query()->count());

        $this->assertTrue(IndOp::query()->where('codigo', '100301')->exists());
        $this->assertTrue(ClassTrib::query()->where('cst', '000')->where('c_class_trib', '000001')->exists());
        $this->assertTrue(NbsCode::query()->where('codigo', '115011000')->exists());
    }

    public function test_correlacao_sugere_nbs_para_desenvolvimento(): void
    {
        $this->seed(IbscbsCatalogSeeder::class);

        $sug = NbsCorrelacao::sugestoesPara('010101', true);

        $this->assertNotEmpty($sug['nbs']);
        $codigos = array_column($sug['nbs'], 'codigo');
        $this->assertContains('115021000', $codigos);
        $this->assertContains('100301', $sug['ind_ops']);
    }

    public function test_correlacao_consultoria_ti(): void
    {
        $this->seed(IbscbsCatalogSeeder::class);

        $sug = NbsCorrelacao::sugestoesPara('010601', true);
        $codigos = array_column($sug['nbs'], 'codigo');

        $this->assertContains('115011000', $codigos);
    }
}
