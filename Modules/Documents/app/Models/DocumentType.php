<?php

namespace Modules\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Documents\Database\Factories\DocumentTypeFactory;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'description',
        'required_by_default',
        'allowed_mimes',
        'max_size_kb',
        'digilocker_doc_type',
        'is_active',
        'ordering',
    ];

    protected function casts(): array
    {
        return [
            'required_by_default' => 'boolean',
            'allowed_mimes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function defaultAllowedMimes(): array
    {
        return $this->allowed_mimes ?: ['application/pdf', 'image/jpeg', 'image/png'];
    }

    protected static function newFactory(): DocumentTypeFactory
    {
        return DocumentTypeFactory::new();
    }
}
