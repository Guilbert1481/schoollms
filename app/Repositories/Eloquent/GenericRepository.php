<?php

namespace App\Repositories\Eloquent;

// Important Imports for the "Big Switch" later
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

abstract class GenericRepository
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->applyTenantConnection();
    }

    /**
     * THE EDITABLE SECTION:
     * This is your "Control Room" for Sophentis.
     */
    protected function applyTenantConnection()
    {
        /** * CURRENT (Single DB): 
         * We do nothing here because we use the default connection.
         */
         
        /** * FUTURE (Database-Per-Tenant): 
         * When you are ready, you will uncomment and use logic like this:
         * * $dbName = Auth::user()->school->database_name;
         * Config::set('database.connections.tenant.database', $dbName);
         * DB::purge('tenant');
         * $this->model->setConnection('tenant');
         */
    }

    /**
     * READ ALL
     */
    public function all(): Collection
    {
        // One place to manage the school_id filter for everyone!
        return $this->model->where('school_id', Auth::user()->school_id)->get();
    }

    /**
     * READ ONE
     */
    public function find(int $id): ?Model
    {
        return $this->model->where('school_id', Auth::user()->school_id)->findOrFail($id);
    }

    /**
     * CREATE
     */
    /**
     * CREATE
     */
    public function create(array $data): Model
    {
        // Only inject school_id if the user is logged in and it's not already set
        if (Auth::check() && empty($data['school_id'])) {
            $data['school_id'] = Auth::user()->school_id;
        }
        
        // If it's STILL empty (like during dev), your Model's 'booted' method 
        // will now successfully kick in and set it to 1.
        return $this->model->create($data);
    }

    /**
     * UPDATE
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->find($id);
        return $record->update($data);
    }

    /**
     * DELETE
     */
    public function delete(int $id): bool
    {
        $record = $this->find($id);
        return $record->delete();
    }
}