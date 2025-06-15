<?php

namespace App\Http\Requests\V1\ConfigurationItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConfigurationItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('configurationItem'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $configItemId = $this->route('configurationItem')->id;
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:server,workstation,laptop,network_device,printer,software,license,virtual_machine,mobile_device,other',
            'status' => 'sometimes|required|string|in:active,inactive,maintenance,retired,disposed',
            'serial_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('configuration_items')->ignore($configItemId)
            ],
            'asset_tag' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('configuration_items')->ignore($configItemId)
            ],
            'manufacturer' => 'sometimes|nullable|string|max:255',
            'model' => 'sometimes|nullable|string|max:255',
            'location' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
            'purchase_date' => 'sometimes|nullable|date',
            'purchase_cost' => 'sometimes|nullable|numeric|min:0',
            'warranty_expiry' => 'sometimes|nullable|date|after:purchase_date',
            'owner_id' => 'sometimes|nullable|exists:users,id',
            'department_id' => 'sometimes|nullable|exists:departments,id',
            'parent_id' => [
                'sometimes',
                'nullable',
                'exists:configuration_items,id',
                Rule::notIn([$configItemId]) // Prevent self-reference
            ],
            'ip_address' => 'sometimes|nullable|ip',
            'mac_address' => 'sometimes|nullable|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'operating_system' => 'sometimes|nullable|string|max:255',
            'cpu_info' => 'sometimes|nullable|string|max:255',
            'ram_size' => 'sometimes|nullable|integer|min:0',
            'storage_size' => 'sometimes|nullable|integer|min:0',
            'attributes' => 'sometimes|nullable|array',
            'attributes.*' => 'string',
            'related_items' => 'sometimes|nullable|array',
            'related_items.*' => [
                'exists:configuration_items,id',
                Rule::notIn([$configItemId]) // Prevent self-reference
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'The selected type is invalid.',
            'status.in' => 'The selected status is invalid.',
            'serial_number.unique' => 'This serial number is already registered.',
            'asset_tag.unique' => 'This asset tag is already in use.',
            'warranty_expiry.after' => 'Warranty expiry date must be after the purchase date.',
            'mac_address.regex' => 'The MAC address format is invalid.',
            'parent_id.not_in' => 'A configuration item cannot be its own parent.',
            'related_items.*.not_in' => 'A configuration item cannot be related to itself.',
        ];
    }
}