<input type="hidden" name="has_module_data" value="1">
<input type="hidden" name="ecom_listing_form_present" value="1">

<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('ecommerce::lang.publish_fields_title')</h3>
    </div>
    <div class="box-body">
        @if(empty($store) || empty($store->id))
            <div class="alert alert-info">
                @lang('ecommerce::lang.configure_storefront_first')
            </div>
        @else
            <div class="row">
                <div class="col-sm-3">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="ecom_publish_online" value="1" {{ old('ecom_publish_online', optional($listing)->is_published) ? 'checked' : '' }}>
                            @lang('ecommerce::lang.publish_online')
                        </label>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <label for="ecom_slug">@lang('ecommerce::lang.product_slug')</label>
                        <input type="text" class="form-control" name="ecom_slug" id="ecom_slug" value="{{ old('ecom_slug', optional($listing)->slug) }}">
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <label for="ecom_meta_title">@lang('ecommerce::lang.meta_title')</label>
                        <input type="text" class="form-control" name="ecom_meta_title" id="ecom_meta_title" value="{{ old('ecom_meta_title', optional($listing)->meta_title) }}">
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <label>@lang('ecommerce::lang.store_preview')</label>
                        <p class="form-control-static"><a href="{{ $store->public_url }}" target="_blank">{{ $store->public_url }}</a></p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="ecom_excerpt">@lang('ecommerce::lang.short_excerpt')</label>
                        <textarea class="form-control" rows="3" name="ecom_excerpt" id="ecom_excerpt">{{ old('ecom_excerpt', optional($listing)->excerpt) }}</textarea>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="ecom_meta_description">@lang('ecommerce::lang.meta_description')</label>
                        <textarea class="form-control" rows="3" name="ecom_meta_description" id="ecom_meta_description">{{ old('ecom_meta_description', optional($listing)->meta_description) }}</textarea>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
