<div class="settings-container">
    <header class="all-settings">
        <img src="{{ILAB_PUB_IMG_URL}}/icon-cloud-w-type.svg">
        <hr>
        <nav>
            <ul>
                @foreach($tools as $key => $atool)
                @if(!empty($atool->toolInfo['settings']))
                <li class="{{($tab == $key) ? 'active' : ''}}">
                    @if($atool->enabled())
                    <span class="tool-indicator tool-active"></span>
                    @elseif($atool->envEnabled())
                    <span class="tool-indicator tool-env-active"></span>
                    @else
                    <span class="tool-indicator tool-inactive"></span>
                    @endif
                    <a href="{{admin_url('admin.php?page=media-cloud-settings&tab='.$key)}}">{{$atool->toolInfo['name']}}</a>
                </li>
                @endif
                @endforeach
            </ul>
        </nav>
    </header>
    <div class="settings-body">
        <div class="ilab-notification-container"></div>
        <form action='options.php' method='post' autocomplete="off">
            <?php
            settings_fields( $group );
            ?>
            <div class="ilab-settings-section ilab-settings-toggle">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable {{$tool->toolInfo['name']}}</th>
                        <td>
                            @include('base/ilab-tool-settings', ['name' => $tab, 'manager' => $manager, 'tool' => $tool])
                        </td>
                    </tr>
                    @if(!empty($tool->toolInfo['related']))
                    @foreach($tool->toolInfo['related'] as $relatedKey)
                        <?php $relatedTool = $manager->tools[$relatedKey]; if (empty($relatedTool)) { continue; } ?>
                        <tr>
                            <th scope="row">Enable {{$relatedTool->toolInfo['name']}}</th>
                            <td>
                                @include('base/ilab-tool-settings', ['name' => $relatedTool->toolInfo['id'], 'manager' => $manager, 'tool' => $relatedTool])
                            </td>
                        </tr>
                    @endforeach
                    @endif
                </table>
            </div>
            @foreach($sections as $section)
            <div class="ilab-settings-section">
                @if(!empty($section['title']))
                <h2>{{$section['title']}}</h2>
                @endif
                <table class="form-table">
                    <?php do_settings_fields( $page, $section['id'] ) ?>
                </table>
            </div>
            @endforeach
            <div class="ilab-settings-button">
                @if($tool->hasEnabledBatchTools())
                    <div class="ilab-settings-batch-tools">
                    @foreach($tool->enabledBatchToolInfo() as $batchTool)
                    <a class="button" href="{{$batchTool['link']}}">{{$batchTool['title']}}</a>
                    @endforeach
                    </div>
                @endif
                <?php submit_button(); ?>
            </div>
        </form>
    </div>
</div>
<script>
    (function($){
        $('[data-conditions]').each(function(){
            var parent = this.parentElement;
            while (parent.tagName.toLowerCase() != 'tr') {
                parent = parent.parentElement;
                if (!parent) {
                    return;
                }
            }
            var name = this.getAttribute('id').replace('setting-','');
            var conditions = JSON.parse($('#'+name+'-conditions').html());

            var conditionTest = function() {
                var match = false;
                Object.getOwnPropertyNames(conditions).forEach(function(prop){
                    var val = $('#'+prop).val();

                    var trueCount = 0;
                    conditions[prop].forEach(function(conditionVal){
                        if (conditionVal[0] == '!') {
                            conditionVal = conditionVal.substring(1);
                            if (val != conditionVal) {
                                trueCount++;
                            }
                        } else {
                            if (val == conditionVal) {
                                trueCount++;
                            }
                        }
                    });

                    if (trueCount==conditions[prop].length) {
                        match = true;
                    } else {
                        match = false;
                    }
                });

                return match;
            };

            if (!conditionTest()) {
                parent.style.display = 'none';
            }

            Object.getOwnPropertyNames(conditions).forEach(function(prop){
                $('#'+prop).on('change', function(e){
                    if (!conditionTest()) {
                        parent.style.display = 'none';
                    } else {
                        parent.style.display = '';
                    }
                });
            });
        });
    })(jQuery);
</script>