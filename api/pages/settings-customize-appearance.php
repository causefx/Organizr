<?php

$pageSettingsCustomizeAppearance = '
<script>
	buildCustomizeAppearance();
</script>
<div class="panel bg-theme-dark panel-info">
    <div class="panel-heading">
		<span lang="en">Customize Appearance</span>
		<button type="button" id="customize-appearance-reload" class="btn btn-primary btn-circle pull-right reload hidden m-r-5"><i class="fa fa-spin fa-refresh"></i> </button>
		<button id="customize-appearance-reload" class="btn btn-sm btn-primary btn-rounded waves-effect waves-light pull-right reload hidden" type="button"><span class="btn-label"><i class="fa fa-spin fa-refresh"></i></span><span lang="en">Reload</span></button>
	</div>
    <div class="panel-wrapper collapse in" aria-expanded="true">
        <div class="panel-body bg-theme-dark">
            <form id="customize-appearance-form" class="form-horizontal" onsbumit="return false;"></form>
        </div>
    </div>
</div>
';
