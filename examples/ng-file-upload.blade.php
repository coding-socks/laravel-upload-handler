<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>ng-file-upload</title>
</head>
<body>
    <h1>ng-file-upload</h1>

    <div ng-app="fileUpload" ng-controller="MyCtrl">
        <form name="myForm">
            <input type="file" ngf-select ng-model="picFile" name="file" required>
            <br/>

            <button ng-disabled="!myForm.$valid" ng-click="uploadPic(picFile)">Submit</button>
            <span ng-show="picFile.result">Upload Successful</span>
        </form>
    </div>

    <script src="//ajax.googleapis.com/ajax/libs/angularjs/1.7.9/angular.min.js"></script>
    <script src="//cdn.jsdelivr.net/gh/danialfarid/ng-file-upload/dist/ng-file-upload.min.js"></script>
    <script>
        const uploadUrl = "{{ url('upload') }}";
        const app = angular.module('fileUpload', ['ngFileUpload']);

        app.controller('MyCtrl', ['$scope', 'Upload', function ($scope, Upload) {
            $scope.uploadPic = function (file) {
                $scope.formUpload = true;
                if (file != null) {
                    $scope.upload(file);
                }
            };

            $scope.upload = function (file) {
                $scope.errorMsg = null;
                file.upload = Upload.upload({
                    url: uploadUrl,
                    resumeSizeUrl: uploadUrl + '?file=' + encodeURIComponent(file.name) + '&totalSize=' + file.size,
                    resumeSizeResponseReader: function(data) {return data.size;},
                    resumeChunkSize: 1024 * 1024,
                    data: {file: file}
                });
            };
        }]);
    </script>
</body>
</html>
