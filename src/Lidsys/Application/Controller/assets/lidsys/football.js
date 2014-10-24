var module =  angular.module('ldsFootball', ['ngRoute']);

module.config(['$injector', '$routeProvider', function ($injector, $routeProvider) {
    $routeProvider
        .when('/football/my-picks/:year?/:week?',
        {
            templateUrl: "/app/template/football/my-picks.html",
            controller: "LidsysFootballPicksCtrl",
            resolve: [[
                '$injector',
                '$route',
                '$q',
                'lidsysFootballSchedule',
                'lidsysFootballTeamStanding',
                function(
                    $injector,
                    $route,
                    $q,
                    footballSchedule,
                    footballTeamStanding
                ) {
                    var resolvers = $injector.get('lidsysFootballPicksRouteResolver');
                    return $q.all({
                        resolvePicks: $injector.invoke(resolvers.resolvePicks)
                    }).then(function () {
                        return footballTeamStanding.load(
                            footballSchedule.getSelectedSeason().year,
                            footballSchedule.getSelectedWeek().week_number
                        )
                    })
                }
            ]],
            navigationLabel: "My Picks",
            isFootball: true
        })
        .when('/football/league-picks/:year?/:week?',
        {
            templateUrl: "/app/template/football/league-picks.html",
            controller: "LidsysFootballLeaguePicksCtrl",
            resolve: $injector.get('lidsysFootballPicksRouteResolver'),
            navigationLabel: "League Picks",
            isFootball: true
        })
        .when('/football/fantasy-standings/:year?/:week?',
        {
            templateUrl: "/app/template/football/fantasy-standings.html",
            controller: "LidsysFootballFantasyStandingsCtrl",
            resolve: $injector.get('lidsysFootballFantasyStandingsRouteResolver'),
            navigationLabel: "Fantasy Standings",
            isFootball: true
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
            resolve: [[
                '$injector',
                '$route',
                '$q',
                'lidsysFootballSchedule',
                'lidsysFootballTeamStanding',
                function(
                    $injector,
                    $route,
                    $q,
                    footballSchedule,
                    footballTeamStanding
                ) {
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
                }
            ]],
            navigationLabel: "Team Standings",
            isFootball: true
        })
}])

module.constant('lidsysFootballWeekSensitiveRouteResolver', {
    resolveValidWeek: [
        '$location',
        '$q',
        '$route',
        'lidsysFootballSchedule',
        function (
            $location,
            $q,
            $route,
            footballSchedule
        ) {
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
        }
    ],
    resolveTeams: ['lidsysFootballTeam', function (footballTeam) {
        return footballTeam.load()
    }]
})

module.constant('lidsysFootballPicksRouteResolver', {
    resolvePicks: [
        '$injector',
        '$route',
        '$q',
        'lidsysFootballFantasyPlayer',
        'lidsysFootballPick',
        'lidsysFootballSchedule',
        function(
            $injector,
            $route,
            $q,
            footballFantasyPlayer,
            footballPick,
            footballSchedule
        ) {
            var resolvers = $injector.get('lidsysFootballWeekSensitiveRouteResolver');
            return $q.all({
                resolveValidWeek: $injector.invoke(resolvers.resolveValidWeek),
                resolveTeams:     $injector.invoke(resolvers.resolveTeams)
            }).then(function () {
                return footballPick.load(
                    footballSchedule.getSelectedSeason().year,
                    footballSchedule.getSelectedWeek().week_number
                )
            }).then(function () {
                return footballFantasyPlayer.load(
                    footballSchedule.getSelectedSeason().year
                )
            })
        }
    ]
})

module.constant('lidsysFootballFantasyStandingsRouteResolver', {
    resolveFantasyStandings: [
        '$injector',
        '$route',
        '$q',
        'lidsysFootballFantasyPlayer',
        'lidsysFootballFantasyStanding',
        'lidsysFootballSchedule',
        function(
            $injector,
            $route,
            $q,
            footballFantasyPlayer,
            footballFantasyStanding,
            footballSchedule
        ) {
            var resolvers = $injector.get('lidsysFootballWeekSensitiveRouteResolver');
            return $q.all({
                resolveValidWeek: $injector.invoke(resolvers.resolveValidWeek),
                resolveTeams:     $injector.invoke(resolvers.resolveTeams)
            }).then(function () {
                return footballFantasyPlayer.load(
                    footballSchedule.getSelectedSeason().year
                )
            }).then(function () {
                return footballFantasyStanding.load(
                    footballSchedule.getSelectedSeason().year
                )
            })
        }
    ]
})

