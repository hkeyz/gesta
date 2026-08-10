<form method="POST" action="{{ $formAction }}">
    @csrf
    @if(!empty($formMethod) && strtoupper($formMethod) !== 'POST')
        @method($formMethod)
    @endif
    <div class="box-body">
        <div class="form-group">
            <label>@lang('mobilemoney::lang.name')</label>
            <input type="text" class="form-control" name="name" value="{{ old('name', $operator->name ?? '') }}" required>
        </div>
        <div class="form-group">
            <label>@lang('mobilemoney::lang.code')</label>
            <input type="text" class="form-control" name="code" value="{{ old('code', $operator->code ?? '') }}">
        </div>
        <div class="form-group">
            <label>@lang('mobilemoney::lang.color')</label>
            <input type="text" class="form-control" name="color" value="{{ old('color', $operator->color ?? '#1f6feb') }}">
        </div>
        <div class="form-group">
            <label>@lang('mobilemoney::lang.sort_order')</label>
            <input type="number" min="0" class="form-control" name="sort_order" value="{{ old('sort_order', $operator->sort_order ?? 0) }}">
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', isset($operator) ? $operator->is_active : true) ? 'checked' : '' }}>
                @lang('mobilemoney::lang.active')
            </label>
        </div>
    </div>
    <div class="box-footer">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('mobilemoney.operators.index') }}" class="btn btn-default">@lang('messages.go_back')</a>
    </div>
</form>
