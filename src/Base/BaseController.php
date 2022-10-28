<?php
/**
 * Created by vim.
 * User: huguopeng
 * Date: 2022/10/28
 * Time: 09:09:02
 * By: BaseController.php
 */
namespace lumenFrame\Base;

use common\Errno;
use common\Utils\Route;
use frame\Exceptions\BaseException;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller;

class BaseController extends Controller
{
    //
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        $this->app = app();
    }

    // 待优化 2022-07-02
    protected function execute()
    {
        $route = request()->route()[1];
        $route = Route::getCurrentRoute($route['uses']);
        $className = 'App\\Module\\' . $route['controller'] . '\\Service\\' . ucwords($route['action']) . 'Service';
        $paramObj = 'App\\Module\\' . $route['controller'] . '\\Param\\' . ucwords($route['action']) . 'Param';
        if (class_exists($className) && class_exists($paramObj)) {
            $obj = new $className();
            return $obj->execute($this->app->get($paramObj));
        } else {
            Log::error($className." 或 ".$paramObj." 类不存在");
            throw new BaseException(Errno::SERVER_INTERNAL_FAIL);
        }
    }

    protected function json($data = null)
    {
        if (!$data) {
            $data = null;
        }
        $output = [];
        $output['code'] = Errno::SUCCESS;
        $output['data'] = $data;
        $output['msg'] = Errno::$msg[Errno::SUCCESS];
        return response()->json($output);
    }

}
