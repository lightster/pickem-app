window.FootballTeam = class FootballTeam
    @constructor: ->
        @abbreviation     = null
        @background_color = null
        @border_color     = null
        @conference       = null
        @division         = null
        @font_color       = null
        @location         = null
        @mascot           = null
        @team_id          = null

    setFromApi: (options) ->
        @abbreviation     = options.abbreviation
        @background_color = options.background_color
        @border_color     = options.border_color
        @conference       = options.conference
        @division         = options.division
        @font_color       = options.font_color
        @location         = options.location
        @mascot           = options.mascot
        @team_id          = options.team_id
        @



window.FootballScheduleService = class FootballScheduleService
    constructor: (@$http, @$q, @teamService) ->
        @seasons = null
        @weeks   = {}
        @games   = {}

        @selectedSeason = null
        @selectedWeek   = null



    setSelectedWeek: (selectedYear, selectedWeekNumber) ->
        @load selectedYear, selectedWeekNumber
    getSelectedSeason: -> @selectedSeason
    getSelectedWeek:   -> @selectedWeek



    load: (requestedYear, requestedWeek) ->
        year    = requestedYear
        week    = requestedWeek
        @$q.when(@loadSeasons())
            .then((response) =>
                seasons = @getSeasons()
                if not seasons[year]?
                    for own a_year of seasons
                        year = a_year

                @$q.when(@loadWeeks(year))
            )
            .then((response) =>
                today = moment().format('YYYY-MM-DD')
                weeks = @getWeeks(year)
                if not weeks[week]?
                    week = 0
                    for own week_num, a_week of weeks
                        week = week_num if a_week.start_date <= today or not week

                if requestedYear isnt year or requestedWeek isnt week
                    @$q.reject({
                        year,
                        week
                    })
                else
                    @selectedSeason = @getSeason year
                    @selectedWeek   = @getWeek year, week
                    @$q.when(@loadGames(year, week))
            )


    loadSeasons: ->
        return @seasons if @seasons?
        @$http.get("/api/v1.0/football/seasons")
            .success((response) => @seasons = response.seasons)


    loadWeeks: (year) ->
        return @weeks[year] if @weeks[year]?
        @$http.get("/api/v1.0/football/weeks/#{year}")
            .success((response) => @weeks[year] = response.weeks)


    loadGames: (year, week) ->
        return @games[year][week] if @games[year]? and @games[year][week]?
        @$http.get("/api/v1.0/football/schedule/#{year}/#{week}")
            .success((response) =>
                teams = @teamService.getTeams()
                games = response.games
                for own game_id, game of games
                    game.away_team = teams[game.away_team_id]
                    game.home_team = teams[game.home_team_id]
                    game.isStarted = moment().isAfter(game.start_time)

                @games[year]       = {}
                @games[year][week] = games
            )


    getSeason: (year) ->
        @getSeasons()[year]


    getSeasons: ->
        throw "Seasons not yet loaded using 'loadSeasons'" if not @seasons?
        @seasons


    getWeek: (year, week_num) ->
        @getWeeks(year)[week_num]


    getWeeks: (year) ->
        throw "Weeks not yet loaded using 'loadWeeks' for year #{year}" if not @weeks[year]?
        @weeks[year]


    getWeeksArray: (year) ->
        throw "Weeks not yet loaded using 'loadWeeks' for year #{year}" if not @weeks[year]?
        for own week_num, week of @weeks[year]
            week


    getGames: (year, week_num) ->
        if not year?
            year     = @selectedSeason.year 
            week_num = null
        if not week_num?
            week_num = @selectedWeek.week_number 

        throw "Games not yet loaded using 'loadGames' for year #{year} week #{week_num}" if not @games[year]? or not @games[year][week_num]?
        @games[year][week_num]



window.FootballTeamService = class FootballTeamService
    constructor: (@$http, @$q) ->
        @teams   = null



    load: ->
        @$q.when(@loadTeams())


    loadTeams: ->
        return @teams if @teams?
        @$http.get("/api/v1.0/football/teams")
            .success((response) =>
                @teams = {}
                for teamId, team of response.teams
                    @teams[teamId] = (new FootballTeam()).setFromApi team
            )


    getTeams: ->
        throw "Teams not yet loaded using 'loadTeams'" if not @teams?
        @teams




