<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Resources\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * HasVersioning Trait.
 *
 * Provides version control and audit trail functionality for admin panel resources.
 * Enables tracking of changes over time with rollback capabilities.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
trait HasVersioning
{
    /**
     * Whether versioning is enabled for this resource.
     */
    public static bool $versioningEnabled = true;

    /**
     * The maximum number of versions to keep.
     */
    public static int $maxVersions = 50;

    /**
     * Whether to version all fields or only specified ones.
     */
    public static bool $versionAllFields = true;

    /**
     * Fields to include in versioning (if not versioning all fields).
     */
    public static array $versionedFields = [];

    /**
     * Fields to exclude from versioning.
     */
    public static array $excludedFields = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Whether to automatically create versions on save.
     */
    public static bool $autoVersion = true;

    /**
     * Whether to compress version data.
     */
    public static bool $compressVersions = false;

    /**
     * Create a new version of the resource.
     */
    public function createVersion(?string $reason = null, array $metadata = []): array
    {
        if (! static::$versioningEnabled) {
            return [];
        }

        $versionData = [
            'resource_type' => static::class,
            'resource_id' => $this->getKey(),
            'version_number' => $this->getNextVersionNumber(),
            'data' => $this->getVersionableData(),
            'reason' => $reason,
            'metadata' => $metadata,
            'user_id' => $this->getCurrentUserId(),
            'created_at' => now()->toDateTimeString(),
            'checksum' => $this->calculateChecksum($this->getVersionableData()),
        ];

        if (static::$compressVersions) {
            $versionData['data'] = $this->compressData($versionData['data']);
            $versionData['compressed'] = true;
        }

        // Store version (in a real implementation, this would save to database)
        $this->storeVersion($versionData);

        // Clean up old versions if needed
        $this->cleanupOldVersions();

        return $versionData;
    }

    /**
     * Get all versions for this resource.
     */
    public function getVersions(): Collection
    {
        if (! static::$versioningEnabled) {
            return collect();
        }

        // In a real implementation, this would query the database
        return $this->loadVersionsFromStorage();
    }

    /**
     * Get a specific version by number.
     */
    public function getVersion(int $versionNumber): ?array
    {
        if (! static::$versioningEnabled) {
            return null;
        }

        $versions = $this->getVersions();

        return $versions->firstWhere('version_number', $versionNumber);
    }

    /**
     * Get the latest version.
     */
    public function getLatestVersion(): ?array
    {
        if (! static::$versioningEnabled) {
            return null;
        }

        $versions = $this->getVersions();

        return $versions->sortByDesc('version_number')->first();
    }

    /**
     * Restore the resource to a specific version.
     */
    public function restoreToVersion(int $versionNumber, ?string $reason = null): bool
    {
        if (! static::$versioningEnabled) {
            return false;
        }

        $version = $this->getVersion($versionNumber);

        if (! $version) {
            return false;
        }

        $data = $version['data'];

        if ($version['compressed'] ?? false) {
            $data = $this->decompressData($data);
        }

        // Create a version before restoring
        $this->createVersion($reason ?? "Restored to version {$versionNumber}");

        // Restore the data
        foreach ($data as $field => $value) {
            if ($this->isVersionableField($field)) {
                $this->resource->{$field} = $value;
            }
        }

        return $this->resource->save();
    }

