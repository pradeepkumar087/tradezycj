<?php

namespace Botble\Ecommerce\Http\Controllers;

use Assets;
use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Forms\ProductForm;
use Botble\Ecommerce\Http\Requests\ProductRequest;
use Botble\Ecommerce\Repositories\Interfaces\GroupedProductInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProductVariationInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProductVariationItemInterface;
use Botble\Ecommerce\Services\Products\StoreAttributesOfProductService;
use Botble\Ecommerce\Services\Products\StoreProductService;
use Botble\Ecommerce\Services\StoreProductTagService;
use Botble\Ecommerce\Tables\ProductTable;
use Botble\Ecommerce\Traits\ProductActionsTrait;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class CjdroppingController extends BaseController
{
    //use ProductActionsTrait;

    /**
     * @param ProductTable $dataTable
     * @return Factory|View
     * @throws Throwable
     */
    public function index(ProductTable $dataTable)
    {
       echo "hello"; die;
    }

    /**
     * @param FormBuilder $formBuilder
     * @return string
     */
  
}
