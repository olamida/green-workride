<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreP2pTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_phone' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'type' => ['required', Rule::in(['cash', 'earned'])],
        ];
    }
}
