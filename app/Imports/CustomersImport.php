<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithPreCalculateFormulas;

class CustomersImport implements ToModel,WithHeadingRow, WithCalculatedFormulas
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {

        // return new Customer([
        //     'first_name'  => $row['first_name'] ?? null,
        //     'last_name'  => $row['last_name'] ?? null,
        //     'email' => $row['email'] ?? null,
        //     'gender	' => $row['gender'] ?? null,
        //     'phone' => $row['phone'] ?? null,
        //     'country' => $row['country'] ?? null,
        //     'city' => $row['city'] ?? null,
        // ]);
    }
}
