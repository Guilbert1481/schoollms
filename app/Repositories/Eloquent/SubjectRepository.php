<?php

namespace App\Repositories\Eloquent;

// FIX THIS LINE: Change 'Contracts' to 'Eloquent'
use App\Repositories\Eloquent\GenericRepository; 
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;

class SubjectRepository extends GenericRepository
{
    public function __construct(Subject $model)
    {
        parent::__construct($model);
    }

    public function paginate($perPage = 10)
    {
        return $this->model->where('school_id', Auth::user()->school_id)->paginate($perPage);
    }

    public function existsByName($name)
    {
        return $this->model->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('teacher_id', Auth::id())
            ->exists();
    }
}