module.factory('lidsysFootballFantasyPlayer', ['$http', '$q', function($http, $q) {
    return new FootballFantasyPlayerService($http, $q)
}])

module.factory('lidsysFootballFantasyStanding', ['$http', '$q', function($http, $q) {
    return new FootballFantasyStandingService($http, $q)
}])

module.factory('lidsysFootballPick', [
    '$http',
    '$timeout',
    '$q',
    '$window',
    function(
        $http,
        $timeout,
        $q,
        $window
    ) {
        return new FootballPickService($http, $timeout, $q, $window)
    }
])

module.factory('lidsysFootballSchedule', [
    '$http',
    '$q',
    'lidsysFootballTeam',
    function(
        $http,
        $q,
        footballTeam
    ) {
        return new FootballScheduleService($http, $q, footballTeam)
    }
])

module.factory('lidsysFootballTeam', ['$http', '$q', function($http, $q) {
    return new FootballTeamService($http, $q)
}])

module.factory('lidsysFootballTeamStanding', ['$http', '$q', function($http, $q) {
    return new FootballTeamStandingService($http, $q)
}])

module.factory('lidsysFootballTeamStylist', [function() {
    var service = {}

    service.getTeamNameBoxStyle = function(team) {
        return {
            'color':            team.font_color,
            'background-color': team.background_color,
            'width':            '40%'
        }
    }
    service.getTeamAccessoryBoxStyle = function(team) {
        return {
            'background-color': team.border_color,
            'width':            '4%'
        }
    }

    return service
}])

module.directive('ldsFootballWeekSelector', [function () {
    return {
        restrict: "E",
        controller: [
            '$location',
            '$route',
            '$scope',
            'lidsysFootballSchedule',
            function (
                $location,
                $route,
                $scope,
                footballSchedule
            ) {
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
            }
        ],
        templateUrl: "/app/template/football/week-selector.html"
    }
}])

