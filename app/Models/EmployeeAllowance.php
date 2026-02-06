<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAllowance extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'employee_detail_id', 'name','amount',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeDetail::class, 'employee_detail_id');
    }
     public function employeeDetail()
    {
        return $this->belongsTo(EmployeeDetail::class, 'employee_detail_id');
    }
}
