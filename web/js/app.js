var animusApp = angular.module('animusApp', []);

animusApp.config(function ($interpolateProvider) {
//    $interpolateProvider.startSymbol('//');
//    $interpolateProvider.endSymbol('//');
    $interpolateProvider.startSymbol('{[{');
    $interpolateProvider.endSymbol('}]}');
});

animusApp.controller('cntrl', function ($scope, $http) {

    $scope.showCreateForm = function () {
        // clear the modal form
        $scope.clearForm();
        // change modal title
        $('#modal-flat-title').text("Erstellen Sie eine neue Wohnung");
        // hide update flat button
        $('#btn-update-flat').hide();
        // show create flat button
        $('#btn-create-flat').show();
        // show the modal form
        $('#modal-flat-form').show();
        // hide add flat button
        $('#btn-add-flat').hide();
        //remove all validation marks
        $scope.newFlatForm.$setUntouched();
    };

    // clear variable / form values
    $scope.clearForm = function () {
        $scope.address = "";
        $scope.price = "";
        $scope.area = "";
        $scope.rooms = "";
    };

    // create new flat
    $scope.createFlat = function () {
        // fields in key-value pairs
        $http.post('/app_dev.php/appartments/create', {
            'address': $scope.address,
            'price': $scope.price,
            'area': $scope.area,
            'rooms': $scope.rooms,
        })
        .success(function (response) {
            console.log(response);
            // hide the modal form
            $('#modal-flat-form').hide();
            // show add flat button
            $('#btn-add-flat').show();
            // clear modal content
            $scope.clearForm();
            // refresh the list
            $scope.getAll();
        });
    };

    // read flats
    $scope.getAll = function () {
        $http.get("/app_dev.php/appartments/read_all").success(function (response) {
            //console.log(response);
            $scope.flats = response.records;
        });
    };

    // retrieve record to fill out the form
    $scope.readOne = function (id) {
        console.log(id);
        // change modal title
        //$('#modal-flat-title').text("Edit Product");
        // show udpate flat button
        $('#btn-update-flat').show();
        // show create flat button
        $('#btn-create-flat').hide();
        // post id of flat to be edited
        $http.post('/app_dev.php/appartments/read', {
            'id': id
        })
        .success(function (data, status, headers, config) {
            console.log(data);
            // put the values in the form
            $scope.id = data[0]["id"];
            $scope.address = data[0]["address"];
            $scope.area = Number(data[0]["area"]);
            $scope.price = Number(data[0]["price"]);
            $scope.rooms = Number(data[0]["rooms"]);

            // show modal
            $('#modal-flat-form').show();
        })
        .error(function (data, status, headers, config) {
            console.log(data);
            alert('Der Datensatz konnte nicht abgerufen werden.');
        });
    };

    // update flat / save changes
    $scope.updateFlat = function () {
        $http.post('/app_dev.php/appartments/update', {
            'id': $scope.id,
            'address': $scope.address,
            'area': $scope.area,
            'price': $scope.price,
            'rooms': $scope.rooms
        })
        .success(function (data, status, headers, config) {
            console.log(data);
            // close modal
            $('#modal-flat-form').hide();
            // show create flat button
            $('#btn-add-flat').show();
            // clear modal content
            $scope.clearForm();
            // refresh the flat list
            $scope.getAll();
        });
    };

    $scope.closeModal = function () {
        // clear modal content
        $scope.clearForm();
        // close modal
        $('#modal-flat-form').hide();
        // show create flat button
        $('#btn-add-flat').show();
    };

    // delete flat
    $scope.deleteFlat = function (id) {
        // ask the user if he is sure to delete the record
        if (confirm("Are you sure?")) {
            // post the id of flat to be deleted
            $http.post('/app_dev.php/appartments/delete', {
                'id': id
            }).success(function (data, status, headers, config) {
                // refresh the list
                $scope.getAll();
            });
        }
    };

});


//  -----  jquery part  ---------

$(document).ready(function () {
    $('#modal-flat-form').hide();
    $('[data-toggle="tooltip"]').tooltip();
});