    /**
     * Compare two versions.
     */
    public function compareVersions(int $fromVersion, int $toVersion): array
    {
        if (! static::$versioningEnabled) {
            return [];
        }

        $from = $this->getVersion($fromVersion);
        $to = $this->getVersion($toVersion);

        if (! $from || ! $to) {
            return [];
        }

        $fromData = $from['compressed'] ?? false ? $this->decompressData($from['data']) : $from['data'];
        $toData = $to['compressed'] ?? false ? $this->decompressData($to['data']) : $to['data'];

        $changes = [];
        $allFields = array_unique(array_merge(array_keys($fromData), array_keys($toData)));

        foreach ($allFields as $field) {
            $oldValue = $fromData[$field] ?? null;
            $newValue = $toData[$field] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                    'type' => $this->getChangeType($oldValue, $newValue),
                ];
            }
        }

        return [
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'changes' => $changes,
            'total_changes' => count($changes),
        ];
    }

    /**
     * Get version statistics.
     */
    public function getVersionStats(): array
    {
        if (! static::$versioningEnabled) {
            return [
                'enabled' => false,
                'total_versions' => 0,
            ];
        }

        $versions = $this->getVersions();

        return [
            'enabled' => true,
            'total_versions' => $versions->count(),
            'latest_version' => $versions->max('version_number') ?? 0,
            'oldest_version' => $versions->min('version_number') ?? 0,
            'total_size' => $this->calculateTotalSize($versions),
            'average_size' => $this->calculateAverageSize($versions),
            'compression_enabled' => static::$compressVersions,
            'auto_versioning' => static::$autoVersion,
            'max_versions' => static::$maxVersions,
        ];
    }

    /**
     * Get the versionable data for the current resource.
     */
    protected function getVersionableData(): array
    {
        $data = $this->resource->toArray();

        if (static::$versionAllFields) {
            // Remove excluded fields
            foreach (static::$excludedFields as $field) {
                unset($data[$field]);
            }
        } else {
            // Only include specified fields
            $data = array_intersect_key($data, array_flip(static::$versionedFields));
        }

        return $data;
    }

    /**
     * Check if a field should be versioned.
     */
    protected function isVersionableField(string $field): bool
    {
        if (static::$versionAllFields) {
            return ! in_array($field, static::$excludedFields);
        }

        return in_array($field, static::$versionedFields);
    }

    /**
     * Get the next version number.
     */
    protected function getNextVersionNumber(): int
    {
        $versions = $this->getVersions();
        $latestVersion = $versions->max('version_number') ?? 0;

        return $latestVersion + 1;
    }

    /**
     * Calculate checksum for version data.
     */
    protected function calculateChecksum(array $data): string
    {
        return md5(serialize($data));
    }

    /**
     * Get the current user ID.
     */
    protected function getCurrentUserId(): ?int
    {
        try {
            return Auth::id();
        } catch (\Exception $e) {
            // Fallback for testing environments
            return null;
        }
    }

    /**
     * Store version data.
     */
    protected function storeVersion(array $versionData): void
    {
        // In a real implementation, this would save to a versions table
        // For now, we'll just store in a property for testing
        if (! property_exists($this, 'storedVersions')) {
            $this->storedVersions = collect();
        }

        $this->storedVersions->push($versionData);
    }

    /**
     * Load versions from storage.
     */
    protected function loadVersionsFromStorage(): Collection
    {
        // In a real implementation, this would query the database
        return property_exists($this, 'storedVersions') ? $this->storedVersions : collect();
    }

    /**
     * Clean up old versions.
     */
    protected function cleanupOldVersions(): void
    {
        if (static::$maxVersions <= 0) {
            return;
        }

        $versions = $this->getVersions();

        if ($versions->count() > static::$maxVersions) {
            $versionsToDelete = $versions->sortByDesc('version_number')
                ->skip(static::$maxVersions);

            foreach ($versionsToDelete as $version) {
                $this->deleteVersion($version['version_number']);
            }
        }
    }

    /**
     * Delete a specific version.
     */
    protected function deleteVersion(int $versionNumber): void
    {
        if (property_exists($this, 'storedVersions')) {
            $this->storedVersions = $this->storedVersions->reject(function ($version) use ($versionNumber) {
                return $version['version_number'] === $versionNumber;
            });
        }
    }

    /**
     * Compress version data.
     */
    protected function compressData(array $data): string
    {
        return base64_encode(gzcompress(serialize($data)));
    }

    /**
     * Decompress version data.
     */
    protected function decompressData(string $compressedData): array
    {
        return unserialize(gzuncompress(base64_decode($compressedData)));
    }

    /**
     * Get the type of change between two values.
     */
    protected function getChangeType($oldValue, $newValue): string
    {
        if ($oldValue === null && $newValue !== null) {
            return 'added';
        }

        if ($oldValue !== null && $newValue === null) {
            return 'removed';
        }

        return 'modified';
    }

    /**
     * Calculate total size of all versions.
     */
    protected function calculateTotalSize(Collection $versions): int
    {
        return $versions->sum(function ($version) {
            return strlen(serialize($version['data']));
        });
    }

    /**
     * Calculate average size of versions.
     */
    protected function calculateAverageSize(Collection $versions): float
    {
        if ($versions->isEmpty()) {
            return 0;
        }

        return $this->calculateTotalSize($versions) / $versions->count();
    }
}