module.controller('LidsysFootballPicksCtrl', [
    '$scope',
    'active',
    'lidsysFootballFantasyPlayer',
    'lidsysFootballPick',
    'lidsysFootballSchedule',
    'lidsysFootballTeam',
    'lidsysFootballTeamStanding',
    'lidsysFootballTeamStylist',
    function (
        $scope,
        active,
        footballPlayer,
        footballPick,
        footballSchedule,
        footballTeam,
        footballTeamStanding,
        footballTeamStylist
    ) {
        var season  = footballSchedule.getSelectedSeason(),
            week    = footballSchedule.getSelectedWeek(),
            picks   = footballPick.getPicks(season.year, week.week_number),
            teams   = footballTeam.getTeams(),
            games   = footballSchedule.getGames(),
            players = footballPlayer.getPlayers(season.year),
            game    = null,
            game_id = null,
            standing_id    = null,
            standings      = {},
            team_standing  = null,
            team_standings = footballTeamStanding.getTeamStandings(
                season.year,
                week.week_number
            ),
            current_player_id = active.getUser().playerId
        for (game_id in games) {
            game = games[game_id]

            game.is_started = moment(game.start_time).isBefore(moment())
            game.picks = picks[game.game_id]

            if (!game.picks) {
                game.picks = {}
            }
            if (!game.picks[current_player_id]) {
                game.picks[current_player_id] = {
                    game_id:   game_id,
                    player_id: current_player_id,
                    team_id:   null
                }
            }
        }

        for (standing_id in team_standings) {
            team_standing = team_standings[standing_id]
            standings[team_standing.team_id] = team_standing
        }

        $scope.currentPlayerId = current_player_id
        $scope.week            = week
        $scope.currentPlayer   = players[$scope.currentPlayerId]
        $scope.games           = games
        $scope.prevGameTime    = null
        $scope.standings        = standings
        $scope.errors          = footballPick.errors
        $scope.pickChanged = function (game, team) {
            if (game.is_started) {
                return
            }
            if (game.picks[current_player_id].team_id != team.team_id) {
                game.picks[current_player_id].team_id = team.team_id
            }
            footballPick.changePick(game, $scope.currentPlayer, team)
        }
        $scope.headerExists = function (game) {
            if ($scope.prevGameTime === game.start_time) {
                return false
            }

            $scope.prevGameTime = game.start_time
            return true
        }
        $scope.getPickedTeamStyle = function (game, team) {
            if (game.picks[$scope.currentPlayer.player_id].team_id == team.team_id) {
                return {
                    'background-color': '#' + $scope.currentPlayer.background_color,
                    'color':            '#' + $scope.currentPlayer.text_color
                }
            }

            return ""
        }
        $scope.getPickCellClasses = function (game, side, opp_side) {
            if (game.isFinal()) {
                return {
                    'label':      game.isFinal(),
                    'success':    game.isFinal() && side.score >= opp_side.score,
                    'alert':      side.score < opp_side.score,
                    'wrong-team': side.team.team_id != game.picks[$scope.currentPlayerId].team_id
                };
            } else if (!$scope.currentPlayer) {
                return {
                    'label':      false,
                    'success':    false,
                    'alert':      false,
                    'wrong-team': false
                };
            } else if (!$scope.isPickSaved(game)) {
                return {
                    'label':      true,
                    'success':    false,
                    'alert':      true,
                    'wrong-team': false
                };
            } else {
                return {
                    'label':      true,
                    'success':    true,
                    'alert':      false,
                    'wrong-team': false
                };
            }
        }
        $scope.isPickSaved = function (game) {
            var isSavePending = footballPick.isPickSavePending(game, $scope.currentPlayer)
            var isPicked
                =  typeof(game.picks) === "object"
                && typeof(game.picks[$scope.currentPlayer.player_id]) === "object"
                && game.picks[$scope.currentPlayer.player_id].team_id

            return !isSavePending && isPicked
        }

        $scope.getTeamNameBoxStyle      = footballTeamStylist.getTeamNameBoxStyle
        $scope.getTeamAccessoryBoxStyle = footballTeamStylist.getTeamAccessoryBoxStyle
    }
])

module.controller('LidsysFootballLeaguePicksCtrl', [
    '$scope',
    'active',
    'lidsysFootballPick',
    'lidsysFootballFantasyPlayer',
    'lidsysFootballSchedule',
    'lidsysFootballTeam',
    function (
        $scope,
        active,
        footballPick,
        footballPlayer,
        footballSchedule,
        footballTeam
    ) {
        var season  = footballSchedule.getSelectedSeason(),
            week    = footballSchedule.getSelectedWeek(),
            picks   = footballPick.getPicks(season.year, week.week_number),
            teams   = footballTeam.getTeams(),
            games   = footballSchedule.getGames(),
            players = footballPlayer.getPlayers(season.year),
            game    = null,
            game_id = null,
            pick_id = null,
            pick    = null,
            players_with_picks_hash = {},
            players_with_picks = [],
            player_id = null,
            player = null
        for (game_id in games) {
            game = games[game_id]

            game.picks = picks[game.game_id]
            for (pick_id in game.picks) {
                pick = game.picks[pick_id]
                players_with_picks_hash[pick.player_id] = players[pick.player_id]
            }
        }

        for (player_id in players_with_picks_hash) {
            players_with_picks.push(players_with_picks_hash[player_id])
        }
        players_with_picks.sort(function (a, b) {
            return a.name.localeCompare(b.name)
        })

        $scope.currentPlayerId  = active.getUser().playerId
        $scope.week             = week
        $scope.games            = games
        $scope.players          = players
        $scope.players_with_picks = players_with_picks
        $scope.playerCount      = 0
        $scope.prevGame         = null
        $scope.prevHeaderExists = null
        $scope.prevGameTime     = null

        var playerCount = 0
        for (var player_id in players) {
            var player = players[player_id]
            if (player.player_id) {
                ++playerCount
            }
        }
        $scope.playerCount = playerCount

        $scope.headerExists = function (game) {
            if ($scope.prevGame === game) {
                return $scope.prevHeaderExists
            }

            $scope.prevHeaderExists = ($scope.prevGameTime !== game.start_time)
            $scope.prevGameTime     = game.start_time
            $scope.prevGame         = game

            return $scope.prevHeaderExists
        }
        $scope.getPickedTeamStyle = function (player, game, team) {
            var losing_team_id = null
            if (game.home_score != game.away_score) {
                losing_team_id = game.home_score > game.away_score
                    ? game.away_team.team_id
                    : game.home_team.team_id
            }
            var pick = game.picks ? game.picks[player.player_id] : null
            if (pick
                && (
                    pick.team_id == team.team_id
                    && (moment(game.start_time).isAfter(moment())
                        || losing_team_id != pick.team_id)
                )
            ) {
                return {
                    'background-color': '#' + $scope.players[player.player_id].background_color,
                    'color':            '#' + $scope.players[player.player_id].text_color
                }
            } else {
                return {
                    'color': '#' + $scope.players[player.player_id].background_color
                }
            }
        }
    }
])

