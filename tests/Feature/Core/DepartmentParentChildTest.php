<?php

namespace Tests\Feature\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Schema and validation tests for parent-child departments.
 *
 * Source: PRD docs/specs/2026-05-25-wns-restructure-prd/01-schema-changes.md.
 */
class DepartmentParentChildTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $bu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bu = BusinessUnit::create([
            'name' => 'Test BU',
            'code' => 'TBU',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function root_department_is_created_without_parent(): void
    {
        $dept = Department::create([
            'business_unit_id' => $this->bu->id,
            'code' => 'ROOT',
            'name' => 'Root Department',
            'is_active' => true,
        ]);

        $this->assertNull($dept->parent_department_id);
        $this->assertTrue($dept->isRootDepartment());
        $this->assertFalse($dept->isSubDepartment());
    }

    #[Test]
    public function sub_department_can_reference_a_root_parent(): void
    {
        $parent = $this->makeRoot('PAR', 'Parent');
        $child = Department::create([
            'business_unit_id' => $this->bu->id,
            'parent_department_id' => $parent->id,
            'code' => 'CHD',
            'name' => 'Child Division',
            'is_active' => true,
        ]);

        $this->assertSame($parent->id, $child->parent_department_id);
        $this->assertTrue($child->isSubDepartment());
        $this->assertSame($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains($child->id));
    }

    #[Test]
    public function descendant_ids_returns_self_for_leaf_or_flat_dept(): void
    {
        $flat = $this->makeRoot('FLT', 'Flat');

        $this->assertSame([$flat->id], $flat->descendantIds());
    }

    #[Test]
    public function descendant_ids_returns_self_plus_active_children_for_root(): void
    {
        $root = $this->makeRoot('R', 'Root');

        $childA = $this->makeChild($root, 'CA', 'Child A');
        $childB = $this->makeChild($root, 'CB', 'Child B');
        $inactive = $this->makeChild($root, 'CI', 'Inactive');
        $inactive->update(['is_active' => false]);

        $ids = $root->fresh()->descendantIds();

        $this->assertContains($root->id, $ids);
        $this->assertContains($childA->id, $ids);
        $this->assertContains($childB->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    #[Test]
    public function parent_must_belong_to_the_same_business_unit(): void
    {
        $otherBu = BusinessUnit::create([
            'name' => 'Other',
            'code' => 'OTH',
            'is_active' => true,
        ]);

        $parent = $this->makeRoot('PAR', 'Parent');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('same business unit');

        Department::create([
            'business_unit_id' => $otherBu->id,
            'parent_department_id' => $parent->id,
            'code' => 'X',
            'name' => 'X',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function sub_of_sub_is_rejected(): void
    {
        $root = $this->makeRoot('R', 'Root');
        $sub = $this->makeChild($root, 'S', 'Sub');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('max 1 level');

        Department::create([
            'business_unit_id' => $this->bu->id,
            'parent_department_id' => $sub->id,
            'code' => 'GC',
            'name' => 'Grandchild',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function self_reference_is_rejected(): void
    {
        $dept = $this->makeRoot('SELF', 'Self');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('its own parent');

        $dept->parent_department_id = $dept->id;
        $dept->save();
    }

    #[Test]
    public function full_name_includes_parent_for_sub_dept(): void
    {
        $root = $this->makeRoot('SM', 'Sales & Marketing');
        $sub = $this->makeChild($root, 'BS', 'Business Solutions');

        $this->assertSame('Test BU - Sales & Marketing', $root->fresh()->full_name);
        $this->assertSame('Test BU - Sales & Marketing / Business Solutions', $sub->fresh()->full_name);
    }

    #[Test]
    public function root_dept_auto_creates_default_positions_but_sub_dept_does_not(): void
    {
        $root = $this->makeRoot('R', 'Root');
        $sub = $this->makeChild($root, 'S', 'Sub');

        $this->assertGreaterThan(0, $root->positions()->count());
        $this->assertSame(0, $sub->positions()->count());
    }

    private function makeRoot(string $code, string $name): Department
    {
        return Department::create([
            'business_unit_id' => $this->bu->id,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function makeChild(Department $parent, string $code, string $name): Department
    {
        return Department::create([
            'business_unit_id' => $parent->business_unit_id,
            'parent_department_id' => $parent->id,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ]);
    }
}
