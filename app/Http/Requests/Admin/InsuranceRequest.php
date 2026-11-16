<?php

namespace App\Http\Requests\Admin;

use App\Enums\ChannelType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Partner create/edit form. The balance is never edited here: creating takes an opening deposit
 * (recorded in the ledger), and later money movements go through the ledger form.
 */
class InsuranceRequest extends FormRequest
{
    /** decimal(15,2) holds up to 13 integer digits. */
    public const MAX_AMOUNT = 9_999_999_999_999;

    /**
     * Authorization is done in the controller through InsurancePolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('post');

        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'insurance_date' => ['required', 'date'],
            'expired_at' => ['required', 'date', 'after_or_equal:insurance_date'],
            'status' => ['required', 'in:0,1'],
            'avatar' => [$creating ? 'required' : 'nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            'channels' => ['required', 'array', 'min:1', 'max:30'],
            'channels.*.type' => ['required', Rule::enum(ChannelType::class)],
            'channels.*.value' => ['required', 'string', 'max:500'],
            'channels.*.is_official' => ['nullable', 'boolean'],
            'channels.*.verified' => ['nullable', 'boolean'],
            'channels.*.bank' => ['nullable', 'string', 'max:100'],
            'channels.*.holder' => ['nullable', 'string', 'max:255'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.title' => ['required', 'string', 'max:255'],
        ];

        if ($creating) {
            $rules['opening_deposit'] = ['required', 'integer', 'min:0', 'max:'.self::MAX_AMOUNT];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ tên thành viên.',
            'full_name.max' => 'Họ tên tối đa :max ký tự.',
            'insurance_date.required' => 'Vui lòng chọn ngày tham gia.',
            'insurance_date.date' => 'Ngày tham gia không hợp lệ.',
            'expired_at.required' => 'Vui lòng chọn ngày hết hạn.',
            'expired_at.date' => 'Ngày hết hạn không hợp lệ.',
            'expired_at.after_or_equal' => 'Ngày hết hạn phải sau hoặc bằng ngày tham gia.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'avatar.required' => 'Vui lòng tải lên ảnh đại diện.',
            'avatar.image' => 'Ảnh đại diện phải là hình ảnh.',
            'avatar.mimes' => 'Ảnh đại diện phải có định dạng jpeg, png, jpg hoặc gif.',
            'avatar.max' => 'Ảnh đại diện tối đa 5MB.',
            'channels.required' => 'Vui lòng thêm ít nhất một kênh liên hệ / thanh toán.',
            'channels.min' => 'Vui lòng thêm ít nhất một kênh liên hệ / thanh toán.',
            'channels.max' => 'Tối đa :max kênh cho một thành viên.',
            'channels.*.type.required' => 'Vui lòng chọn loại kênh.',
            'channels.*.type.enum' => 'Loại kênh không hợp lệ.',
            'channels.*.value.required' => 'Vui lòng nhập giá trị cho từng kênh.',
            'channels.*.value.max' => 'Giá trị kênh tối đa :max ký tự.',
            'channels.*.bank.max' => 'Tên ngân hàng tối đa :max ký tự.',
            'channels.*.holder.max' => 'Tên chủ tài khoản tối đa :max ký tự.',
            'services.required' => 'Vui lòng thêm ít nhất một dịch vụ.',
            'services.min' => 'Vui lòng thêm ít nhất một dịch vụ.',
            'services.*.title.required' => 'Vui lòng nhập tên dịch vụ.',
            'services.*.title.max' => 'Tên dịch vụ tối đa :max ký tự.',
            'opening_deposit.required' => 'Vui lòng nhập số tiền ký quỹ ban đầu (nhập 0 nếu chưa nạp).',
            'opening_deposit.integer' => 'Số tiền ký quỹ ban đầu phải là số nguyên (VND).',
            'opening_deposit.min' => 'Số tiền ký quỹ ban đầu không được âm.',
            'opening_deposit.max' => 'Số tiền ký quỹ ban đầu quá lớn.',
        ];
    }
}
