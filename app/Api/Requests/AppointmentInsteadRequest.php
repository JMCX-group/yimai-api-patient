<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/20
 * Time: 下午2:27
 */

namespace App\Api\Requests;

use App\Http\Requests\Request;

class AppointmentInsteadRequest extends Request
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
            'name' => 'required|between:1,10',
            'phone' => 'required',
            'locums_doctor' => 'required',
            'demand_hospital' => 'required',
            'demand_dept' => 'required',
            'demand_title' => 'required'
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'required' => ':attribute不能为空',
            'between' => ':attribute长度必须在:min和:max之间'
        ];
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => '姓名',
            'phone' => '手机号码',
            'locums_doctor' => '代约医生ID',
            'demand_hospital' => '代约需求-医院',
            'demand_dept' => '代约需求-科室',
            'demand_title' => '代约需求-职称'
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