window.FootballTeamStandingService = class FootballTeamService
    constructor: (@$http, @$q) ->
        @teamStandings   = {}

        @selectedConference = 'AFC'
        @selectedDivision   = 'North'


    setSelectedConference: (@selectedConference) ->
    getSelectedConference: -> @selectedConference
    setSelectedDivision:   (@selectedDivision) ->
    getSelectedDivision:   -> @selectedDivision


    load: (requestedYear, requestedWeek) ->
        @$q.when(@loadTeamStandings(requestedYear, requestedWeek))


    loadTeamStandings: (year, week) ->
        return @teamStandings[year][week] if @teamStandings[year]? and @teamStandings[year][week]?
        @$http.get("/api/v1.0/football/team-standings/#{year}/#{week}")
            .success((response) =>
                @teamStandings[year]       = {}
                @teamStandings[year][week] = response.team_standings
            )


    getTeamStandings: (year, week_num) ->
        throw "Team standings not yet loaded using 'loadTeamStandings' for year #{year} week #{week_num}" if not @teamStandings[year]? or not @teamStandings[year][week_num]?
        @teamStandings[year][week_num]




window.FootballPickService = class FootballPickService
    constructor: (@$http, @$timeout, @$q, @$window) ->
        @picks              = {}
        @queuedPickChanges  = []
        @queueTimeout       = null
        @isSaving           = false
        @errors             = []


    load: (requestedYear, requestedWeek) ->
        @picks = {}
        @$q.when(@loadPicks(requestedYear, requestedWeek))


    loadPicks: (year, week) ->
        return @picks[year][week] if @picks[year]? and @picks[year][week]?
        @$http.get("/api/v1.0/football/fantasy-picks/#{year}/#{week}")
            .success((response) =>
                @picks[year]       = {}
                @picks[year][week] = response.fantasy_picks
            )


    getPicks: (year, week_num) ->
        throw "Picks not yet loaded using 'loadPicks' for year #{year} week #{week_num}" if not @picks[year]? or not @picks[year][week_num]?
        @picks[year][week_num]


    changePick: (game, player, team) ->
        @queuedPickChanges.push
            game_id: game.game_id
            team_id: team.team_id
        @savePicks() if not @isSaving
        true

    savePicks: ->
        @$timeout.cancel(@queueTimeout) if @queueTimeout
        @queueTimeout = @$timeout(
            () =>
                @isSaving = true

                picksHash = {}
                while @queuedPickChanges.length
                    pick = @queuedPickChanges.pop()
                    picksHash[pick.game_id] = pick.team_id

                pickCount = (k for own k of picksHash).length

                @errors.pop while @errors.length
                data   = {fantasy_picks: picksHash}
                @$http.post("/api/v1.0/football/fantasy-picks/", data)
                    .success((response) =>
                        if response.saved_picks.length != pickCount
                            console.log(response.saved_picks.length, pickCount)
                            @$window.location.reload()
                    )
                    .error((response) =>
                        @errors.push("Your picks could not be saved. Please try again.")
                    )
                    .finally(=>
                        @isSaving = false
                        @savePicks() if @queuedPickChanges.length
                        true
                    )
            500,
            true
        )

    isPickSavePending: (game, player) ->
        for queuedPick in @queuedPickChanges
            return true if queuedPick.game_id == game.game_id
        false




window.FootballFantasyPlayerService = class FootballFantasyPlayerService
    constructor: (@$http, @$q) ->
        @players              = {}


    load: (requestedYear, requestedWeek) ->
        @$q.when(@loadPlayers(requestedYear, requestedWeek))


    loadPlayers: (year) ->
        return @players[year] if @players[year]?
        @$http.get("/api/v1.0/football/fantasy-players/#{year}")
            .success((response) =>
                @players[year] = {}
                for playerId, player of response.fantasy_players
                    names = player.name.split(" ")
                    player.displayName = names[0][0] + names[0][names[0].length - 1] + names[1][0]
                    @players[year][playerId] = player
            )


    getPlayers: (year, week_num) ->
        throw "Players not yet loaded using 'loadPlayers' for year #{year}" if not @players[year]?
        @players[year]



window.FootballFantasyStandingService = class FootballFantasyStandingService
    constructor: (@$http, @$q) ->
        @standings              = {}


    load: (requestedYear) ->
        @$q.when(@loadStandings(requestedYear))


    loadStandings: (year) ->
        return @standings[year] if @standings[year]?
        @$http.get("/api/v1.0/football/fantasy-standings/#{year}")
            .success((response) =>
                @standings[year] = response.fantasy_standings
            )


    getStandings: (year) ->
        throw "Standings not yet loaded using 'loadStandings' for year #{year}" if not @standings[year]?
        @standings[year]
