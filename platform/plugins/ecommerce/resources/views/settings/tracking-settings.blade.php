@extends(BaseHelper::getAdminMasterLayoutTemplate())
@section('content')
    {!! Form::open(['url' => route('ecommerce.tracking-settings'), 'class' => 'main-setting-form']) !!}
        <div class="max-width-1200">

            <div class="flexbox-annotated-section">
                <div class="flexbox-annotated-section-annotation">
                    <div class="annotated-section-title pd-all-20">
                        <h2>{{ trans('plugins/ecommerce::ecommerce.setting.tracking_settings') }}</h2>
                    </div>
                    <div class="annotated-section-description pd-all-20 p-none-t">
                        <p class="color-note">{{ trans('plugins/ecommerce::ecommerce.setting.tracking_settings_description') }}</p>
                    </div>
                </div>
                <div class="flexbox-annotated-section-content">
                    <div class="wrapper-content pd-all-20">
                        <div class="form-group mb-3">
                            <label class="text-title-field"
                                   for="facebook_pixel_enabled">{{ trans('plugins/ecommerce::ecommerce.setting.enable_facebook_pixel') }}
                            </label>
                            <label class="me-2">
                                <input type="radio" name="facebook_pixel_enabled"
                                       value="1"
                                       class="trigger-input-option" data-setting-container=".facebook-pixel-settings-container"
                                       @if (EcommerceHelper::isFacebookPixelEnabled()) checked @endif>{{ trans('core/setting::setting.general.yes') }}
                            </label>
                            <label>
                                <input type="radio" name="facebook_pixel_enabled"
                                       value="0"
                                       class="trigger-input-option" data-setting-container=".facebook-pixel-settings-container"
                                       @if (!EcommerceHelper::isFacebookPixelEnabled()) checked @endif>{{ trans('core/setting::setting.general.no') }}
                            </label>
                        </div>

                        {!! Form::helper(trans('plugins/ecommerce::ecommerce.setting.facebook_pixel_helper')) !!}

                        <div class="facebook-pixel-settings-container mb-4 border rounded-top rounded-bottom p-3 bg-light @if (!EcommerceHelper::isFacebookPixelEnabled()) d-none @endif">
                            <div class="form-group mb-3">
                                <label class="text-title-field"
                                       for="facebook_pixel_id">{{ trans('plugins/ecommerce::ecommerce.setting.facebook_pixel_id') }}
                                </label>
                                <input type="text" name="facebook_pixel_id" class="next-input" value="{{ get_ecommerce_setting('facebook_pixel_id') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flexbox-annotated-section" style="border: none">
                <div class="flexbox-annotated-section-annotation">
                    &nbsp;
                </div>
                <div class="flexbox-annotated-section-content">
                    <button class="btn btn-info" type="submit">{{ trans('plugins/ecommerce::currency.save_settings') }}</button>
                </div>
            </div>
        </div>
    {!! Form::close() !!}
@endsection
