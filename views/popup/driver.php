<?php

use yii\helpers\Html;

?>

<div class="modal fade" id="driverPopup" tabindex="-1" role="dialog" aria-labelledby="driverPopupLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="driverPopupLabel">Important Information for Drivers</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Welcome, Driver! Please be aware of the new driving regulations starting next week.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#driverPopup').modal('show');
    });
</script>
