<div class="imgix-parameter ilabm-pillbox {{(empty($paramInfo['no-icon'])) ? '' : 'ilabm-pillbox-no-icon'}} {{(empty($paramInfo['classes'])) ? '' : $paramInfo['classes']}}" data-param="{{$param}}" data-param-type="{{$paramInfo['type']}}" data-param-values="{{implode(',',array_keys($paramInfo['options']))}}" data-radio-mode="{{(empty($paramInfo['radio'])) ? 'false' : 'true'}}" data-must-select="{{(empty($paramInfo['must-select'])) ? 'false' : 'true'}}">
    {% foreach($paramInfo['options'] as $optionKey => $optionInfo) %}
    <input type="hidden" name="{{$optionKey}}" value="{{$paramInfo['selected']($settings, $optionKey, '1','0')}}">
    <a data-param="{{$optionKey}}" class="ilabm-pill imgix-pill-{{$optionKey}} {{$paramInfo['selected']($settings, $optionKey, 'pill-selected', '')}}" href="#"><span class="icon"></span><span>{{$optionInfo['title']}}</span></a>
    {% endforeach %}
</div>
