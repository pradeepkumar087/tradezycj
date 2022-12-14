<?php

namespace Botble\Marketplace\Http\Controllers\Fronts;

use Assets;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Repositories\Interfaces\CustomerInterface;
use Botble\Ecommerce\Repositories\Interfaces\OrderInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProductInterface;
use Botble\Marketplace\Enums\RevenueTypeEnum;
use Botble\Marketplace\Enums\WithdrawalStatusEnum;
use Botble\Marketplace\Http\Requests\BecomeVendorRequest;
use Botble\Marketplace\Models\Store;
use Botble\Marketplace\Repositories\Interfaces\RevenueInterface;
use Botble\Marketplace\Repositories\Interfaces\StoreInterface;
use Botble\Marketplace\Repositories\Interfaces\VendorInfoInterface;
use Botble\Marketplace\Repositories\Interfaces\WithdrawalInterface;
use Botble\Media\Chunks\Exceptions\UploadMissingFileException;
use Botble\Media\Chunks\Handler\DropZoneUploadHandler;
use Botble\Media\Chunks\Receiver\FileReceiver;
use EcommerceHelper;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use MarketplaceHelper;
use Response;
use GuzzleHttp\Client;
use RvMedia;
use SeoHelper;
use SlugHelper;
use Theme;
use Auth;
use Illuminate\Support\Str;
use Mimey\MimeTypes;
use Illuminate\Http\UploadedFile;


use Excel;

class ImportcjproductController
{
    /**
     * @var Repository
     */
    protected $config;

    /**
     * @var CustomerInterface
     */
    protected $customerRepository;
  

    /**
     * @var StoreInterface
     */
    protected $storeRepository;

    /**
     * @var VendorInfoInterface
     */
    protected $vendorInfoRepository;

    /**
     * @var RevenueInterface
     */
    protected $revenueRepository;

    /**
     * @var OrderInterface
     */
    protected $orderRepository;

    /**
     * @var ProductInterface
     */
    protected $productRepository;

    /**
     * @var WithdrawalInterface
     */
    protected $withdrawalRepository;

    /**
     * DashboardController constructor.
     * @param Repository $config
     * @param CustomerInterface $customerRepository
     * @param StoreInterface $storeRepository
     * @param VendorInfoInterface $vendorInfoRepository
     * @param RevenueInterface $revenueRepository
     * @param ProductInterface $productRepository
     * @param WithdrawalInterface $withdrawalRepository
     * @param OrderInterface $orderRepository
     */
    public function __construct(
        Repository $config,
        CustomerInterface $customerRepository,
        
        StoreInterface $storeRepository,
        VendorInfoInterface $vendorInfoRepository,
        RevenueInterface $revenueRepository,
        ProductInterface $productRepository,
        WithdrawalInterface $withdrawalRepository,
        OrderInterface $orderRepository
    ) {
        $this->storeRepository = $storeRepository;
        $this->customerRepository = $customerRepository;
       
        $this->vendorInfoRepository = $vendorInfoRepository;
        $this->orderRepository = $orderRepository;
        $this->revenueRepository = $revenueRepository;
        $this->productRepository = $productRepository;
        $this->withdrawalRepository = $withdrawalRepository;

        Assets::setConfig($config->get('plugins.marketplace.assets', []));

        Theme::asset()
            ->add('customer-style', 'vendor/core/plugins/ecommerce/css/customer.css');

        Theme::asset()
            ->container('footer')
            ->add('ecommerce-utilities-js', 'vendor/core/plugins/ecommerce/js/utilities.js', ['jquery'])
            ->add('cropper-js', 'vendor/core/plugins/ecommerce/libraries/cropper.js', ['jquery'])
            ->add('avatar-js', 'vendor/core/plugins/ecommerce/js/avatar.js', ['jquery']);
    }

    /**
     * @return BaseHttpResponse|Application|Factory|View
     */
    public function index(Request $request, BaseHttpResponse $response)
    {
        page_title()->setTitle(__('Import Product'));
		
		$category_data = DB::table('ec_product_categories')->where('is_cj','Yes')->get();       
		
        return MarketplaceHelper::view('dashboard.importcj',compact('category_data'));
    }


