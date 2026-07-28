<?php

namespace App\Observers;

use App\Models\AuditLog;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stringable;

class AuditModelObserver
{
    /**
     * Fields that are too sensitive or too noisy to store verbatim in audit metadata.
     *
     * @var array<int, string>
     */
    private array $summarizedFields = [
        'password',
        'remember_token',
        'content',
        'payload_json',
        'source_row_json',
        'ai_prompt_json',
        'website_settings',
        'pddikti_payload_json',
        'development_installments_json',
        'tuition_installments_json',
        'ukt_installments_json',
        'installment_schedule_json',
        'custom_installment_schedule_json',
    ];

    public function created(Model $model): void
    {
        $this->write('model_created', $model);
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $original = [];

        foreach (array_keys($changes) as $field) {
            $original[$field] = $model->getOriginal($field);
        }

        $this->write('model_updated', $model, [
            'changes' => $this->summarize($changes),
            'original' => $this->summarize($original),
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->write('model_deleted', $model, [
            'snapshot' => $this->summarize($model->getAttributes()),
            'restore_payload' => $this->restorePayload($model),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function restorePayload(Model $model): array
    {
        return collect($model->getAttributes())
            ->reject(fn (mixed $value, string $field): bool => in_array($field, ['password', 'remember_token'], true))
            ->map(fn (mixed $value): mixed => $this->normalize($value))
            ->all();
    }

    private function write(string $event, Model $model, array $metadata = []): void
    {
        if (app()->runningInConsole() || $model instanceof AuditLog || ! auth()->check()) {
            return;
        }

        AuditLog::query()->create([
            'event' => $event,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'user_id' => auth()->id(),
            'metadata' => array_merge([
                'model' => $model::class,
                'label' => $this->label($model),
            ], $metadata),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function summarize(array $values): array
    {
        return collect($values)
            ->mapWithKeys(function (mixed $value, string $field): array {
                if (in_array($field, $this->summarizedFields, true)) {
                    return [$field => $this->summaryValue($value)];
                }

                if (is_array($value)) {
                    return [$field => $this->summaryValue($value)];
                }

                if (is_string($value)) {
                    return [$field => Str::limit($value, 300)];
                }

                return [$field => $this->normalize($value)];
            })
            ->all();
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof Stringable) {
            return Str::limit((string) $value, 300);
        }

        if (is_object($value)) {
            return '['.class_basename($value).']';
        }

        return $value;
    }

    private function summaryValue(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        if (is_array($value)) {
            return '[data JSON '.count($value).' item]';
        }

        if (is_string($value)) {
            return '[teks panjang '.strlen($value).' karakter]';
        }

        return '[data]';
    }

    private function label(Model $model): string
    {
        foreach (['name', 'title', 'full_name', 'invoice_number', 'nim', 'email', 'selection_number'] as $field) {
            $value = $model->getAttribute($field);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }
}


