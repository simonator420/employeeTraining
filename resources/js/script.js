humhub.module('employeeTraining.modal', function (module, require, $) {
    $(document).ready(function () {
        if (humhub.modules.ui.status.hasFlash('showDriverPopup')) {
            $('#driverPopup').modal('show');
        }
    });
});
