<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JTD\AdminPanel\Resources\Concerns\HasNestedResources;
use JTD\AdminPanel\Resources\Resource;
use PHPUnit\Framework\TestCase;

/**
 * Mock Model for testing nested resources.
 */
class MockNestedModel extends Model
{
    protected $fillable = ['id', 'name', 'parent_id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setRawAttributes($attributes);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}

/**
 * Test Resource with nested functionality.
 */
class TestNestedResource extends Resource
{
    use HasNestedResources;

    public static string $model = MockNestedModel::class;

    public function fields(Request $request): array
    {
        return [];
    }

    public static function uriKey(): string
    {
        return 'test-nested';
    }

    public function title(): string
    {
        return $this->resource->name ?? 'Untitled';
    }

    // Mock the indexQuery method
    public static function indexQuery(Request $request, $query)
    {
        return $query;
    }
}

/**
 * NestedResources Test Class
 */
class NestedResourcesTest extends TestCase
{
    private TestNestedResource $rootResource;
    private TestNestedResource $childResource;
    private TestNestedResource $grandchildResource;
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a hierarchy: root -> child -> grandchild
        $rootModel = new MockNestedModel(['id' => 1, 'name' => 'Root', 'parent_id' => null]);
        $childModel = new MockNestedModel(['id' => 2, 'name' => 'Child', 'parent_id' => 1]);
        $grandchildModel = new MockNestedModel(['id' => 3, 'name' => 'Grandchild', 'parent_id' => 2]);

        // Mock the relationships
        $rootModel->setRelation('children', collect([$childModel]));
        $childModel->setRelation('parent', $rootModel);
        $childModel->setRelation('children', collect([$grandchildModel]));
        $grandchildModel->setRelation('parent', $childModel);
        $grandchildModel->setRelation('children', collect()); // Empty collection for leaf node

        $this->rootResource = new TestNestedResource($rootModel);
        $this->childResource = new TestNestedResource($childModel);
        $this->grandchildResource = new TestNestedResource($grandchildModel);
        $this->request = new Request();
    }

    // ========================================
    // Basic Nested Resource Tests
    // ========================================

    public function test_has_parent_returns_false_for_root(): void
    {
        $this->assertFalse($this->rootResource->hasParent());
    }

    public function test_has_parent_returns_true_for_child(): void
    {
        $this->assertTrue($this->childResource->hasParent());
    }

    public function test_has_children_returns_true_for_parent(): void
    {
        $this->assertTrue($this->rootResource->hasChildren());
        $this->assertTrue($this->childResource->hasChildren());
    }

    public function test_has_children_returns_false_for_leaf(): void
    {
        $this->assertFalse($this->grandchildResource->hasChildren());
    }

    public function test_get_parent_resource_returns_null_for_root(): void
    {
        $parent = $this->rootResource->getParentResource($this->request);

        $this->assertNull($parent);
    }

    public function test_get_parent_resource_returns_parent_for_child(): void
    {
        $parent = $this->childResource->getParentResource($this->request);

        $this->assertInstanceOf(TestNestedResource::class, $parent);
        $this->assertEquals(1, $parent->getKey());
        $this->assertEquals('Root', $parent->resource->name);
    }

    public function test_get_children_resources_returns_children(): void
    {
        $children = $this->rootResource->getChildrenResources($this->request);

        $this->assertCount(1, $children);
        $this->assertEquals(2, $children->first()->getKey());
        $this->assertEquals('Child', $children->first()->resource->name);
    }

    public function test_get_children_resources_returns_empty_for_leaf(): void
    {
        $children = $this->grandchildResource->getChildrenResources($this->request);

        $this->assertCount(0, $children);
    }

    // ========================================
    // Depth and Hierarchy Tests
    // ========================================

    public function test_get_depth_returns_correct_depth(): void
    {
        $this->assertEquals(0, $this->rootResource->getDepth($this->request));
        $this->assertEquals(1, $this->childResource->getDepth($this->request));
        $this->assertEquals(2, $this->grandchildResource->getDepth($this->request));
    }

    public function test_get_breadcrumbs_returns_correct_trail(): void
    {
        $breadcrumbs = $this->grandchildResource->getBreadcrumbs($this->request);

        $this->assertCount(3, $breadcrumbs);
        $this->assertEquals('Root', $breadcrumbs[0]['title']);
        $this->assertEquals('Child', $breadcrumbs[1]['title']);
        $this->assertEquals('Grandchild', $breadcrumbs[2]['title']);
    }

    public function test_get_breadcrumbs_returns_empty_when_disabled(): void
    {
        TestNestedResource::$showParentInBreadcrumbs = false;

        $breadcrumbs = $this->grandchildResource->getBreadcrumbs($this->request);

        $this->assertEmpty($breadcrumbs);

        // Reset for other tests
        TestNestedResource::$showParentInBreadcrumbs = true;
    }

    public function test_get_root_ancestors_returns_correct_ancestors(): void
    {
        $ancestors = $this->grandchildResource->getRootAncestors($this->request);

        $this->assertCount(2, $ancestors);
        $this->assertEquals(1, $ancestors->first()->getKey());
        $this->assertEquals(2, $ancestors->last()->getKey());
    }

    public function test_get_root_ancestors_returns_empty_for_root(): void
    {
        $ancestors = $this->rootResource->getRootAncestors($this->request);

        $this->assertCount(0, $ancestors);
    }

    // ========================================
    // Movement and Validation Tests
    // ========================================

    public function test_can_move_to_returns_false_for_self(): void
    {
        $canMove = $this->childResource->canMoveTo($this->childResource->resource);

        $this->assertFalse($canMove);
    }

    public function test_can_move_to_returns_false_for_descendant(): void
    {
        $canMove = $this->rootResource->canMoveTo($this->grandchildResource->resource);

        $this->assertFalse($canMove);
    }

    public function test_can_move_to_returns_true_for_valid_parent(): void
    {
        $newParent = new MockNestedModel(['id' => 4, 'name' => 'New Parent', 'parent_id' => null]);

        $canMove = $this->childResource->canMoveTo($newParent);

        $this->assertTrue($canMove);
    }

    public function test_can_move_to_returns_true_for_null_parent(): void
    {
        $canMove = $this->childResource->canMoveTo(null);

        $this->assertTrue($canMove);
    }

    // ========================================
    // Tree Structure Tests
    // ========================================

    public function test_get_tree_structure_returns_correct_structure(): void
    {
        $tree = $this->rootResource->getTreeStructure($this->request, 2);

        $this->assertEquals('Root', $tree['title']);
        $this->assertEquals(0, $tree['depth']);
        $this->assertCount(1, $tree['children']);
        $this->assertEquals('Child', $tree['children'][0]['title']);
        $this->assertEquals(1, $tree['children'][0]['depth']);
        $this->assertCount(1, $tree['children'][0]['children']);
        $this->assertEquals('Grandchild', $tree['children'][0]['children'][0]['title']);
        $this->assertEquals(2, $tree['children'][0]['children'][0]['depth']);
    }

    public function test_get_tree_structure_respects_max_depth(): void
    {
        $tree = $this->rootResource->getTreeStructure($this->request, 1);

        $this->assertEquals('Root', $tree['title']);
        $this->assertCount(1, $tree['children']);
        $this->assertEquals('Child', $tree['children'][0]['title']);
        $this->assertCount(0, $tree['children'][0]['children']); // Should be empty due to max depth
    }

    // ========================================
    // Nested Resource Fields Tests
    // ========================================

    public function test_get_nested_resource_fields_includes_parent_for_child(): void
    {
        $fields = $this->childResource->getNestedResourceFields($this->request);

        $parentField = collect($fields)->firstWhere('type', 'parent');
        $this->assertNotNull($parentField);
        $this->assertEquals('Parent', $parentField['label']);
        $this->assertInstanceOf(TestNestedResource::class, $parentField['resource']);
    }

    public function test_get_nested_resource_fields_includes_children_for_parent(): void
    {
        $fields = $this->rootResource->getNestedResourceFields($this->request);

        $childrenField = collect($fields)->firstWhere('type', 'children');
        $this->assertNotNull($childrenField);
        $this->assertEquals('Children', $childrenField['label']);
        $this->assertEquals(1, $childrenField['count']);
    }

    public function test_get_nested_resource_fields_excludes_parent_when_disabled(): void
    {
        TestNestedResource::$showParentInBreadcrumbs = false;

        $fields = $this->childResource->getNestedResourceFields($this->request);

        $parentField = collect($fields)->firstWhere('type', 'parent');
        $this->assertNull($parentField);

        // Reset for other tests
        TestNestedResource::$showParentInBreadcrumbs = true;
    }

    public function test_get_nested_resource_fields_excludes_children_when_disabled(): void
    {
        TestNestedResource::$showChildrenInDetail = false;

        $fields = $this->rootResource->getNestedResourceFields($this->request);

        $childrenField = collect($fields)->firstWhere('type', 'children');
        $this->assertNull($childrenField);

        // Reset for other tests
        TestNestedResource::$showChildrenInDetail = true;
    }

    // ========================================
    // Breadcrumb URL Tests
    // ========================================

    public function test_get_breadcrumb_url_returns_correct_format(): void
    {
        $reflection = new \ReflectionClass($this->childResource);
        $method = $reflection->getMethod('getBreadcrumbUrl');
        $method->setAccessible(true);

        $url = $method->invoke($this->childResource, $this->rootResource);

        $this->assertEquals('/admin/resources/test-nested/1', $url);
    }
}
