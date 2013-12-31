window.ActiveService = class ActiveService
    constructor: ->
        @user = null


    setUser: (@user) ->
    getUser: -> @user

    isLoggedIn: -> if @user.userId then true else false



window.User = class User
    constructor: ->
        @userId = null
        @permissions = {}


    isAuthorized: (permission) ->
        if @permissions[permission]? and @permissions[permission] then true else false


    setFromApi: (options) ->
        @userId = options.user_id
        @username = options.username
        @



window.FootballScheduleService = class FootballScheduleService
    constructor: (@$http, @$q) ->
        @seasons = null
        @weeks   = {}
        @games   = {}


    load: (current_route) ->
        year    = current_route.params.year
        week    = current_route.params.week
        @$q.when(@loadSeasons())
            .then((response) =>
                seasons = @getSeasons()
                if not seasons[year]?
                    for own a_year of seasons
                        year = a_year
                    current_route.params.year = year

                @$q.when(@loadWeeks(year))
            )
            .then((response) =>
                today = moment().format('YYYY-MM-DD')
                weeks = @getWeeks(year)
                if not weeks[week]?
                    week = 0
                    for own week_num, a_week of weeks
                        week = week_num if start_date > today or not week
                    current_route.params.week = week

                @$q.when(@loadGames(year, week))
            )


    loadSeasons: ->
        return @seasons if @seasons?
        @$http.get("/api/v1.0/football/seasons")
            .success((response) => @seasons = response.seasons)


    loadWeeks: (year) ->
        return @weeks[year] if @weeks[year]?
        @$http.get("/api/v1.0/football/weeks/" + year)
            .success((response) => @weeks[year] = response.weeks)


    loadGames: (year, week) ->
        return @games[year][week] if @games[year]? and @games[year][week]?
        @$http.get("/api/v1.0/football/schedule/" + year + "/" + week)
            .success((response) =>
                @games[year]       = {}
                @games[year][week] = response.games
                console.log @games
            )


    getSeasons: ->
        throw "Seasons not yet loaded using 'loadSeasons'" if not @seasons?
        @seasons


    getWeeks: (year) ->
        throw "Weeks not yet loaded using 'loadWeeks' for year" + year if not @weeks[year]?
        @weeks[year]


    getGames: (year, week) ->
        throw "Games not yet loaded using 'loadGames' for year " + year + " week " + week if not @games[year]? or not @games[year][week]?
        @games[year][week]
