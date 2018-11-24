<div data-default-value="{{$paramInfo['default']}}" data-param="{{$param}}" data-param-type="{{$paramInfo['type']}}" class="imgix-parameter">
    <div class="imgix-param-title">
        <div class="imgix-param-title-left">
            <h3>{{__($paramInfo['title'])}}</h3>
        </div>
        <div class="imgix-param-title-right">
            <h3 class="imgix-param-label" style="font-style:italic">{{imgixCurrentValue($param,$settings,$paramInfo['default'])}}</h3>
        </div>
    </div>
    <input class="imgix-param" type="range" min="{{$paramInfo['min']}}" max="{{$paramInfo['max']}}" step="{{empty($paramInfo['inc']) ? '1' : $paramInfo['inc']}}" value="{{imgixCurrentValue($param,$settings,$paramInfo['default'])}}" />
    <a class="imgix-param-reset" href="#">{{__('Reset')}}</a>
    {% if (!empty($paramInfo['hidden'])) %}
    <div class="imgix-param-imagick-warning">
        <div>This parameter requires the <a target="_blank" href="http://php.net/manual/en/book.imagick.php">PHP ImageMagick extension</a> to be installed.</div>
    </div>
    {% endif %}
</div>