module.controller('LidsysFootballFantasyStandingsCtrl', [
    '$scope',
    'active',
    'lidsysFootballFantasyPlayer',
    'lidsysFootballSchedule',
    'lidsysFootballFantasyStanding',
    function (
        $scope,
        active,
        footballPlayer,
        footballSchedule,
        footballFantasyStanding
    ) {
        var season           = footballSchedule.getSelectedSeason(),
            selected_week    = footballSchedule.getSelectedWeek(),
            all_weeks        = footballSchedule.getWeeks(season.year),
            standings        = footballFantasyStanding.getStandings(season.year),
            players          = footballPlayer.getPlayers(season.year),
            weeks            = [],
            player_standings = [],
            rank             = 0,
            minPointsPerWeek = {},
            maxPointsPerWeek = {},
            possiblePoints   = 0,
            playedPoints = 0
        for (var week_num in all_weeks) {
            var week = all_weeks[week_num]
            weeks.push({
                week: week,
                week_num: week_num
            })
            possiblePoints += week.game_count * week.win_weight
            playedPoints += week.games_played * week.win_weight

            if (week == selected_week) {
                break
            }
        }

        for (var player_idx in players) {
            var player = players[player_idx],
                player_standing = {
                    player:           player,
                    standings:        [],
                    total_points:     0,
                    total_percent:    0,
                    potential_points: 0,
                    weighted_percent: 0,
                    rank:             0,
                    weeks_won:        0
                }
            for (var week_idx in weeks) {
                var week     = weeks[week_idx],
                    standing = standings[week.week_num] ? standings[week.week_num][player.player_id] : null

                if (standing) {
                    standing.percent = parseFloat(standing.points) / parseFloat(week.week.game_count)

                    player_standing.total_points     += parseInt(standing.points)
                    player_standing.potential_points += parseInt(standing.potential_points)
                    player_standing.standings.push({
                        standing: standing,
                        week:     week
                    })

                    var standing_points = parseInt(standing.points)

                    if (typeof minPointsPerWeek[week.week_num] == 'undefined') {
                        minPointsPerWeek[week.week_num] = standing.points
                    }
                    else {
                        minPointsPerWeek[week.week_num]
                            = Math.min(standing.points, minPointsPerWeek[week.week_num])
                    }

                    if (typeof maxPointsPerWeek[week.week_num] == 'undefined') {
                        maxPointsPerWeek[week.week_num] = standing.points
                    }
                    else {
                        maxPointsPerWeek[week.week_num]
                            = Math.max(standing.points, maxPointsPerWeek[week.week_num])
                    }
                }
                else {
                    player_standing.standings.push({
                        standing: {},
                        week:     week
                    })
                }
            }

            player_standing.total_percent = player_standing.total_points / possiblePoints
            player_standing.weighted_percent = player_standing.total_points / player_standing.potential_points

            player_standings.push(player_standing)
        }
        player_standings.sort(function (a, b) {
            var point_diff = b.total_points - a.total_points

            if (point_diff) {
                return point_diff
            } else {
                return a.player.name.localeCompare(b.player.name)
            }
        })
        var lastPoints = 0
        var rankTies = 1
        for (var player_standing_idx in player_standings) {
            var player_standing = player_standings[player_standing_idx]
            if (lastPoints != player_standing.total_points) {
                rank += rankTies
                rankTies = 1
            } else {
                ++rankTies
            }
            player_standings[player_standing_idx].rank = rank
            lastPoints = player_standing.total_points
        }

        for (var ps_idx = 0; ps_idx < player_standings.length; ps_idx++) {
            var player_standing = player_standings[ps_idx],
                standing_count  = player_standing.standings.length
            for (var standing_idx = 0; standing_idx < standing_count; standing_idx++) {
                var standing = player_standing.standings[standing_idx]
                if (standing.standing.points == maxPointsPerWeek[standing.week.week_num]
                    && maxPointsPerWeek[standing.week.week_num]
                ) {
                    player_standing.weeks_won++
                }
            }
        }

        $scope.currentPlayerId  = active.getUser().playerId
        $scope.week             = selected_week
        $scope.weeks            = weeks
        $scope.players          = players
        $scope.standings        = player_standings
        $scope.minPointsPerWeek = minPointsPerWeek
        $scope.maxPointsPerWeek = maxPointsPerWeek
        $scope.possiblePoints   = possiblePoints
        $scope.playedPoints     = playedPoints

        $scope.getDisplayNameStyle = function (player) {
            return {
                'background-color': '#' + player.background_color,
                'color':            '#' + player.text_color
            }
        }
        $scope.getWeekPointsStyle = function (player_standing) {
            var fontColor   = 192
                week        = player_standing.week,
                standing    = player_standing.standing,
                minPoints   = minPointsPerWeek[week.week_num],
                maxPoints   = maxPointsPerWeek[week.week_num],
                pointsRange = maxPoints - minPoints,
                pointsDiff  = 0
            if(maxPoints - minPoints == 0) {
                fontColor = 0
            } else if (standing) {
                pointsDiff = standing.points - minPoints
                fontColor  = parseInt(Math.max(
                    0,
                    parseInt(fontColor * (1 - (pointsDiff / (pointsRange))))
                ))
            }

            var style = {
                'color': 'rgb('
                    + fontColor
                    + ','
                    + fontColor
                    + ','
                    + fontColor
                    + ')'
            }

            if (maxPoints != minPoints && maxPoints == standing.points) {
                style['background-color'] = '#ffff99'
            }

            return style
        }
    }
])

