<?php

namespace App\Http\Requests;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;

class StoreWishlistDealOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof Conversation
            && $conversation->wishlist_request_id !== null
            && (int) $conversation->seller_id === (int) $this->user()?->user_id
            && ($this->user()?->can('view', $conversation) ?? false);
    }

    public function rules(): array
    {
        return [
            'offered_price' => ['required', 'integer', 'min:1', 'max:9999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'offered_price.required' => 'Masukkan harga penawaran.',
            'offered_price.integer' => 'Harga penawaran harus berupa angka bulat dalam Rupiah.',
            'offered_price.min' => 'Harga penawaran minimal Rp1.',
        ];
    }
}
