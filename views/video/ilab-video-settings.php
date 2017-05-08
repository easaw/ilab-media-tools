<style>
    .ilab-job-data-container {
        margin-bottom: 40px;
    }

    .ilab-output {
        margin-top: 20px;
        padding: 0px 20px;
        border: 1px solid #e0e0e0;
        background-color: #fafafa;
    }

    .ilab-button-delete {
        background: #cc2d44 !important;
        border-color: #e13b53 !important;
        -webkit-box-shadow: inset 0 1px 0 rgba(225, 59, 83, 0.5), 0 1px 0 rgba(0,0,0,.15) !important;
        box-shadow: inset 0 1px 0 rgba(225, 59, 83, 0.5), 0 1px 0 rgba(0,0,0,.15) !important;
        color: #fff !important;
        text-decoration: none !important;
    }

    .ilab-button-row {
        margin-top: 20px;
        margin-bottom: 20px;
    }
</style>
<div class="wrap">
    <form action='options.php' method='post'>
        <?php
        settings_fields( $group );
        do_settings_sections( $page );
        ?>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">&nbsp;&nbsp;&nbsp;<a href="#" id="ilab-et-reset-cache" class="button">Clear Presets Cache</a>
        </p>
    </form>
    <h2>Job Output Settings</h2>
    <p>Each video upload will be processed and transcoded into the outputs that you define below.  You must have at least 1 output defined before transcoding can occur.</p>
    <a href="#" id="ilab-add-output" class="button button-secondary">Add Output</a>
    <div id="ilab-job-outputs" class="ilab-job-data-container">

    </div>
    <h2>Playlist Settings</h2>
    <p>For adaptive streaming protocols like HLS and MPEG-Dash, you'll need to generate a playlist and add the outputs you've defined above to them.</p>
    <a href="#" id="ilab-add-playlist" class="button button-secondary">Add Playlist</a>
    <div id="ilab-playlists" class="ilab-job-data-container">

    </div>
</div>
<script>
    var ilabJobPresets = {{json_encode($presets, JSON_PRETTY_PRINT)}};
    var ilabJobDef = {{json_encode($jobDef, JSON_PRETTY_PRINT)}};
    var ilabRegion = "{{$region}}";
</script>
<?php include ILAB_VIEW_DIR.'/video/ilab-video-templates.php' ?>
