<?php

$pageSettingsTabEditorCategories = '
<script>
buildCategoryEditor();
$( \'#categoryEditorTable\' ).sortable({
    stop: function () {
        var inputs = $(\'input.order\');
        var nbElems = inputs.length;
        $(\'input.order\').each(function(idx) {
            $(this).val(idx + 1);
        });
        submitCategoryOrder();
    }
});
</script>
<div class="panel bg-org panel-info">
    <div class="panel-heading">
        <span lang="en">Category Editor</span>
        <button type="button" class="btn btn-success btn-circle pull-right popup-with-form m-r-5" href="#new-category-form" data-effect="mfp-3d-unfold"><i class="fa fa-plus"></i> </button>
    </div>
    <div class="table-responsive">
        <form id="submit-categories-form" onsubmit="return false;">
            <table class="table table-hover manage-u-table">
                <thead>
                    <tr>
                        <th width="70" class="text-center">#</th>
                        <th lang="en">NAME</th>
                        <th lang="en" style="text-align:center">TABS</th>
                        <th lang="en" style="text-align:center">DEFAULT</th>
                        <th lang="en" style="text-align:center">EDIT</th>
                        <th lang="en" style="text-align:center">DELETE</th>
                    </tr>
                </thead>
                <tbody id="categoryEditorTable"></tbody>
            </table>
        </form>
    </div>
</div>
<form id="new-category-form" class="mfp-hide white-popup-block mfp-with-anim">
    <h1 lang="en">Add New Category</h1>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="new-category-form-inputNameNew" lang="en">Category Name</label>
            <input type="text" class="form-control" id="new-category-form-inputNameNew" name="name" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="new-category-form-inputImageNew" lang="en">Category Image</label>
            <input type="text" class="form-control" id="new-category-form-inputImageNew" name="image"  required="">
        </div>
    </fieldset>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none addNewCategory" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Add Category</span></button>
    <div class="clearfix"></div>
</form>
<form id="edit-category-form" class="mfp-hide white-popup-block mfp-with-anim">
    <input type="hidden" name="id" value="">
    <h1 lang="en">Edit Category</h1>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="edit-category-form-inputName" lang="en">Category Name</label>
            <input type="text" class="form-control" id="edit-category-form-inputName" name="name" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="edit-category-form-inputImage" lang="en">Category Image</label>
            <div class="panel panel-info">
                <div class="panel-heading">
                    <span lang="en">Image Legend</span>
                    <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-plus"></i></a></div>
                </div>
                <div class="panel-wrapper collapse" aria-expanded="false">
                    <div class="panel-body">
                        <p lang="en">You may use an image or icon in this field</p>
                        <p lang="en">For images, use the following format:</p><code>url::path/to/image</code>
                        <p lang="en">For icons, use the following format:</p><code>icon-type::icon-name</code> i.e. <code>fontawesome::home</code>
                    </div>
                </div>
            </div>
            <input type="text" class="form-control" id="edit-category-form-inputImage" name="image"  required="">
        </div>
    </fieldset>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none editCategory" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Edit Category</span></button>
    <div class="clearfix"></div>
</form>
';