module.controller('LidsysFootballScheduleCtrl', [
    '$scope',
    'lidsysFootballSchedule',
    'lidsysFootballTeam',
    'lidsysFootballTeamStylist',
    function (
        $scope,
        footballSchedule,
        footballTeam,
        footballTeamStylist
    ) {
        var teams   = footballTeam.getTeams(),
            games   = footballSchedule.getGames(),
            game    = null,
            game_id = null
        $scope.games        = games
        $scope.prevGameTime = null
        $scope.headerExists = function (game) {
            if ($scope.prevGameTime === game.start_time) {
                return false
            }

            $scope.prevGameTime = game.start_time
            return true
        }
        $scope.getTeamScoreCellClasses = function (game, side, opp_side) {
            return {
                'winning_team': game.isFinal() && side.score >= opp_side.score,
                'losing_team': game.isFinal() && side.score < opp_side.score
            }
        }
        $scope.getTeamNameBoxStyle      = footballTeamStylist.getTeamNameBoxStyle
        $scope.getTeamAccessoryBoxStyle = footballTeamStylist.getTeamAccessoryBoxStyle
    }
])

module.directive('ldsFootballDivisionSelector', [
    function () {
        return {
            restrict: "E",
            controller: [
                '$location',
                '$route',
                '$scope',
                'lidsysFootballTeamStanding',
                function ($location,  $route, $scope, footballTeamStanding) {
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
                }
            ],
            templateUrl: "/app/template/football/division-selector.html"
        }
    }
])

module.controller('LidsysFootballTeamStandingsCtrl', [
    '$scope',
    'lidsysFootballSchedule',
    'lidsysFootballTeam',
    'lidsysFootballTeamStanding',
    function ($scope, footballSchedule, footballTeam, footballTeamStanding) {
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
        for (standing_idx = 0; standing_idx < standings.length; standing_idx++) {
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
    }
])
