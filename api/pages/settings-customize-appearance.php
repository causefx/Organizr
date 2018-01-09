<?php

$pageSettingsCustomizeAppearance = '
<script>
buildCustomizeAppearance();
</script>
<div class="panel bg-theme-dark panel-info">
    <div class="panel-heading" lang="en"> Customize Appearance</div>
    <div class="panel-wrapper collapse in" aria-expanded="true">
        <div class="panel-body bg-theme-dark">
            <form id="customize-appearance-form" class="form-horizontal" onsbumit="return false;">

                <!-- FORM GROUP -->
                <h3 class="box-title">Person Info</h3>
                <hr class="m-t-0 m-b-40">
                <div class="row">

                    <!-- INPUT BOX -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label col-md-3">First Name</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" placeholder="John doe"></div>
                        </div>
                    </div>
                    <!--/ INPUT BOX -->

                </div>
                <!--/ FORM GROUP -->

            </form>
        </div>
    </div>
</div>
';
