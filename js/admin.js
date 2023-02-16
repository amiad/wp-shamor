jQuery(document).ready(function( $ ) {
    function toggleCoords(){
        let disabled = true;
        if ($('#shamor_block_local')[0].checked){
            disabled = false;
        }
        $('[name="shamor_longitude"], [name="shamor_latitude"]').attr("disabled", disabled);
    }
    toggleCoords();
    $('#shamor_block_local').change(toggleCoords);
});