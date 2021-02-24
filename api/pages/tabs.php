<?php
$GLOBALS['organizrPages'][] = 'tabs';
function get_page_tabs($Organizr)
{
	if (!$Organizr) {
		$Organizr = new Organizr();
	}
	return '
<script>
</script>
<div class="container-fluid">
    <div class="row bg-title">
        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
            <h4 class="page-title" lang="en">No Tabs Available</h4>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!--.row-->
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-warning">
                <div class="panel-heading"> <i class="ti-alert fa-fw"></i> <span lang="en">No Tabs Available</span></div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        <p lang="en">There are no available tabs for your group - please contact the Administrator</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--./row-->
</div>
<!-- /.container-fluid -->
';
}