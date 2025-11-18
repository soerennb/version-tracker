<?php

namespace App\Services;

use App\Models\Software;
use Illuminate\Support\Facades\Auth;

class SoftwareService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Software
    {
        $userId = Auth::id();

        return Software::create(array_merge($data, [
            'created_by' => $userId,
            'updated_by' => $userId,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Software $software, array $data): Software
    {
        $software->fill($data);
        $software->updated_by = Auth::id();
        $software->save();

        return $software->refresh();
    }
}
