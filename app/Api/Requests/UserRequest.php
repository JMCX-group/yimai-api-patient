<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/20
 * Time: 下午2:27
 */

namespace App\Api\Requests;

use App\Http\Requests\Request;

/**
 * Class UserRequest
 * @package App\Api\Requests
 */
class UserRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'between:1,10',
            'head_img' => 'mimes:jpg,jpeg,png',
            'email' => 'email'
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'required' => ':attribute不能为空',
            'between' => ':attribute长度必须在:min和:max之间',
            'mimes' => ':attribute需为jpg/jpeg/png文件',
            'email' => '电子邮箱格式错误'
        ];
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => '姓名',
            'head_img' => '用户头像'
        ];
    }

    /**
     * @param array $errors
     * @return mixed
     */
    public function response(array $errors)
    {
        return response()->json(['message' => current($errors)[0]], 403);
    }
}
