<?php

namespace App\Http\Requests;

use App\Models\MiniGrid;
use Illuminate\Foundation\Http\FormRequest;

class StoreMiniGridRequest extends FormRequest {
    public const PARAM_CLUSTER_ID = 'cluster_id';
    public const PARAM_NAME = 'name';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array {
        return [
            'name' => 'required|min:3',
            'cluster_id' => 'required',
            'geo_data' => 'required',
        ];
    }

    public function getMiniGrid(): MiniGrid {
        $miniGrid = new MiniGrid();
        $miniGrid->setClusterId($this->input(self::PARAM_CLUSTER_ID));
        $miniGrid->setName($this->input(self::PARAM_NAME));

        return $miniGrid;
    }
}
