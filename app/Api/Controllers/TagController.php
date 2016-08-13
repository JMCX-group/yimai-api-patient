<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/21
 * Time: 上午9:45
 */

namespace App\Api\Controllers;

use App\Api\Transformers\DeptTransformer;
use App\Api\Transformers\IllnessTransformer;
use App\DeptStandard;
use App\Illness;
use Illuminate\Http\Request;

class TagController extends BaseController
{
    /**
     * Get all.
     * 
     * @return mixed
     */
    public function index()
    {
        $tags = DeptStandard::where('parent_id', '!=', '0')->get();

        return $this->response->collection($tags, new DeptTransformer());
    }

    /**
     * Get illness.
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function getIllness(Request $request)
    {
        $data = null;
        if (isset($request['id']) && !empty($request['id'])) {
            $data = Illness::where('dept2_id', $request['id'])->get();
        }

        return $this->response->collection($data, new IllnessTransformer());
    }
}
