<?php

namespace Hanafalah\ModuleOpnameStock\Concerns\Emr;

trait HasExaminationSummary
{
    public function examinationSummary()
    {
        return $this->morphOneModel('ExaminationSummary', 'reference');
    }
}
