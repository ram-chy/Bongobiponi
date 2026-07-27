<?php

namespace App\Services;

use App\Models\DocumentReference;
use Illuminate\Support\Str;

class DocumentReferenceService
{
    public function generateUuid(): string
    {
        return (string) Str::uuid();
    }

    public function createReference(
        string $uuid,
        string $documentType,
        int $documentId,
        ?string $parentDocumentType = null,
        ?int $parentDocumentId = null,
    ): DocumentReference {
        return DocumentReference::create([
            'uuid' => $uuid,
            'document_type' => $documentType,
            'document_id' => $documentId,
            'parent_document_type' => $parentDocumentType,
            'parent_document_id' => $parentDocumentId,
        ]);
    }

    public function getDocumentByUuid(string $uuid, string $documentType): ?DocumentReference
    {
        return DocumentReference::where('uuid', $uuid)
            ->where('document_type', $documentType)
            ->first();
    }

    public function getChildren(string $uuid): iterable
    {
        $parent = DocumentReference::where('uuid', $uuid)->first();

        if (! $parent) {
            return collect();
        }

        return DocumentReference::where('parent_document_id', $parent->id)
            ->where('parent_document_type', $parent->document_type)
            ->get();
    }

    public function getParentChain(string $uuid): array
    {
        $chain = [];
        $current = DocumentReference::where('uuid', $uuid)->first();

        while ($current && $current->parent_document_id) {
            $chain[] = $current;
            $parentRef = DocumentReference::where('document_type', $current->parent_document_type)
                ->where('document_id', $current->parent_document_id)
                ->first();
            $current = $parentRef;
        }

        return $chain;
    }
}
