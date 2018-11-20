<div class="settings-container">
    <header>
        <img src="{{ILAB_PUB_IMG_URL}}/icon-cloud.svg">
        <h1>{{$title}}</h1>
    </header>
    <div class="settings-body">
        {% foreach($tools as $tool) %}
        <div class="media-cloud-tool-description">
            <h2>{{$tool['title']}}</h2>
            <p>{{$tool['description']}}</p>
            <a href="{{$tool['link']}}" class="button button-primary">Run {{$tool['title']}}</a>
        </div>
        {% endforeach %}
    </div>
</div>