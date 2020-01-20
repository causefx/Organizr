<?php

$pageDependencies = '
<script>
</script>
<div class="container-fluid">
    <div class="row bg-title">
        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
            <h4 class="page-title" lang="en">Organizr Dependency Check</h4>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!--.row-->
    <div class="row">
        <div class="col-lg-8">
            <div class="panel panel-danger">
                <div class="panel-heading"> <i class="ti-alert fa-fw"></i> <span lang="en">Dependencies Missing</span>
                    <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="panel-dismiss"><i class="ti-close"></i></a> </div>
                </div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        <ul class="common-list" id="depenency-info"></ul>
                    </div>
                </div>
            </div>
        </div>
		<div class="col-lg-4">
            <div class="panel panel-info">
                <div class="panel-heading"> <i class="ti-alert fa-fw"></i> <span lang="en">PHP Version Check</span>
                    <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="panel-dismiss"><i class="ti-close"></i></a> </div>
                </div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <td id="php-version-check" lang="en">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
		<div class="col-lg-4">
            <div class="panel panel-info">
                <div class="panel-heading"> <i class="ti-alert fa-fw"></i> <span lang="en">Web Folder</span>
                    <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="panel-dismiss"><i class="ti-close"></i></a> </div>
                </div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <td>'.dirname(__DIR__,2).'</td>
                            </tr>
                            <tr>
                                <td id="web-folder" lang="en">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel panel-info">
                <div class="panel-heading"> <i class="ti-alert fa-fw"></i> <span lang="en">Browser Information</span>
                    <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="panel-dismiss"><i class="ti-close"></i></a> </div>
                </div>
                <div class="panel-wrapper collapse in" id="browser-info" aria-expanded="true"></div>
            </div>
        </div>

    </div>
    <!--./row-->
</div>
<!-- /.container-fluid -->
';