	public function get_products_from_dropship(Request $request) {
		$store = auth('customer')->user()->store;
		$store_id = $store->id;
       try{


            $client       = new Client;

            $api_url    = 'https://developers.cjdropshipping.com/api2.0/v1/authentication/getAccessToken';


            $category_api_url = 'https://developers.cjdropshipping.com/api2.0/v1/product/getCategory';

            $access_token = $this->get_access_token($api_url,$client,$request);
			
            $result_2 = $client->get($category_api_url, ['headers' => ['CJ-Access-Token' => $access_token]]);

            $categories =  json_decode($result_2->getBody()->getContents());
			
			
		//dd($categories);
            $i = 1;

            if($categories->message == 'Success'){
				
              foreach($categories->data as $cat){
				//dd($cat);
               // if($i == 1){
					
                  //$category = new Category;
                  $records = DB::table('ec_product_categories')->select('*')->where(['name' => $cat->categoryFirstName])->first();
				
                  if($records && !empty($records)){
                    $category_id = $records->id;
					
                  }else{
					$category_data = array(
						'name' => $cat->categoryFirstName,
						'category_cj_id' => $cat->categoryFirstId,
						'is_cj' => 'Yes',
						'parent_id' => 0,
						'status' => 'published',
						'order' => 0,
						//'author_id' => (int)Auth::user()->id,
						'created_at' => date('Y-m-d h:i:s'),
					); 
					
					
                    $category_insert = DB::table('ec_product_categories')->insertGetId($category_data);					
					$category_id = $category_insert;
					
					if($category_id){
						$category_slug_data = array(
								'key' => Str::slug($cat->categoryFirstName, '-'),
								'reference_id' => $category_id,
								'reference_type' => 'Botble\Ecommerce\Models\ProductCategory',
								'prefix' => 'product-categories',								
								'created_at' => date('Y-m-d h:i:s'),
								'updated_at' => date('Y-m-d h:i:s'),
							); 
						$category_slug_insert = DB::table('slugs')->insertGetId($category_slug_data);		
					}
                  }
				
                  foreach($cat->categoryFirstList as $sub){
						
                          //$categories = new Category;
                          //$reco = Category::select('*')->where(['category_name' => $sub->categorySecondName])->first();
						  $reco = DB::table('ec_product_categories')->select('*')->where(['name' => $sub->categorySecondName])->first();
                          if($reco && !empty($reco)){
                            $sub_category_id = $reco->id;
                          }else{

                          /*$categories->category_name = $sub->categorySecondName;
                          $categories->slug = str_slug($sub->categorySecondName);
                          $categories->level = '2';
                          $categories->save();
						  */
						  $sub_category_data = array(
								'name' => $sub->categorySecondName,
								'category_cj_id' => $sub->categorySecondId,
								'is_cj' => 'Yes',
								'parent_id' => $category_id,
								'status' => 'published',
								'order' => 2,
								//'author_id' => (int)Auth::user()->id,
								'created_at' => date('Y-m-d h:i:s'),
							); 
							$sub_category_insert = DB::table('ec_product_categories')->insertGetId($sub_category_data);
							$sub_category_id = $sub_category_insert;
							if($sub_category_id){
								$sub_category_slug_data = array(
										'key' => Str::slug($sub->categorySecondName, '-'),
										'reference_id' => $sub_category_id,
										'reference_type' => 'Botble\Ecommerce\Models\ProductCategory',
										'prefix' => 'product-categories',								
										'created_at' => date('Y-m-d h:i:s'),
										'updated_at' => date('Y-m-d h:i:s'),
									); 
								$sub_category_slug_data_insert = DB::table('slugs')->insertGetId($sub_category_slug_data);		
							}	
                          }


				

                        foreach($sub->categorySecondList as $sub_cat){

                          //$categoriess = new Category;
                          //$recos = Category::select('*')->where(['category_name' => $sub_cat->categoryName])->first();
						   $recos = DB::table('ec_product_categories')->select('*')->where(['name' => $sub_cat->categoryName])->first();
                          if($recos && !empty($recos)){
                            $third_categories_id = $recos->id;
                          }else{

                          /*$categoriess->category_name = $sub_cat->categoryName;
                          $categoriess->slug = str_slug($sub_cat->categoryName);
                          $categoriess->level = '3';
                          $categoriess->save();
						  */
						  $third_category_data = array(
								'name' => $sub_cat->categoryName,
								'category_cj_id' => $sub_cat->categoryId,
								'is_cj' => 'Yes',
								'parent_id' => $sub_category_id,
								'status' => 'published',
								'order' => 3,
								//'author_id' => (int)Auth::user()->id,
								'created_at' => date('Y-m-d h:i:s'),
							); 
							$third_category_insert = DB::table('ec_product_categories')->insertGetId($third_category_data);
							$third_category_id = $third_category_insert;
							if($third_category_id){
								$thirdsub_category_slug_data = array(
										'key' => Str::slug($sub_cat->categoryName, '-'),
										'reference_id' => $third_category_id,
										'reference_type' => 'Botble\Ecommerce\Models\ProductCategory',
										'prefix' => 'product-categories',								
										'created_at' => date('Y-m-d h:i:s'),
										'updated_at' => date('Y-m-d h:i:s'),
									); 
								$thirdsub_category_slug_data = DB::table('slugs')->insertGetId($thirdsub_category_slug_data);		
							}	

                          }


                        }

                  }

                //}
                         

                $i++;
                
              }
              dd("done");

            }

            else{

              return response()->json(['status' => 0, 'message' => "Something went wrong in get category api", 'data' => '']);

            }

          }catch(\Exception $e){

            return response()->json(['status' => 0, 'message' => $e->getMessage(), 'data' => '']);

          }
    }

