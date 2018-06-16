class LeakyBucket
{
    public $capacity=10;
    private $water = 0;
    public $rate;
    private $lastTime;

    /**
     * LeakyBucket constructor.
     * @param $capacity
     * @param $rate
     */
    public function __construct($capacity, $rate)
    {
        $this->capacity = $capacity;
        $this->rate = $rate;
        $this->lastTime = time();
    }

    /**
     * 当前是否能够接受请求
     * @return bool
     */
    public function grant()
    {
        //先放水
        $now = time();
        $this->water = max(0, $this->water - ($now - $this->lastTime) * $this->rate);
        if ($this->water < $this->cap) {
            $this->water++;
            $this->lastTime = $now;
            return true;
        }
        return false;
    }
}

$le = new LeakyBucket(10, 1);
$c = 0;
for ($i = 1; $i < 1000; $i++) {
    if ($le->grant()){
        $c++;
        echo "$c\n";
    }
    usleep(10000);
}
echo $c;
