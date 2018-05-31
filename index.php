<?php

include 'data.php';
$_SESSION['data'] = $data;

trait Poisson
{
    protected $epsilon = 0.0001;

    function chance($lambda, $goals)
    {
        $f = function ($n) {
            if ($n < 0) {
                throw \Exception('Wrong argument for factorial.');
            }
            $r = 1;
            for ($n; $n >= 2;) {
                $r *= $n--;
            }

            return $r;
        };

        return pow($lambda, $goals) / $f($goals) * pow(M_E, -1 * $lambda);
    }

    public function distribution($lambda)
    {
        $distribution = [];
        $i = 0;
        $last_step = 0;
        while (true) {
            $chance = $this->chance($lambda, $i);
            if ($chance < $this->epsilon) {
                break;
            }
            $last_step += $chance;
            $distribution[$i] = $last_step;
            $i++;
        }

        return $distribution;
    }
}

class Team
{
    use Poisson;

    CONST POINT_FOR_GAME = 10000;
    CONST POINT_FOR_DRAW = 10000;
    CONST POINT_FOR_WIN = 30000;
    CONST POINT_FOR_SCORED_GOAL = 501;
    CONST POINT_FOR_SKIPPED_GOAL = -500;

    /**
     * @var array $statistic
     */
    private $statistic;
    /**
     * рэйтинг или "мощь" команды
     * @var float|int $rating
     */
    private $rating;

    public function __construct($team_id, $teams)
    {
        if (isset($teams[$team_id])) {
            $this->statistic = $teams[$team_id];
            $this->rating = ($this->statistic['games'] * self::POINT_FOR_GAME +
                    $this->statistic['draw'] * self::POINT_FOR_DRAW +
                    $this->statistic['win'] * self::POINT_FOR_WIN +
                    $this->statistic['goals']['scored'] * self::POINT_FOR_SCORED_GOAL +
                    $this->statistic['goals']['skipped'] * self::POINT_FOR_SKIPPED_GOAL) / $this->statistic['games'];
        } else {
            throw new \Exception('Team #' . $team_id . ' not found;');
        }
    }

    /**
     * @return float|int
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param Team $team_opponent
     * @return float
     */
    protected function makeLambda(Team $team_opponent)
    {
        $rating_correction = $this->getRating() / $team_opponent->getRating() - 1;
        $rating_correction_opponent = $team_opponent->getRating() / $this->getRating() - 1;
        $scored = $this->statistic['goals']['scored'] / $this->statistic['games'] + $rating_correction;
        $skipped = $team_opponent->statistic['goals']['skipped'] / $team_opponent->statistic['games'] - $rating_correction_opponent;

        return ($scored + $skipped) / 2;
    }

    /**
     * @param Team $team_opponent
     * @return int
     */
    public function goals(Team $team_opponent)
    {
        $goals = $this->distribution($this->makeLambda($team_opponent));
        $rand = rand(0, 100000) / 100000;
        foreach ($goals as $goal => $chance) {
            if ($rand < $chance) {
                return $goal;
            }
        }

        return $goal;
    }

    /**
     * @param Team $team_opponent
     * @return array
     */
    public function play(Team $team_opponent)
    {
        return [$this->goals($team_opponent), $team_opponent->goals($this)];
    }
}

function match($team_a, $team_b)
{
    $team_a = new Team($team_a, $_SESSION['data']);
    $team_b = new Team($team_b, $_SESSION['data']);
    return $team_a->play($team_b);
}