    /**
     * @param FormBuilder $formBuilder
     * @return string
     */
	 
	 public function get_products_from_dropship_category(Request $request, BaseHttpResponse $response){
		 
		 
		  $category = $request->category_ids;
		  //dd($category);
			$store = auth('customer')->user()->store;
			$store_id = $store->id;
        try{
			
			foreach($category as $key=> $categoryid){
				
				$params = '?categoryId='.$categoryid;	
				
				$client       = new Client;
				$api_url    = 'https://developers.cjdropshipping.com/api2.0/v1/authentication/getAccessToken';
				$product_api_url = 'https://developers.cjdropshipping.com/api2.0/v1/product/list'.$params;
				$access_token = $this->get_access_token($api_url,$client,$request);
				
				$result_2 = $client->get($product_api_url, ['headers' => ['CJ-Access-Token' => $access_token]]);
				
				$products =  json_decode($result_2->getBody()->getContents());
				
				if($products->message == 'Success'){
	 
				  foreach($products->data->list as $prod){
					  
							
							$api_url    = 'https://developers.cjdropshipping.com/api2.0/v1/authentication/getAccessToken';
							$prod_detail_api_url = 'https://developers.cjdropshipping.com/api2.0/v1/product/query?pid='.$prod->pid;
							
							$access_token = $this->get_access_token($api_url,$client,$request);
							
							$result_2 = $client->get($prod_detail_api_url, ['headers' => ['CJ-Access-Token' => $access_token]]);
							
							$prod_details =  json_decode($result_2->getBody()->getContents());
							
							//dd($prod_details);		
							
							$product_image = json_decode($prod_details->data->productImage);
							$sale_price =explode('-',$prod_details->data->sellPrice);
							$productImageSet = $prod_details->data->productImageSet;
							
							$productImageSet_array = array();
							if($productImageSet){
								foreach($productImageSet as $keyiamge => $ImageSet){
									$productImageSet_url = $ImageSet;
									
									 if (empty($productImageSet_url)) {
									return $productImageSet_url;
										}

										$info_imageset = pathinfo($productImageSet_url);
										
										try {
											$contents = file_get_contents($productImageSet_url);
										} catch (Exception $exception) {
											return $productImageSet_url;
										}
										
										if (empty($contents)) {
											return $productImageSet_url;
										}
										$filename_ImageSet = 'products/'.rand(10,1000).$info_imageset['basename'];
										$path_imageset = public_path('storage/').$filename_ImageSet;
										if(file_exists($path_imageset)){
											unlink($path_imageset);
										}
										file_put_contents($path_imageset, $contents);
										$mimeType = $info_imageset['extension'];
										$fileUpload = new UploadedFile($path_imageset, $filename_ImageSet, $mimeType, null, true);
										$result = RvMedia::handleUpload($fileUpload, 0, 'products');
									$productImageSet_array[$keyiamge] = $filename_ImageSet;
									
								
								}
							}
							
							//get image from url 
							$url = $product_image[0];
							
							 if (empty($url)) {
									return $url;
								}

								$info = pathinfo($url);
								
								try {
									$contents = file_get_contents($url);
								} catch (Exception $exception) {
									return $url;
								}
								
								if (empty($contents)) {
									return $url;
								}
								$filename = 'products/'.rand(10,1000).$info['basename'];
								$path = public_path('storage/').$filename;
								//dd($filename	);
								if(file_exists($path)){
									unlink($path);
								}

								
									
								file_put_contents($path, $contents);

								$mimeType = $info['extension'];
								//dd($path,$filename);
								$fileUpload = new UploadedFile($path, $filename, $mimeType, null, true);

								$result = RvMedia::handleUpload($fileUpload, 0, 'products');

							$image_array_name = array($filename);
							
							$total_image_set = array_merge($image_array_name,$productImageSet_array);
							$product_images = json_encode($total_image_set);
							//dd($product_images);
							
							
							
							$commision = ($sale_price[0] * 35/100);
						$product_data = array(	
						
										'name' =>$prod_details->data->productNameEn,
										'description' =>'',
										'content' =>$prod_details->data->description,
										'status' =>'published',
										'images' =>$product_images,
										'sku' =>$prod_details->data->productSku,
										'order'=>0,
										'quantity'=>100000,
										'allow_checkout_when_out_of_stock'=>0,
										'with_storehouse_management'=>1,
										'is_featured'=>1,
										'brand_id'=>0,
										'sale_type'=>0,
										'price'=>($sale_price[0] + $commision)	,
										'sale_price'=>($sale_price[0] + $commision),
										'start_date'=>'',
										'end_date'=>'',
										'length'=>'',
										'wide'=>'',
										'height'=>'',
										'is_variation'=>0,
										'weight'=>$prod_details->data->packingWeight,
										'tax_id'=>1,
										'created_at'=>date('Y-m-d h:s:i'),
										'stock_status'=>'in_stock',
										'store_id'=>$store_id,
										
										

											
							);
							$product = DB::table('ec_products')->insertGetId($product_data);
							
							
						if ($product) {
							$category_name = $prod_details->data->categoryName;
							 //$recos = Category::select('*')->where(['category_name' => $category_name])->first();
							 $recos = DB::table('ec_product_categories')->select('*')->where(['name' => $category_name])->first();
							 //$categoriess = new Category;
							  if($recos && !empty($recos)){
								$categoriess = $recos->id;
							  
							  }else{
							
							  $categoriess_data = array(
										'name' => $prod_details->data->categoryName,
										'category_cj_id' => $prod_details->data->categoryId,
										'is_cj' => 'Yes',
										'status' => 'published',
										'order' => 1,
							  );
							  $sub_category_insert = DB::table('ec_product_categories')->insertGetId($categoriess_data);
								$categoriess = $sub_category_insert;
							  
							  
							  }
							  
								
							
								//add product id to category
								
							$product_category_id = array(
										'category_id' =>$categoriess ,
										'product_id' => $product,
										
										);	
								//dd($product_category_id);			
								$product_categories = DB::table('ec_product_category_product')->insert($product_category_id);
								
							$ec_products_translations = array(
										'lang_code' =>'vi' ,
										'ec_products_id' => $product,
										'name' => $prod_details->data->productNameEn,
										
										);	
								//dd($product_category_id);			
								$ec_products_translations_insert = DB::table('ec_products_translations')->insert($ec_products_translations);

								if($product){
									$thirdsub_slug_data = array(
											'key' => Str::slug($prod_details->data->productNameEn, '-'),
											'reference_id' => $product,
											'reference_type' => 'Botble\Ecommerce\Models\Product',
											'prefix' => 'products',								
											'created_at' => date('Y-m-d h:i:s'),
											'updated_at' => date('Y-m-d h:i:s'),
										); 
									$thirdsub_slug = DB::table('slugs')->insertGetId($thirdsub_slug_data);		
								}


								if($prod_details->data->variants){
									if($prod_details->data->productKeyEn == "Color"){
										
										$product_attri_set_data=array(
															'attribute_set_id'=> 3,
															'product_id'=>$product,
														);
										$product_attri_set_data_insert = DB::table('ec_product_with_attribute_set')->insertGetId($product_attri_set_data);
										$product_attri_set_data_insert_id = $product_attri_set_data_insert;					
										foreach($prod_details->data->variants as $keys => $variants){
											$commision_variation = ($variants->variantSellPrice * 35/100);
											$urls = $variants->variantImage;
							
											 if (empty($urls)) {
													return $urls;
												}

												$infos = pathinfo($urls);
												
												try {
													$contents = file_get_contents($urls);
												} catch (Exception $exception) {
													return $urls;
												}
												
												if (empty($contents)) {
													return $urls;
												}
												$filenames = 'products/'.rand(10,1000).$infos['basename'];
												$path = public_path('storage/').$filenames;
												//dd($filename	);
												if(file_exists($path)){
													unlink($path);
												}

												
													
												file_put_contents($path, $contents);

												$mimeType = $info['extension'];
												//dd($path,$filename);
												$fileUpload = new UploadedFile($path, $filenames, $mimeType, null, true);

												$result = RvMedia::handleUpload($fileUpload, 0, 'products');

											$image_array_names = array($filenames);
											$product_image = json_encode($image_array_names);
											
											
											$product_data_var = array(						
												'name' =>$prod_details->data->productNameEn,
												'description' =>'',
												'content' =>'',
												'status' =>'published',
												'images' =>$product_image,
												'sku' =>$variants->variantSku,
												'order'=>0,
												'quantity'=>100000,
												'allow_checkout_when_out_of_stock'=>0,
												'with_storehouse_management'=>1,
												'is_featured'=>0,
												'brand_id'=>0,
												'is_variation'=>1,
												'sale_type'=>0,
												'price'=>$variants->variantSellPrice + $commision_variation	,
												'sale_price'=>$variants->variantSellPrice + $commision_variation,
												'start_date'=>'',
												'end_date'=>'',
												'length'=>$variants->variantLength,
												'wide'=>'',
												'height'=>$variants->variantHeight,
												'weight'=>$variants->variantWeight,
												'tax_id'=>'',
												'created_at'=>date('Y-m-d h:s:i'),
												'stock_status'=>'in_stock',
												'store_id'=>'',
											
											);
											$variant_product_id = DB::table('ec_products')->insertGetId($product_data_var);
											
											if($variant_product_id){
												$variantKey_name = $variants->variantKey;
												 $variantKeys = DB::table('ec_product_attributes')->select('*')->where(['title' => $variantKey_name])->first();
												if($variantKeys && !empty($variantKeys)){
													$variantKeys_attri_id = $variantKeys->id;
												  
												}else{
												   $image_url = $variants->variantImage;
													$ch = curl_init();
													curl_setopt($ch, CURLOPT_POST, 0);
													curl_setopt($ch,CURLOPT_URL,$image_url);	
													curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
													$image_result=curl_exec($ch);
													curl_close($ch);
													$filename = 'products\/'.rand(10,1000).basename($image_url);
													//dd($prod->pid.'_'.$filename);
													$path = public_path('storage/').$filename;
													//dd($path);
													 if(file_exists($path)){
															unlink($path);
														}
													
													$fp = fopen($path,'x');
													fwrite($fp, $image_result);
													fclose($fp);
													
												  $variantKeys_attri_data = array(
															'attribute_set_id' => 1,
															'title' => $variants->variantKey,
															'slug' => $variants->variantKey,
															'color' => '',
															'image' => $filename,
															'status' => 'published',
															'created_at'=> date('Y-m-d h:i:s'),
												  );
												  $variantKeys_attri_insert = DB::table('ec_product_attributes')->insertGetId($variantKeys_attri_data);
												  $variantKeys_attri_id = $variantKeys_attri_insert;
												}  
												if($variantKeys_attri_id){
													if($keys == 0){
														$product_variation_data=array(
															'product_id'=> $variant_product_id,
															'configurable_product_id'=>$product,
															'is_default'=>1,
														);
													}else{
														$product_variation_data=array(
															'product_id'=> $variant_product_id,
															'configurable_product_id'=>$product,
															'is_default'=>0,
														);
													}	
													$variation_insert = DB::table('ec_product_variations')->insertGetId($product_variation_data);
													$variation_id = $variation_insert;	
												}
												if($variation_id != '' &&  $variantKeys_attri_id != '') {
													$product_variation_items=array(
														'attribute_id'=> $variantKeys_attri_id,
														'variation_id'=>$variation_id
													);
													$variation_insert = DB::table('ec_product_variation_items')->insertGetId($product_variation_items);
													$variation_id = $variation_insert;	
												} 
												  
												 
												
											}
											
										}
									
									}
									
								}	
								
								
							}
					
					//dd("done");	
				  }
				 
				}
			}
			 return $response
            ->setNextUrl('vendor/products')
            ->setMessage(trans('core/base::notices.create_success_message'));
          }catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }

    }
	
	 protected function get_access_token($api_url,$client,$request){


      $data         = ["email" => "jade@aussiedigital.io","password"=>"5354f9be28114803ab2baa7231aa4577"];
      $result_1     = $client->post($api_url, [
                          'headers' => ['Content-Type' => 'application/json'],
                          'body' => json_encode($data)
                      ]);

      $response       = json_decode($result_1->getBody()->getContents());

      if($response->message == 'Success'){
        return $response->data->accessToken;
      }else{
        return response()->json(['status' => 0, 'message' => "Something went wrong in access toekn api", 'data' => '']);

      }

    }
    
}
