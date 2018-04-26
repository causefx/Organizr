<?php

$pageSettingsTabEditorHomepageOrder = '
<script>
    $("#homepage-items").sortable({
    placeholder:    "sort-placeholder col-md-3",
    forcePlaceholderSize: true,
    start: function( e, ui ){
        ui.item.data( "start-pos", ui.item.index()+1 );
    },
    change: function( e, ui ){
        var seq,
        startPos = ui.item.data( "start-pos" ),
        $index,
        correction;
        correction = startPos <= ui.placeholder.index() ? 0 : 1;
        ui.item.parent().find( "div.sort-homepage").each( function( idx, el ){
            var $this = $( el ),
            $index = $this.index();
            if ( ( $index+1 >= startPos && correction === 0) || ($index+1 <= startPos && correction === 1 ) ){
                $index = $index + correction;
                $this.find( ".ordinal-position").text( $index);
                link = $this.find( ".ordinal-position" ).attr("data-link");
                $("#homepage-values [name="+link+"]").val($index);
                $("#homepage-values [name="+link+"]").attr("data-changed", "true");
                console.log(link+" - "+$index);
            }
        });
        seq = ui.item.parent().find( "div.sort-placeholder.col-md-3").index() + correction;
        ui.item.find( ".ordinal-position" ).text( seq );
        newlink = ui.item.find( ".ordinal-position" ).attr("data-link");
        $("#homepage-values [name="+newlink+"]").val(seq);
        $("#homepage-values [name="+newlink+"]").attr("data-changed", "true");
        console.log(newlink+" - "+seq);
    }
    });
</script>
<div class="panel bg-org panel-info">
    <div class="panel-heading">
		<span lang="en">Homepage Order</span>
        <button type="button" class="btn btn-success btn-circle pull-right m-r-5" onclick="submitHomepageOrder()" ><i class="fa fa-save"></i></button>
	</div>
    <div class="panel-wrapper collapse in" aria-expanded="true">
        <div class="panel-body bg-org" >
        <div class="row el-element-overlay m-b-40" id="settings-homepage-order">'.buildHomepageSettings().'</div>
        </div>
    </div>
</div>

';
