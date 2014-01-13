var app =  angular.module('app', ['ngRoute']);

app.config(['$injector', '$routeProvider', function ($injector, $routeProvider) {
    $routeProvider
        .when('/',
        {
            template: "Main",
            controller: "AppCtrl"
        })
        .when('/user/login',
        {
            templateUrl: "/app/template/login/index.html",
            controller: "LoginCtrl",
            navigationLabel: "Login"
        })
        .when('/football/schedule/:year?/:week?',
        {
            templateUrl: "/app/template/football/schedule.html",
            controller: "LidsysFootballScheduleCtrl",
            resolve: $injector.get('lidsysFootballWeekSensitiveRouteResolver'),
            navigationLabel: "Schedule",
            isFootball: true
        })
        .when('/football/team-standings/:year?/:week?',
        {
            templateUrl: "/app/template/football/team-standings.html",
            controller: "LidsysFootballTeamStandingsCtrl",
            resolve: [['$injector', '$route', '$q', 'lidsysFootballSchedule', 'lidsysFootballTeamStanding', function($injector, $route, $q, footballSchedule, footballTeamStanding) {
                var resolvers = $injector.get('lidsysFootballWeekSensitiveRouteResolver');
                return $q.all({
                    resolveValidWeek: $injector.invoke(resolvers.resolveValidWeek),
                    resolveTeams:     $injector.invoke(resolvers.resolveTeams)
                }).then(function () {
                    return footballTeamStanding.load(
                        footballSchedule.getSelectedSeason().year,
                        footballSchedule.getSelectedWeek().week_number
                    )
                })
            }]],
            navigationLabel: "Team Standings",
            isFootball: true
        })
        .otherwise({
            template: "This doesn't exist!"
        });
}])

app.factory('active', [function() {
    return new ActiveService()
}])

app.directive('ldsAuthenticated', ['$rootScope', 'active', function ($rootScope, active) {
    return {
        restrict: "A",
        link: function (scope, element, attrs) {
            var wasOriginallyDisplayed = element.css('display')
            $rootScope.$watch(
                function (scope) {
                    var expected = (attrs.ldsAuthenticated !== "false")
                    return expected === active.isLoggedIn()
                },
                function (isAsExpected, wasAsExpected, scope) {
                    if (!isAsExpected) {
                        element.css('display', 'none')
                    }
                    else {
                        element.css('display', wasOriginallyDisplayed)
                    }
                }
            )
        }
    }
}])

app.directive('ldsAuthorized', ['$rootScope', 'active', function ($rootScope, active) {
    return {
        restrict: "A",
        link: function (scope, element, attrs) {
            var wasOriginallyDisplayed = element.css('display')
            $rootScope.$watch(
                function (scope) {
                    return active.getUser().isAuthorized(attrs.ldsAuthorized)
                },
                function (isAuthorized, wasAuthorized, scope) {
                    if (!isAuthorized) {
                        element.css('display', 'none');
                    }
                    else {
                        element.css('display', wasOriginallyDisplayed)
                    }
                }
            )
        }
    }
}])

app.run(['$rootScope', 'active', function ($rootScope, active) {
    active.setUser(new User())
}])

app.controller('AppCtrl', ['$scope', '$http', function ($scope, $http) {
}])

