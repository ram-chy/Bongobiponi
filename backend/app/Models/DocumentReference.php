<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'uuid',
    'document_type',
    'document_id',
    'parent_document_type',
    'parent_document_id',
])]
class DocumentReference extends Model
{
    //
}
