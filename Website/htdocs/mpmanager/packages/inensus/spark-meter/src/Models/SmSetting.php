<?php

namespace Inensus\SparkMeter\Models;

use App\Relations\BelongsToMorph;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmSetting extends BaseModel
{
    protected $table = 'sm_settings';

    public function setting(): morphTo
    {
        return $this->morphTo();
    }

    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsToMorph
     */
    public function settingSms(): BelongsToMorph
    {
        return BelongsToMorph::build($this, SmSmsSetting::class, 'setting');
    }
    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsToMorph
     */
    public function settingSync(): BelongsToMorph
    {
        return BelongsToMorph::build($this, SmSyncSetting::class, 'setting');
    }
}
