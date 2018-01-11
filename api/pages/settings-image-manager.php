<?php

$pageSettingsImageManager = '
<script>
	buildImageManagerView();
    var myDropzone = new Dropzone("#new-image-form", {
      url: "api/?v1/settings/image/manager/view",
      init: function() {
        this.on("complete", function(file) {
            buildImageManagerView();
            //$.magnificPopup.close();
        });
      }
    });
</script>
<div class="panel bg-theme-dark panel-info">
    <div class="panel-heading">
		<span lang="en">View Images</span>
        <button type="button" class="btn btn-success btn-circle pull-right popup-with-form m-r-5" href="#new-image-form" data-effect="mfp-3d-unfold"><i class="fa fa-upload"></i> </button>
	</div>
    <div class="panel-wrapper collapse in" aria-expanded="true">
        <div class="panel-body bg-theme-dark" >
        <div class="row el-element-overlay m-b-40" id="settings-image-manager-list"></div>
        </div>
    </div>
</div>
<form action="#" id="new-image-form" class="mfp-hide white-popup-block mfp-with-anim dropzone">
    <h1 lang="en">Upload Image</h1>
    <div class="fallback">
        <input name="file" type="file" multiple />
    </div>
    <div class="clearfix"></div>
</form>
';
