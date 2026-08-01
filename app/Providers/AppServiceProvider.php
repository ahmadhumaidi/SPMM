<?php

namespace App\Providers;

use App\Models\AffiliateCommission;
use App\Models\AffiliateNetwork;
use App\Models\Campus;
use App\Models\ClassTrack;
use App\Models\EducationNews;
use App\Models\FeeScheme;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\ReferralPartner;
use App\Models\StudentBiodata;
use App\Models\StudentDocument;
use App\Models\StudentNumber;
use App\Models\StudentPayment;
use App\Models\StudentProfile;
use App\Models\StudyProgram;
use App\Models\User;
use App\Observers\AuditModelObserver;
use App\Observers\StudentPaymentCommissionObserver;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        foreach ($this->auditedModels() as $model) {
            $model::observe(AuditModelObserver::class);
        }

        StudentPayment::observe(StudentPaymentCommissionObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;

            if (! $user instanceof User) {
                return;
            }

            app(AuditLogger::class)->record('admin_login', $user, [
                'label' => $user->name ?: $user->email,
                'email' => $user->email,
                'role' => $user->role?->value,
                'ip' => request()->ip(),
                'user_agent' => str(request()->userAgent())->limit(300)->toString(),
            ], $user);
        });

        RateLimiter::for('payment-webhooks', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('meta-leads', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }

    /**
     * @return array<int, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private function auditedModels(): array
    {
        return [
            AffiliateCommission::class,
            AffiliateNetwork::class,
            Campus::class,
            StudyProgram::class,
            ClassTrack::class,
            FeeScheme::class,
            Lead::class,
            Invoice::class,
            StudentProfile::class,
            StudentBiodata::class,
            StudentDocument::class,
            StudentNumber::class,
            StudentPayment::class,
            EducationNews::class,
            ReferralPartner::class,
            User::class,
        ];
    }
}
