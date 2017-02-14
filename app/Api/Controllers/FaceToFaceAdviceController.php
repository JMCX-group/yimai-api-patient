<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/21
 * Time: 上午9:45
 */

namespace App\Api\Controllers;

use App\FaceToFaceAdvice;
use App\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;

class FaceToFaceAdviceController extends BaseController
{
    public function index()
    {
    }

    /**
     * 新建一个面诊
     *
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse|mixed
     */
    public function store(Request $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $data = [
            'doctor_id' => $user->id,
            'phone' => $request['phone'],
            'name' => $request['name'],
            'price' => $user->fee_face_to_face, //医生的收入
            'transaction_id' => '',
            'status' => 'wait_pay'
        ];

        try {
            $faceToFaceAdvice = FaceToFaceAdvice::create($data);
        } catch (JWTException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }

        $QrCode = '';
        
        $price = intval($faceToFaceAdvice['price']) + 0;

        return [
            'data' => [
                'id' => $faceToFaceAdvice->id,
                'price' => $price,
                'qr_code' => '/qrcode/test.png'
            ]
        ];
    }
}
