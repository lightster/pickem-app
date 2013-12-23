var app =  angular.module('app', ['ngRoute']);

app.config(['$routeProvider', function ($routeProvider) {
    $routeProvider
        .when('/',
        {
            template: "",
            controller: "AppCtrl"
        })
        .when('/user/login',
        {
            templateUrl: "/app/template/login/index.html",
            controller: "LoginCtrl"
        })
        .when('/football/picks',
        {
            template: "",
            controller: "AppCtrl"
        })
        .otherwise({
            template: "This doesn't exist!"
        });
}])

app.controller('AppCtrl', ['$scope', '$location', function ($scope, $location) {
}])

app.controller('LoginCtrl', ['$scope', '$location', '$http', function ($scope, $location, $http) {
    $scope.formChanged = function ($event) {
        var login = $scope.login;

        if (login.username != login.submittedUsername
            || login.password != login.submittedPassword
        ) {
            login.error = '';
        }
    }
    $scope.processLogin = function ($event) {
        var login = $scope.login;

        login.error             = {};
        login.submittedUsername = login.username;
        login.submittedPassword = login.password;

        if (!login.username) {
            login.error.hasError = true;
            login.error.username = 'Please enter your username.';
        }
        if (!login.password) {
            login.error.hasError = true;
            login.error.password = 'Please enter your password.';
        }

        if (login.error.hasError) {
            return false;
        }

        var postData = {
            username: login.username,
            password: login.password
        }

        $http.post("/app/user/login/", postData)
            .success(function (data) {
                if (data.authenticated_user) {
                    login.error.form = 'Success!!';
                }
                else {
                    login.error.form = 'The provided username/password are incorrect.';
                }
            })
            .error(function (data) {
                login.error.form = 'There was an error processing your login request. Please contact an administrator.';
            })

        return false;
    }

    $scope.login = {
        submitEnabled: false,
        error: {},
        username: '',
        password: '',
        previousUsername: '',
        previousPassword: ''
    }
}])