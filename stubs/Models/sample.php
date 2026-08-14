<?php 
/**
 *  @package Backend-PHP
 */

namespace App\Models;


use App\Core\Database\BaseModel;


class MyModel  extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected static string $table = '{{table}}';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected static string $primaryKey = '{{primaryKey}}';

    /**
     * Indicates if soft deletes are enabled.
     *
     * @var bool
     */
    protected static bool $useSoftDeletes = true;

    /**
     * The column name for soft deletes.
     *
     * @var string
     */
    protected static string $deletedAtColumn = 'deleted_at';

    /* =========================================================================
     * LIFECYCLE HOOKS
     * ========================================================================= */

    /**
     * Hook triggered before creating a record.
     */
    protected static function beforeCreate(array &$data): void
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
    }

    /**
     * Hook triggered after creating a record.
     */
    protected static function afterCreate(string|false $id, array $data): void
    {
        // Add post-creation logic (e.g., trigger event, clear cache)
    }

    /**
     * Hook triggered before updating a record.
     */
    protected static function beforeUpdate(mixed $id, array &$data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
    }

    /**
     * Hook triggered after updating a record.
     */
    protected static function afterUpdate(mixed $id, array $data, int $affectedRows): void
    {
        // Add post-update logic
    }
}

