<?php

namespace App\Http\Requests\V1\ConfigurationItem;

use Illuminate\Foundation\Http\FormRequest;

class CreateConfigurationItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ConfigurationItem::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:server,workstation,laptop,network_device,printer,software,license,virtual_machine,mobile_device,other',
            'status' => 'required|string|in:active,inactive,maintenance,retired,disposed',
            'serial_number' => 'nullable|string|max:255|unique:configuration_items,serial_number',
            'asset_tag' => 'nullable|string|max:255|unique:configuration_items,asset_tag',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date|after:purchase_date',
            'owner_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'parent_id' => 'nullable|exists:configuration_items,id',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'operating_system' => 'nullable|string|max:255',
            'cpu_info' => 'nullable|string|max:255',
            'ram_size' => 'nullable|integer|min:0',
            'storage_size' => 'nullable|integer|min:0',
            'attributes' => 'nullable|array',
            'attributes.*' => 'string',
            'related_items' => 'nullable|array',
            'related_items.*' => 'exists:configuration_items,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The configuration item name is required.',
            'type.required' => 'The configuration item type is required.',
            'type.in' => 'The selected type is invalid.',
            'status.required' => 'The status is required.',
            'status.in' => 'The selected status is invalid.',
            'serial_number.unique' => 'This serial number is already registered.',
            'asset_tag.unique' => 'This asset tag is already in use.',
            'warranty_expiry.after' => 'Warranty expiry date must be after the purchase date.',
            'mac_address.regex' => 'The MAC address format is invalid.',
        ];
    }
}