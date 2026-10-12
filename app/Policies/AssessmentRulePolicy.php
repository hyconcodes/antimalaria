<?php

namespace App\Policies;

use App\Enums\RuleStatus;
use App\Models\AssessmentRule;
use App\Models\User;

class AssessmentRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function update(User $user, AssessmentRule $assessmentRule): bool
    {
        return $this->isAdministrator($user)
            && in_array($assessmentRule->status, [RuleStatus::Draft, RuleStatus::PendingReview], true);
    }

    public function submit(User $user, AssessmentRule $assessmentRule): bool
    {
        return $this->isAdministrator($user) && $assessmentRule->status === RuleStatus::Draft;
    }

    public function approve(User $user, AssessmentRule $assessmentRule): bool
    {
        return $this->isAdministrator($user) && $assessmentRule->status === RuleStatus::PendingReview;
    }

    public function activate(User $user, AssessmentRule $assessmentRule): bool
    {
        return $this->isAdministrator($user)
            && $assessmentRule->status === RuleStatus::Approved
            && ! $assessmentRule->enabled;
    }

    public function retire(User $user, AssessmentRule $assessmentRule): bool
    {
        return $this->isAdministrator($user) && $assessmentRule->status !== RuleStatus::Retired;
    }

    private function isAdministrator(User $user): bool
    {
        return in_array(
            mb_strtolower($user->email),
            config('resistance-assessment.admin_emails', []),
            true,
        );
    }
}
