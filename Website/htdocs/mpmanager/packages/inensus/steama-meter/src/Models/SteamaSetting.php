<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\BaseModel;
use App\Relations\BelongsToMorph;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SteamaSetting extends BaseModel
{
    protected $table = 'steama_settings';

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
        return BelongsToMorph::build($this, SteamaSmsSetting::class, 'setting');
    }
    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsToMorph
     */
    public function settingSync(): BelongsToMorph
    {
        return BelongsToMorph::build($this, SteamaSyncSetting::class, 'setting');
    }
}