app.controller('LoginCtrl', ['$scope', '$location', '$http', 'active', function ($scope, $location, $http, active) {
    $scope.formChanged = function ($event) {
        var login = $scope.login;

        if (login.username != login.submittedUsername ||
            login.password != login.submittedPassword
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
                    active.setUser((new User()).setFromApi(data.authenticated_user))
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




app.directive('ldsNavigation', [function () {
    return {
        restrict: "E",
        scope: {
        },
        controller: ['$attrs', '$route', '$scope', 'lidsysFootballSchedule', function ($attrs, $route, $scope, footballSchedule) {
            var navItems = [],
                routeDef,
                route,
                currentRoute   = $route.current,
                routeScope = $scope.$new(),
                url,
                urlParamNum,
                urlParam,
                urlParamFind,
                urlParamReplace
            for (routeDef in $route.routes) {
                route            = $route.routes[routeDef]
                routeScope.route = route
                if ((!$attrs.filter || routeScope.$eval($attrs.filter)) &&
                    route.navigationLabel
                ) {
                    url = "#" + routeDef

                    for (urlParamNum = 0; urlParamNum < route.keys.length; urlParamNum++) {
                        urlParam        = route.keys[urlParamNum]
                        urlParamFind    = "/:" + urlParam.name
                        urlParamReplace = currentRoute.params[urlParam.name]

                        if (urlParam.optional) {
                            urlParamFind += "?"
                        }
                        else if (!urlParamReplace) {
                            throw "'" + urlParam.name + "' is required by navigation but cannot be found in current route"
                        }

                        url = url.replace(urlParamFind, "/" + urlParamReplace)
                    }

                    navItems.push({
                        url:      url,
                        label:    route.navigationLabel,
                        selected: (currentRoute.originalPath == route.originalPath)
                    })
                }
            }
            $scope.navItems = navItems
        }],
        templateUrl: "/app/template/football/navigation.html"
    }
}])





app.constant('lidsysFootballWeekSensitiveRouteResolver', {
    resolveValidWeek: ['$location', '$q', '$route', 'lidsysFootballSchedule', function ($location, $q, $route, footballSchedule) {
        var year = $route.current.params.year,
            week = $route.current.params.week
        return footballSchedule.load(year, week)
            .catch(function (message) {
                if (message.year && message.week) {
                    $location.path(
                        $route.current.originalPath
                            .replace(":year?", message.year)
                            .replace(":week?", message.week)
                    ).replace()
                }

                return $q.reject(message)
            })
    }],
    resolveTeams: ['lidsysFootballTeam', function (footballTeam) {
        return footballTeam.load()
    }]
})

app.factory('lidsysFootballSchedule', ['$http', '$q', function($http, $q) {
    return new FootballScheduleService($http, $q)
}])

app.factory('lidsysFootballTeam', ['$http', '$q', function($http, $q) {
    return new FootballTeamService($http, $q)
}])

app.factory('lidsysFootballTeamStanding', ['$http', '$q', function($http, $q) {
    return new FootballTeamStandingService($http, $q)
}])

app.directive('ldsFootballWeekSelector', [function () {
    return {
        restrict: "E",
        controller: ['$location', '$route', '$scope', 'lidsysFootballSchedule', function ($location,  $route, $scope, footballSchedule) {
            var season = footballSchedule.getSelectedSeason(),
                week   = footballSchedule.getSelectedWeek()
            $scope.week_selector = {
                season:  season,
                week:    week,
                seasons: footballSchedule.getSeasons(),
                weeks:   footballSchedule.getWeeksArray(season.year)
            };
            $scope.changeSelectedWeek = function() {
                $location.path(
                    $route.current.originalPath
                        .replace(":year?", $scope.week_selector.season.year)
                        .replace(":week?", $scope.week_selector.week.week_number)
                )
            }
        }],
        templateUrl: "/app/template/football/week-selector.html"
    }
}])

app.controller('LidsysFootballScheduleCtrl', ['$scope', 'lidsysFootballSchedule', 'lidsysFootballTeam', function ($scope, footballSchedule, footballTeam) {
    var teams   = footballTeam.getTeams(),
        games   = footballSchedule.getGames(),
        game    = null,
        game_id = null
    for (game_id in games) {
        game = games[game_id]

        if (game.away_team_id && !game.away_team) {
            game.away_team = teams[game.away_team_id]
            game.home_team = teams[game.home_team_id]
        }
    }
    $scope.games        = games
    $scope.prevGameTime = null
    $scope.headerExists = function (game) {
        if ($scope.prevGameTime === game.start_time) {
            return false
        }

        $scope.prevGameTime = game.start_time
        return true
    }
}])

app.directive('ldsFootballDivisionSelector', [function () {
    return {
        restrict: "E",
        controller: ['$location', '$route', '$scope', 'lidsysFootballTeamStanding', function ($location,  $route, $scope, footballTeamStanding) {
            // var conference = footballTeamStanding.getSelectedConference(),
            //     division   = footballTeamStanding.getSelectedDivision()
            $scope.getSelectedConference = function () {
                return footballTeamStanding.getSelectedConference()
            }
            $scope.setSelectedConference = function ($event, conference) {
                $event.preventDefault()
                footballTeamStanding.setSelectedConference(conference)
                $route.reload()
            }
            $scope.getSelectedDivision   = function () {
                return footballTeamStanding.getSelectedDivision()
            }
            $scope.setSelectedDivision   = function ($event, division) {
                $event.preventDefault()
                footballTeamStanding.setSelectedDivision(division)
                $route.reload()
            }
        }],
        templateUrl: "/app/template/football/division-selector.html"
    }
}])

app.controller('LidsysFootballTeamStandingsCtrl', ['$scope', 'lidsysFootballSchedule', 'lidsysFootballTeam', 'lidsysFootballTeamStanding', function ($scope, footballSchedule, footballTeam, footballTeamStanding) {
    var season = footballSchedule.getSelectedSeason(),
        week   = footballSchedule.getSelectedWeek(),
        conference = footballTeamStanding.getSelectedConference(),
        division   = footballTeamStanding.getSelectedDivision()
    var teams               = footballTeam.getTeams(),
        standings           = footballTeamStanding.getTeamStandings(
            season.year,
            week.week_number
        ),
        standing_idx,
        standing,
        team,
        filteredStandings = []
    for (standing_idx in standings) {
        standing = standings[standing_idx]
        if (standing.team_id) {
            team          = teams[standing.team_id]
            standing.team = team
            if (team.conference == conference && team.division == division) {
                filteredStandings.push(standing)
            }
        }
    }
    $scope.standings = filteredStandings
}])