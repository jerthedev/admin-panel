<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Dashboards\Concerns;

use Illuminate\Http\Request;
use JTD\AdminPanel\Support\DashboardMetadataManager;
use JTD\AdminPanel\Support\DashboardConfigurationManager;

/**
 * Has Metadata Trait
 * 
 * Provides metadata and configuration capabilities for dashboards
 * including icons, descriptions, categories, ordering, and display preferences.
 */
trait HasMetadata
{
    /**
     * Dashboard metadata.
     */
    protected array $metadata = [];

    /**
     * Dashboard configuration.
     */
    protected array $configuration = [];

    /**
     * Dashboard tags.
     */
    protected array $tags = [];

    /**
     * Dashboard priority for ordering.
     */
    protected int $priority = 100;

    /**
     * Whether the dashboard is visible.
     */
    protected bool $visible = true;

    /**
     * Whether the dashboard is enabled.
     */
    protected bool $enabled = true;

    /**
     * Dashboard color scheme.
     */
    protected ?string $color = null;
    protected ?string $backgroundColor = null;
    protected ?string $textColor = null;

    /**
     * Dashboard author information.
     */
    protected ?string $author = null;

    /**
     * Dashboard version.
     */
    protected ?string $version = null;

    /**
     * Dashboard permissions.
     */
    protected array $permissions = [];

    /**
     * Dashboard dependencies.
     */
    protected array $dependencies = [];

    /**
     * Dashboard display options.
     */
    protected array $displayOptions = [];

    /**
     * Get dashboard description.
     */
    public function description(): ?string
    {
        return $this->metadata['description'] ?? null;
    }

    /**
     * Set dashboard description.
     */
    public function withDescription(string $description): static
    {
        $this->metadata['description'] = $description;
        return $this;
    }

    /**
     * Get dashboard icon.
     */
    public function icon(): ?string
    {
        return $this->metadata['icon'] ?? null;
    }

    /**
     * Set dashboard icon.
     */
    public function withIcon(string $icon): static
    {
        $this->metadata['icon'] = $icon;
        return $this;
    }

    /**
     * Get dashboard category.
     */
    public function category(): ?string
    {
        return $this->metadata['category'] ?? 'General';
    }

    /**
     * Set dashboard category.
     */
    public function withCategory(string $category): static
    {
        $this->metadata['category'] = $category;
        return $this;
    }

    /**
     * Get dashboard tags.
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Set dashboard tags.
     */
    public function withTags(array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Add a tag to the dashboard.
     */
    public function addTag(string $tag): static
    {
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }
        return $this;
    }

    /**
     * Get dashboard priority.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Set dashboard priority.
     */
    public function withPriority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Check if dashboard is visible.
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * Set dashboard visibility.
     */
    public function visible(bool $visible = true): static
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * Hide the dashboard.
     */
    public function hidden(): static
    {
        return $this->visible(false);
    }

    /**
     * Check if dashboard is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set dashboard enabled state.
     */
    public function enabled(bool $enabled = true): static
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Disable the dashboard.
     */
    public function disabled(): static
    {
        return $this->enabled(false);
    }

    /**
     * Get dashboard color.
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * Set dashboard color.
     */
    public function withColor(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Get dashboard background color.
     */
    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    /**
     * Set dashboard background color.
     */
    public function withBackgroundColor(string $color): static
    {
        $this->backgroundColor = $color;
        return $this;
    }

    /**
     * Get dashboard text color.
     */
    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    /**
     * Set dashboard text color.
     */
    public function withTextColor(string $color): static
    {
        $this->textColor = $color;
        return $this;
    }

    /**
     * Get dashboard author.
     */
    public function getAuthor(): ?string
    {
        return $this->author;
    }

    /**
     * Set dashboard author.
     */
    public function withAuthor(string $author): static
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Get dashboard version.
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Set dashboard version.
     */
    public function withVersion(string $version): static
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Get dashboard permissions.
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Set dashboard permissions.
     */
    public function withPermissions(array $permissions): static
    {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * Add a permission requirement.
     */
    public function requiresPermission(string $permission): static
    {
        if (!in_array($permission, $this->permissions)) {
            $this->permissions[] = $permission;
        }
        return $this;
    }

    /**
     * Get dashboard dependencies.
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Set dashboard dependencies.
     */
    public function withDependencies(array $dependencies): static
    {
        $this->dependencies = $dependencies;
        return $this;
    }

    /**
     * Add a dependency.
     */
    public function dependsOn(string $dependency): static
    {
        if (!in_array($dependency, $this->dependencies)) {
            $this->dependencies[] = $dependency;
        }
        return $this;
    }

    /**
     * Get dashboard display options.
     */
    public function getDisplayOptions(): array
    {
        return $this->displayOptions;
    }

    /**
     * Set dashboard display options.
     */
    public function withDisplayOptions(array $options): static
    {
        $this->displayOptions = array_merge($this->displayOptions, $options);
        return $this;
    }

    /**
     * Set a display option.
     */
    public function setDisplayOption(string $key, $value): static
    {
        $this->displayOptions[$key] = $value;
        return $this;
    }

    /**
     * Get dashboard configuration.
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * Set dashboard configuration.
     */
    public function withConfiguration(array $configuration): static
    {
        $this->configuration = array_merge($this->configuration, $configuration);
        return $this;
    }

    /**
     * Set a configuration option.
     */
    public function setConfiguration(string $key, $value): static
    {
        $this->configuration[$key] = $value;
        return $this;
    }

    /**
     * Get all dashboard metadata.
     */
    public function getMetadata(): array
    {
        return DashboardMetadataManager::getMetadata($this);
    }

    /**
     * Set dashboard metadata.
     */
    public function setMetadata(array $metadata): static
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        DashboardMetadataManager::setMetadata($this, $this->metadata);
        return $this;
    }

    /**
     * Get dashboard configuration with user preferences.
     */
    public function getConfigurationWithPreferences(Request $request = null): array
    {
        return DashboardConfigurationManager::getConfiguration($this, $request);
    }

    /**
     * Check if dashboard meets dependency requirements.
     */
    public function checkDependencies(): bool
    {
        foreach ($this->dependencies as $dependency) {
            if (!class_exists($dependency) && !function_exists($dependency)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has required permissions.
     */
    public function checkPermissions(Request $request): bool
    {
        $user = $request->user();
        if (!$user) {
            return empty($this->permissions);
        }

        foreach ($this->permissions as $permission) {
            if (!$user->can($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get dashboard JSON representation with metadata.
     */
    public function toArrayWithMetadata(): array
    {
        $array = $this->toArray();
        $array['metadata'] = $this->getMetadata();
        $array['configuration'] = $this->getConfiguration();
        return $array;
    }

    /**
     * Boot the metadata trait.
     */
    protected function bootHasMetadata(): void
    {
        // Initialize default metadata
        $this->metadata = array_merge([
            'name' => $this->name(),
            'description' => null,
            'icon' => null,
            'category' => 'General',
        ], $this->metadata);
    }
}
