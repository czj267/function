
class TokenBucket
{
    private $capacity = 10;
    private $inRate = 1;
    private $water;
    private $lastTime;

    /**
     * TokenBucket constructor.
     * @param int $capacity
     * @param int $inRate
     */
    public function __construct($capacity, $inRate)
    {
        $this->capacity = $capacity;
        $this->inRate = $inRate;
        $this->lastTime = time();
        $this->water = $capacity;
    }

    public function grant()
    {
        //先加令牌
        $now = time();
        $this->water = min($this->capacity, $this->water + ($now - $this->lastTime) * $this->inRate);
        $this->lastTime = $now;

        if ($this->water > 0) {
            $this->water--;
            return true;
        } else {
            return false;
        }
    }
}

$token = new TokenBucket(10, 1);
$c = 0;
for ($i = 0; $i < 100; $i++) {
    $is = $token->grant();
    if ($is) {
        $c++;
        echo "$c\n";
    }
    usleep(100000);
}
