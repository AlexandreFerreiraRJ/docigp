<?php

namespace App\Data\Repositories;

use App\Data\Models\Departament as DepartamentModel;

class Departaments extends Repository
{
    /**
     * @var string
     */
    protected $model = DepartamentModel::class;

    public function createDepartamentFromCongressman($congressman)
    {
        return $this->model::firstOrCreate([
            'name' => ($name = $this->normalizeName($congressman['Nome'])),
            'created_by_id' => 1,
            'updated_by_id' => 1,
        ]);
    }
}
