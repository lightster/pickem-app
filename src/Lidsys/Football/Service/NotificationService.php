<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Football\Service;

use DateTime;
use DateTimeZone;
use Exception;

use Lidsys\Application\Service\MailerService;

class NotificationService
{
    private $mailer;

    public function __construct(
        MailerService $mailer
    ) {
        $this->mailer      = $mailer;
    }

    public function sendWelcomeEmail($user)
    {
        $this->mailer->sendMessage(
            array(
                'to'      => "{$user['name']} <{$user['email']}>",
                'subject' => 'Fantasy Football \'14 - Week 1 starts THURSDAY',
                'text'    => <<<TEXT
Hello {{PLAYER_NAME}},

The regular season starts THIS THURSDAY, September 4. I recommend that you
start picking now so you don't forget to do so later—you can always change
your picks later, as long as it is before the game is locked.

The rules for this season of fantasy football are the same as last year:
- Each fantasy football pick'em participant ("player") chooses one team
  for EVERY game of the season.
- Picks must be submitted before the game begins.
- For every winning team chosen, the player earns one point. Losses
  result in zero points for that game. A tie between two teams results in
  one point being awarded to any player who chose for that game.
- If a player does not choose a team for a game, zero points
  will be awarded to the player for the respective game.
- During the playoffs, each win is worth progressively more points:
        Wild Card Weekend games = 2 points
        Divisional Playoff games = 4 points
        Conference Championship games = 8 points
        "The Big Game" = 16 points

To make your picks, visit the "My Picks" <{{BASE_URL}}/football/my-picks>
page and check one checkbox per game. Notice that this year there is no
save button—picks save automatically. The box around the checkbox will
change from red to green once your pick is automatically saved. Picks may
be changed anytime, up to the due date and time, by returning to the
"My Picks" <{{BASE_URL}}/football/my-picks> page. Also note that you may
view previous picks and choose picks for upcoming weeks by using the "Week"
selection menu near the top of the page.

If you forgot your account information, use the
"Need help logging in?" <{{BASE_URL}}/user/login/help> link on the login page.

If you know of anyone who wants to play,
send them a link to create an account <{{BASE_URL}}/user/register>.

Be sure to follow @LidsysFootball on Twitter <https://twitter.com/LidsysFootball>.

Please report any technical issues or other concerns to the Commissioner.

Good luck,

Matt Light
The Commissioner
Lightdatasys <http://lightdatasys.com>
@LidsysFootball <https://twitter.com/LidsysFootball>
TEXT
                ,
                'html'    => <<<HTML
<p>Hello {{PLAYER_NAME}},</p>

<p>
    The regular season starts THIS THURSDAY, September 4. I recommend that you
    start picking now so you don't forget to do so later—you can always change
    your picks later, as long as it is before the game is locked.
</p>

<p>
    The rules for this season of fantasy football are the same as last year:
    <ul>
        <li>
            Each fantasy football pick'em participant ("player") chooses one team
            for EVERY game of the season.
        </li>
        <li>
            Picks must be submitted before the game begins.
        </li>
        <li>
            For every winning team chosen, the player earns one point. Losses
            result in zero points for that game. A tie between two teams results in
            one point being awarded to any player who chose for that game.
        </li>
        <li>
            If a player does not choose a team for a game, zero points
            will be awarded to the player for the respective game.
        </li>
        <li>
            During the playoffs, each win is worth progressively more points:
            <ul>
                <li>2 points for Wild Card Weekend games</li>
                <li>4 points for Divisional Playoff games</li>
                <li>8 points for Conference Championship games</li>
                <li>16 points for "The Big Game"</li>
            </ul>
        </li>
    </ul>
</p>

<p>
    To make your picks, visit the <a href="{{BASE_URL}}/football/my-picks">My Picks</a>
    page and check one checkbox per game. Notice that this year there is no
    save button—picks save automatically. The box around the checkbox will change
    from red to green once your pick is automatically saved. Picks may
    be changed anytime, up to the due date and time, by returning to the
    <a href="{{BASE_URL}}/football/my-picks">My Picks</a> page. Also note that you may
    view previous picks and choose picks for upcoming weeks by using the "Week"
    selection menu near the top of the page.
</p>

<p>
    If you forgot your account information, use the
    <a href="{{BASE_URL}}/user/login/help">Need help logging in?</a> link on the login page.
</p>

<p>
    If you know of anyone who wants to play, send them a link to
    <a href="{{BASE_URL}}/user/register">create an account</a>.
</p>

<p>Be sure to follow <a href="https://twitter.com/LidsysFootball">@LidsysFootball</a> on Twitter.</p>

<p>Please report any technical issues or other concerns to the Commissioner.</p>

<p>Good luck,</p>

<p>
    Matt Light<br />
    The Commissioner<br />
    <a href="http://lightdatasys.com">Lightdatasys</a><br />
    <a href="https://twitter.com/LidsysFootball">@LidsysFootball</a>
</p>
HTML
                ,
            ),
            array(
                '{{PLAYER_NAME}}' => $user['name'],
            )
        );

        return true;
    }

    public function sendReminderEmail($user, $week, $first_game)
    {
        $week_number     = $week['week_number'];
        $year            = $week['year'];

        $utc_timezone    = new DateTimeZone('UTC');
        $first_game_time = new DateTime($first_game['start_time'], $utc_timezone);
        $pt_timezone     = new DateTimeZone('America/Los_Angeles');
        $first_game_time->setTimezone($pt_timezone);
        $first_game_day  = $first_game_time->format('l');

        $this->mailer->sendMessage(
            array(
                'to'      => "{$user['name']} <{$user['email']}>",
                'subject' => "Fantasy Football {$year} - Week {$week_number} starts {$first_game_day}",
                'text'    => <<<TEXT
Hello {{PLAYER_NAME}},

Remember to make your fantasy football picks for week {{WEEK_NUMBER}}. Each game has its
own individual due date and time, but the first game is {{FIRST_GAME_DAY}}.

If you already submitted your picks for week {{WEEK_NUMBER}}, you are good to go.

Good luck,

Matt Light
The Commissioner
Lightdatasys <http://lightdatasys.com>
@LidsysFootball <https://twitter.com/LidsysFootball>
TEXT
                ,
                'html'    => <<<HTML
<p>Hello {{PLAYER_NAME}},</p>

<p>
    Remember to make your fantasy football picks for week {{WEEK_NUMBER}}. Each game has its
    own individual due date and time, but the first game is {{FIRST_GAME_DAY}}.
</p>

<p>
    If you already submitted your picks for week {{WEEK_NUMBER}}, you are good to go.
</p>

<p>Good luck,</p>

<p>
    Matt Light<br />
    The Commissioner<br />
    <a href="http://lightdatasys.com">Lightdatasys</a><br />
    <a href="https://twitter.com/LidsysFootball">@LidsysFootball</a>
</p>
HTML
                ,
            ),
            array(
                '{{PLAYER_NAME}}'    => $user['name'],
                '{{WEEK_NUMBER}}'    => $week_number,
                '{{FIRST_GAME_DAY}}' => $first_game_day,
            )
        );

        return true;
    }
}
