
<script id="tmpl-ilab-output-template" type="text/template">
	<div class="ilab-output" id="output={{ data.output.outputId }}">
		<table class="form-table">
			<tr>
				<th scope="row">
					Output Filename Format
				</th>
				<td>
					<input type="text" id="outputFilenameFormat-{{data.output.outputId}}" value="{{data.output.outputFilenameFormat}}">
                    <p class="description">This will format the output filename for this transcoding output.  You should always use the <code>@{filename}</code> variable.  The <code>@{filename}</code> variable will use the base filename of the uploaded video.   You can also use the following variables: <code>@{date:format}</code>, <code>@{site-name}</code>, <code>@{site-host}</code>, <code>@{site-id}</code>, <code>@{versioning}</code>, <code>@{user-name}</code>, <code>@{unique-id}</code>, <code>@{unique-path}</code>.  For the date token, format is any format string that you can use with php's date() function.  WordPress's default prefix would look like: <code>@{date:Y/m}</code>.</p>				</td>
			</tr>
			<tr>
				<th scope="row">
					Preset
				</th>
				<td>
					<select id="presetId-{{data.output.outputId}}">
						<# _.each(data.presets, function(preset){ #>
							<option value="{{preset.Id}}" {{((preset.Id == data.output.presetId) ? "selected" : "")}}>{{preset.Name}}</option>
							<# }); #>
					</select>
                    <p class="description">
                        These presets are managed in your <a href="https://{{ilabRegion}}.console.aws.amazon.com/elastictranscoder/home?region={{ilabRegion}}#presets:">AWS console</a>.
                    </p>
				</td>
			</tr>
			<tr id="segdur-row-{{data.output.outputId}}">
				<th scope="row">
					Segment Duration
				</th>
				<td>
					<input type="number" id="segdur-{{data.output.outputId}}" value="{{data.output.segmentDuration}}">
                    <p class="description">Leave blank to create a single, unsegmented output file. If you want to generate a series of segments for streaming, enter a value between 1 and 60 as the target maximum segment duration in seconds. Required for MPEG-Dash.</p>
				</td>
			</tr>
            <tr id="createThumbnails-row-{{data.output.outputId}}">
                <th scope="row">
                    Create Thumbnails
                </th>
                <td>
                    <select id="createThumbnails-{{data.output.outputId}}">
                        <option value="0" {{((0 == data.output.createThumbnails) ? "selected" : "")}}>False</option>
                        <option value="1" {{((1 == data.output.createThumbnails) ? "selected" : "")}}>True</option>
                    </select>
                    <p class="description">Specify whether you want Elastic Transcoder to create thumbnail graphics files for the video you’re transcoding.</p>
                </td>
            </tr>
            <tr id="thumbname-row-{{data.output.outputId}}">
                <th scope="row">
                    Thumbnail Filename Pattern
                </th>
                <td>
                    <input type="text" id="thumbname-{{data.output.outputId}}" value="{{data.output.thumbnailFilenamePattern}}">
                </td>
            </tr>
		</table>
        <div class="ilab-button-row">
            <a href="#" id="delete-{{data.output.outputId}}" class="button ilab-button-delete">Delete Output</a>
        </div>
	</div>
</script>
<script id="tmpl-ilab-playlist-template" type="text/template">
    <div class="ilab-output" id="playlist={{ data.playlist.playlistId }}">
        <table class="form-table">
            <tr>
                <th scope="row">
                    Playlist Name
                </th>
                <td>
                    <input type="text" id="playlistNameFormat-{{data.playlist.playlistId}}" value="{{data.playlist.playlistNameFormat}}">
                    <p class="description">This will format the output filename for this playlist.  You should always use the <code>@{filename}</code> variable.  The <code>@{filename}</code> variable will use the base filename of the uploaded video.   You can also use the following variables: <code>@{date:format}</code>, <code>@{site-name}</code>, <code>@{site-host}</code>, <code>@{site-id}</code>, <code>@{versioning}</code>, <code>@{user-name}</code>, <code>@{unique-id}</code>, <code>@{unique-path}</code>.  For the date token, format is any format string that you can use with php's date() function.  WordPress's default prefix would look like: <code>@{date:Y/m}</code>.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Playlist Format
                </th>
                <td>
                    <select id="playlistFormat-{{data.playlist.playlistId}}">
                        <option value="HLSv3" {{(('HLSv3' == data.playlist.playlistFormat) ? "selected" : "")}}>HLSv3</option>
                        <option value="HLSv4" {{(('HLSv4' == data.playlist.playlistFormat) ? "selected" : "")}}>HLSv4</option>
                        <option value="Smooth" {{(('MPEG-DASH' == data.playlist.playlistFormat) ? "selected" : "")}}>Smooth</option>
                        <option value="MPEG-DASH" {{(('MPEG-DASH' == data.playlist.playlistFormat) ? "selected" : "")}}>MPEG-DASH</option>
                    </select>
                    <p class="description">
                        The adaptive streaming protocol to use. Select from HTTP Live Streaming version 3 (HLSv3), HTTP Live Streaming version 4 (HLSv4), or Smooth Streaming (Smooth) or MPEG-DASH.
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Outputs
                </th>
                <td>
                    <select id="outputsSelector-{{data.playlist.playlistId}}">
                    </select>
                    <a href="#" id="add-output-{{data.playlist.playlistId}}" class="button">Add Output</a>
                    <ul id="outputs-{{data.playlist.playlistId}}">
                    </ul>
                    <span id="outputs-warning-{{data.playlist.playlistId}}" style="display:none">You don't have any qualifying outputs defined for this format of playlist.</span>
                </td>
            </tr>
        </table>
        <div class="ilab-button-row">
            <a href="#" id="delete-playlist-{{data.playlist.playlistId}}" class="button ilab-button-delete">Delete Playlist</a>
        </div>
    </div>
