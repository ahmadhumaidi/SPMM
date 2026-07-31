<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Enums\ProspectStatus;
use App\Filament\Resources\LeadResource;
use App\Services\LeadProspectStatusService;
use App\Support\FinancialStatusLabels;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected ?string $oldProspectStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('follow_up_whatsapp')
                ->label('Follow Up WA')
                ->icon('heroicon-o-phone')
                ->url(fn (): ?string => LeadResource::whatsappFollowUpUrl($this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($this->record?->whatsapp_number)),
            Actions\ActionGroup::make(
                collect(ProspectStatus::cases())
                    ->map(fn (ProspectStatus $status): Actions\Action => Actions\Action::make('prospect_'.$status->value)
                        ->label(LeadResource::prospectStatusLabel($status->value))
                        ->icon(LeadResource::prospectStatusIcon($status->value))
                        ->color(LeadResource::prospectStatusColor($status->value))
                        ->action(function (LeadProspectStatusService $service) use ($status): void {
                            $event = $service->update($this->record, $status->value, auth()->user());
                            $this->record = $this->record->fresh();

                            Notification::make()
                                ->title('Status prospek diperbarui')
                                ->body('Meta event: '.$event->meta_status)
                                ->success()
                                ->send();
                        }))
                    ->all()
            )
                ->label('Update Prospek')
                ->icon('heroicon-o-signal'),
        ];
    }


    protected function getSavedNotification(): ?Notification
    {
        return null;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldProspectStatus = $this->record->prospect_status?->value ?? $this->record->prospect_status;

        return $data;
    }

    protected function afterSave(): void
    {
        $lead = $this->record->fresh(['studentBiodata', 'studentNumber']);
        $newProspectStatus = $lead->prospect_status?->value ?? $lead->prospect_status;

        if (filled($newProspectStatus) && $this->oldProspectStatus !== $newProspectStatus) {
            $event = app(LeadProspectStatusService::class)->update($lead, $newProspectStatus, auth()->user(), $this->oldProspectStatus);

            Notification::make()
                ->title('Status prospek dikirim ke Meta')
                ->body('Meta event: '.$event->meta_status)
                ->success()
                ->send();

            $lead = $lead->fresh(['studentBiodata', 'studentNumber']);
        }

        $biodata = $lead->studentBiodata;

        if ($biodata) {
            $biodata->update([
                'student_type' => $biodata->student_type ?: 'new',
                'campus_id' => $lead->campus_id,
                'study_program_id' => $biodata->study_program_id ?: $lead->study_program_id,
                'class_track_id' => $biodata->class_track_id ?: $lead->class_track_id,
                'name' => $biodata->name ?: $lead->full_name,
                'student_number' => $biodata->student_number ?: $lead->studentNumber?->nim,
                'financial_status' => FinancialStatusLabels::leadStatus($lead->loadMissing('studentPayments')),
                'email' => $biodata->email ?: $lead->email,
                'whatsapp_number' => $biodata->whatsapp_number ?: $lead->whatsapp_number,
            ]);

            $lead->update([
                'full_name' => $biodata->name ?: $lead->full_name,
                'email' => $biodata->email ?: $lead->email,
                'whatsapp_number' => $biodata->whatsapp_number ?: $lead->whatsapp_number,
                'study_program_id' => $biodata->study_program_id ?: $lead->study_program_id,
                'class_track_id' => $biodata->class_track_id ?: $lead->class_track_id,
            ]);
        }

        $this->js(sprintf(
            'alert(%s); window.location.href = %s',
            json_encode('Lead berhasil disimpan.'),
            json_encode($this->getResource()::getUrl('index')),
        ));
    }
}

