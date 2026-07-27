<?php

namespace App\Services;

class SafeDeleteResult
{
    private function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $reason = null,
        public readonly ?string $referenceModule = null,
        public readonly ?int $referenceCount = null,
        public readonly ?array $sampleRecords = null,
        public readonly ?int $latestRecordId = null,
    ) {}

    public static function deleted(string $message): self
    {
        return new self(success: true, message: $message);
    }

    public static function blocked(string $message, string $reason, ?string $referenceModule = null, ?int $referenceCount = null, ?array $sampleRecords = null): self
    {
        return new self(
            success: false,
            message: $message,
            reason: $reason,
            referenceModule: $referenceModule,
            referenceCount: $referenceCount,
            sampleRecords: $sampleRecords,
        );
    }

    public static function blockedByLatestRecord(string $message, string $reason, int $latestRecordId): self
    {
        return new self(
            success: false,
            message: $message,
            reason: $reason,
            latestRecordId: $latestRecordId,
        );
    }

    public function toArray(): array
    {
        $response = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->reason !== null) {
            $response['reason'] = $this->reason;
        }

        if ($this->referenceModule !== null) {
            $response['reference_module'] = $this->referenceModule;
        }

        if ($this->referenceCount !== null) {
            $response['reference_count'] = $this->referenceCount;
        }

        if ($this->sampleRecords !== null) {
            $response['sample_records'] = $this->sampleRecords;
        }

        if ($this->latestRecordId !== null) {
            $response['latest_record_id'] = $this->latestRecordId;
        }

        return $response;
    }
}