</script>
<script id="tmpl-ilab-playlist-output-template" type="text/template">
    <li id="playlist-output-{{data.output.outputId}}">{{data.output.outputFilenameFormat}}&nbsp;&nbsp;&nbsp;<a href="#">Remove</a></li>
</script>
<script>
    (function($){
        var outputContainer;
        var playlistContainer;

        var outputTemplate;
        var playlistTemplate;
        var playlistOutputTemplate;

        var outputUI = [];
        var playlistUI = [];

        var jobOutputUI = function(output, changedCallback, deleteCallback){
            var self = this;
            this.output = output;
            this.editor = $(outputTemplate({"output": output, "presets": ilabJobPresets}));

            this.updateByPreset = function() {
                var ps;

                if (!self.output.hasOwnProperty('presetId')) {
                    ps = ilabJobPresets[Object.keys(ilabJobPresets)[0]];
                } else {
                    ps = ilabJobPresets[self.output.presetId];
                }

                self.output.allowDashPlaylist = (ps.Container == 'fmp4');
                self.output.allowSmoothPlaylist = (ps.Container == 'fmp4');
                self.output.allowHLS3Playlist = (ps.Container == 'ts');
                self.output.allowHLS4Playlist = ((ps.Container == 'ts') && ((ps.hasOwnProperty('Video') && !ps.hasOwnProperty('Audio')) || (!ps.hasOwnProperty('Video') && ps.hasOwnProperty('Audio'))));

                if (ps.Container == 'ts' || ps.Container == 'fmp4') {
                    self.editor.find('#segdur-row-'+self.output.outputId).css({display: ''});
                } else {
                    self.editor.find('#segdur-row-'+self.output.outputId).css({display: 'none'});
                }

                if (ps.hasOwnProperty('Video')) {
                    self.editor.find('#createThumbnails-row-'+self.output.outputId).css({display: ''});
                    self.updateByCreateThumbnail();
                } else {
                    self.editor.find('#createThumbnails-row-'+self.output.outputId).css({display: 'none'});
                    self.editor.find('#thumbname-row-'+self.output.outputId).css({display: 'none'});
                }
            };

            this.updateByCreateThumbnail = function() {
                if (1 == self.output.createThumbnails) {
                    self.editor.find('#thumbname-row-'+self.output.outputId).css({display: ''});
                } else {
                    self.editor.find('#thumbname-row-'+self.output.outputId).css({display: 'none'});
                }
            };


            var updateTimer = null;
            this.performChange = function() {
                self.updateByCreateThumbnail();
                self.updateByPreset();

                clearTimeout(updateTimer);
                updateTimer = setTimeout(changedCallback, 750);
            };

            this.editor.find('#outputFilenameFormat-'+output.outputId).on('input', function(e){
                self.output.outputFilenameFormat = $(this).val();
                self.performChange();

                _.each(playlistUI, function(playlistObj){
                    playlistObj.refreshTemplates();
                });
            });

            this.editor.find('#thumbname-'+output.outputId).on('input', function(e){
                self.output.thumbnailFilenamePattern = $(this).val();
                self.performChange();
            });

            this.editor.find('#presetId-'+output.outputId).on('change', function(e){
                self.output.presetId = $(this).val();
                self.performChange();
            });

            this.editor.find('#segdur-'+output.outputId).on('input', function(e){
                self.output.segmentDuration = $(this).val();
                self.performChange();
            });

			this.editor.find('#createThumbnails-'+output.outputId).on('change', function(e){
			    self.output.createThumbnails = $(this).val();
                self.performChange();
			});

			this.editor.find('#delete-'+output.outputId).on('click', function(e){
                if (confirm("Are you sure you want to delete this output?")) {
                    self.editor.remove();
                    deleteCallback(self.output, self);
                }

			    e.preventDefault();
			    return false;
            });

			this.updateByPreset();
			this.updateByCreateThumbnail();

			outputContainer.append(this.editor);
        };

        var jobPlaylistUI = function(playlist, changedCallback, deleteCallback) {
            var self = this;
            this.playlist = playlist;
            this.editor = $(playlistTemplate({"playlist": playlist }));

            var updateTimer = null;
            this.performChange = function() {
                clearTimeout(updateTimer);
                updateTimer = setTimeout(changedCallback, 750);
            };

            this.refreshOutputs = function() {
                self.editor.find('#outputs-'+self.playlist.playlistId).find('li').remove();

                _.each(self.playlist.outputIds, function(oid){
                    var foundOutput = null;
                    _.each(ilabJobDef.outputs, function(output){
                        if (output.outputId == oid) {
                            foundOutput = output;
                        }
                    });

                    var op = $(playlistOutputTemplate({"output": foundOutput}));
                    self.editor.find('#outputs-'+self.playlist.playlistId).append(op);

                    op.find('a').on('click', function(e){
                        if (confirm("Are you sure you want to remove this output from your playlist?")) {
                            var idx = self.playlist.outputIds.indexOf(oid);
                            if (idx > -1) {
                                self.playlist.outputIds.splice(idx, 1);
                                op.remove();
                                self.performChange();
                            }
                        }
                        e.preventDefault();
                        return false;
                    });
                });
            };

            this.refreshTemplates = function() {
                self.refreshOutputs();

                self.editor.find('#outputsSelector-'+self.playlist.playlistId).each(function(){
                    var select = $(this);
                    select.find('option').remove();

                    _.each(ilabJobDef.outputs, function(output){
                        if (output.allowHLS3Playlist && self.playlist.playlistFormat == 'HLSv3') {
                            select.append($('<option value="'+output.outputId+'">'+output.outputFilenameFormat+'</option>'));
                        }
                        else if (output.allowHLS4Playlist && self.playlist.playlistFormat == 'HLSv4') {
                            select.append($('<option value="'+output.outputId+'">'+output.outputFilenameFormat+'</option>'));
                        }
                        else if (output.allowSmoothPlaylist && self.playlist.playlistFormat == 'Smooth') {
                            select.append($('<option value="'+output.outputId+'">'+output.outputFilenameFormat+'</option>'));
                        }
                        else if (output.allowDashPlaylist && self.playlist.playlistFormat == 'MPEG-DASH') {
                            select.append($('<option value="'+output.outputId+'">'+output.outputFilenameFormat+'</option>'));
                        }
                    });

                    if (select.find('option').length == 0) {
                        select.css({display: 'none'});
                        self.editor.find('#add-output-'+self.playlist.playlistId).css({display: 'none'});
                        self.editor.find('#outputs-'+self.playlist.playlistId).css({display: 'none'});
                        self.editor.find('#outputs-warning-'+self.playlist.playlistId).css({display: ''});
                    } else {
                        select.css({display: ''});
                        self.editor.find('#add-output-'+self.playlist.playlistId).css({display: ''});
                        self.editor.find('#outputs-'+self.playlist.playlistId).css({display: ''});
                        self.editor.find('#outputs-warning-'+self.playlist.playlistId).css({display: 'none'});
                    }
                });
            };

            this.editor.find('#playlistNameFormat-'+playlist.playlistId).on('input', function(e){
                self.playlist.playlistNameFormat = $(this).val();
                self.performChange();
            });

            this.editor.find('#playlistFormat-'+playlist.playlistId).on('change', function(e){
                if (self.playlist.playlistFormat == $(this).val()) {
                    return;
                }

                self.playlist.outputIds = [];
                self.playlist.playlistFormat = $(this).val();
                self.refreshTemplates();
                self.performChange();
            });

            this.editor.find('#add-output-'+self.playlist.playlistId).on('click', function(e){
                var oid = self.editor.find('#outputsSelector-'+self.playlist.playlistId).val();
                if (self.playlist.outputIds.indexOf(oid) != -1) {
                    e.preventDefault();
                    return false;
                }

                self.playlist.outputIds.push(oid);

                self.refreshOutputs();
                self.performChange();

                e.preventDefault();
                return false;
            });

            console.log('#delete-playlist-'+playlist.playlistId);
            this.editor.find('#delete-playlist-'+((playlist.playlistId) ? playlist.playlistId : '')).on('click', function(e){
                if (confirm("Are you sure you want to delete this output?")) {
                    self.editor.remove();
                    deleteCallback(self.playlist, self);
                }

                e.preventDefault();
                return false;
            });

            this.refreshTemplates();

            playlistContainer.append(this.editor);

            console.log(this);
        };

        $(document).on('ready',function(){
            outputContainer = $('#ilab-job-outputs');
            outputTemplate = wp.template('ilab-output-template');

            playlistContainer = $('#ilab-playlists');
            playlistTemplate = wp.template('ilab-playlist-template');
            playlistOutputTemplate = wp.template('ilab-playlist-output-template');

            var modelChanged = function() {
                console.log(ilabJobDef);

                data={};
                data['action'] = 'ilab_et_save_job_def';
                data['job_def'] = JSON.stringify(ilabJobDef);

                $.post(ajaxurl, data, function(response){
                    console.log(response);
                });

            };

            var outputDeleted = function(output, ui) {
                console.log(output);
                var idx = ilabJobDef.outputs.indexOf(output);
                if (idx > -1) {
                    ilabJobDef.outputs.splice(idx, 1);
                }

                idx = outputUI.indexOf(ui);
                if (idx > -1) {
                    outputUI.splice(idx, 1);
                }

                modelChanged();
            };

            var playlistDeleted = function(playlist, ui) {
                console.log(playlist);
                var idx = ilabJobDef.playlists.indexOf(playlist);
                if (idx > -1) {
                    ilabJobDef.playlists.splice(idx, 1);
                }

                idx = playlistUI.indexOf(ui);
                if (idx > -1) {
                    playlistUI.splice(idx, 1);
                }

                modelChanged();
            };

            if (!ilabJobDef.hasOwnProperty('outputs')) {
                ilabJobDef.outputs = [];
            }

            if (!ilabJobDef.hasOwnProperty('playlists')) {
                ilabJobDef.playlists = [];
            }

            var addNewOutput = function() {
                var newOutput = {
                    'outputId': (new Date().getTime()).toString(16),
                    'presetId': ilabJobPresets[Object.keys(ilabJobPresets)[0]].Id,
                    'segmentDuration': null,
                    'outputFilenameFormat': '@{filename}',
                    'createThumbnails': 0,
                    'allowHLS3Playlist': false,
                    'allowHLS4Playlist': false,
                    'allowDashPlaylist': false,
                    'allowSmoothPlaylist': false,
                    'thumbnailFilenamePattern': '@{filename}-thumb-'
                };

                ilabJobDef.outputs.push(newOutput);
                var newOutputUI = new jobOutputUI(newOutput, modelChanged, outputDeleted);
                outputUI.push(newOutputUI);

                _.each(playlistUI, function(playlistObj){
                   playlistObj.refreshTemplates();
                });

                modelChanged();
            };

            var addNewPlaylist = function() {
                var newPlaylistModel = {
                    'playlistId': (new Date().getTime()).toString(16),
                    'playlistNameFormat': '@{filename}',
                    'playlistFormat': 'HLSv3',
                    'outputIds': []
                };

                ilabJobDef.playlists.push(newPlaylistModel);
                playlistUI.push(new jobPlaylistUI(newPlaylistModel, modelChanged, playlistDeleted));

                modelChanged();
            };


            _.each(ilabJobDef.outputs, function(output){
                var newOutputUI = new jobOutputUI(output, modelChanged, outputDeleted);
                outputUI.push(newOutputUI);
            });

            _.each(ilabJobDef.playlists, function(playlist){
                var newPlaylistUI = new jobPlaylistUI(playlist, modelChanged, playlistDeleted);
                playlistUI.push(newPlaylistUI);
            });

            $('#ilab-add-output').on('click', function(e) {
                addNewOutput();

                e.preventDefault();
                return false;
            });

            $('#ilab-add-playlist').on('click', function(e) {
                addNewPlaylist();

                e.preventDefault();
                return false;
            });

            $('#ilab-et-reset-cache').on('click', function(e){
                $.post(ajaxurl, {"action":"ilab_et_clear_cache"}, function(response){
                    window.location.reload(true);
                });

                e.preventDefault();
                return false;
            });
        });
    })(jQuery);
</script>