
# `BaseModel` Framework Documentation

`BaseModel` is an abstract Active Record and QueryBuilderV2 bridge class designed for high-performance PHP applications. It supports both **PHP-FPM** environments and **OpenSwoole Coroutine-based** concurrency with automatic context awareness, connection pool isolation, soft deletes, lifecycle hooks, and fluid query building.

---

## Key Features

1. **Dual Engine Support (OpenSwoole & PHP-FPM):** Automatically handles Swoole Coroutine context switching and connection pooling without deadlocks, while seamlessly falling back to standard PHP-FPM.
2. **Active Record & Dynamic Query Scope:** Execute queries via static calls like `User::where(...)` or instance-based queries using `$user->newQuery()`.
3. **Soft Delete Capability:** Built-in soft deletion with scopes (`withTrashed()`, `onlyTrashed()`, `restoreById()`, `forceDeleteById()`).
4. **Lifecycle Hooks:** Extensible hooks (`beforeSave`, `beforeCreate`, `afterCreate`, `beforeUpdate`, `afterUpdate`, `beforeDelete`, `afterDelete`) for clean domain event driven code.
5. **Multi-Database Connection Switching:** On-the-fly connection targeting using `Model::onConnection('mysql_read')`.

---

## Configuration & Usage Guide

### Defining a Model

Inherit from `App\Core\Database\BaseModel` and set the static configuration properties.

```php
<?php

namespace App\Models;

use App\Core\Database\BaseModel;

class User extends BaseModel
{
    /**
     * Target database table (Defaults to pluralized class name if left empty: "users")
     */
    protected static string $table = 'users';

    /**
     * Primary key column name
     */
    protected static string $primaryKey = 'id';

    /**
     * Enable or disable Soft Deletes
     */
    protected static bool $useSoftDeletes = true;

    /**
     * Soft delete timestamp column
     */
    protected static string $deletedAtColumn = 'deleted_at';
}

```

---

## Core Operations

### 1. Basic CRUD

```php
// Create
$userId = User::create([
    'name'  => 'John Doe',
    'email' => 'john@example.com'
]);

// Read
$users = User::all();
$user  = User::find(1);
$activeUsers = User::where('status', '=', 'active')->get();

// Update
User::updateById(1, [
    'name' => 'John Updated'
]);

// Delete
User::deleteById(1); // Performs soft delete if enabled
User::deleteById(1, true); // Force hard delete

```

### 2. Soft Deletes

```php
// Query including soft-deleted items
$allUsers = User::withTrashed()->get();

// Query only soft-deleted items
$trashedUsers = User::onlyTrashed()->get();

// Restore a soft-deleted record
User::restoreById(1);

// Permanently delete a record
User::forceDeleteById(1);

```

### 3. Lifecycle Hooks

Override these protected static methods in your child model class to automatically intercept lifecycle steps:

```php
protected static function beforeCreate(array &$data): void
{
    // Mutate data before creation
    $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    $data['created_at'] = date('Y-m-d H:i:s');
}

protected static function afterCreate(string|false $id, array $data): void
{
    // Trigger notifications/events after creation
    write_log('info', "User created with ID: {$id}");
}

```

### 4. Multi-Connection & Multi-Tenant Support

Switch database connections per query seamlessly across both OpenSwoole and FPM:

```php
// Run query on a secondary database connection (e.g., read replica or secondary tenant)
$logs = AuditLog::onConnection('logging_db')
    ->where('level', '=', 'error')
    ->get();

```

---

## Generator Stubs CLI / Code Generators

Below are the command to build your basic model with CLI generator (e.g., `./generator DashboardModel`).

### Generator

Use this for basic template models.

```bash
 # Examples:
 *   ./generator DashboardModel
 *   ./generator AdminSettingsModel App/Models/Admin

 # Others:
 *   ./generator AdminSettingsController app/Controllers/Admin
 *   ./generator UserApiController App/Controllers/Api
 *   ./generator detail_pesanan views/order

```
