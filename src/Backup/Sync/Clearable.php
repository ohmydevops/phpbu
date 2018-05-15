<?php
namespace phpbu\App\Backup\Sync;

use phpbu\App\Backup\Collector;
use phpbu\App\Backup\Target;
use phpbu\App\Configuration\Backup\Cleanup;
use phpbu\App\Factory;
use phpbu\App\Result;

/**
 * Clearable trait
 *
 * @package    phpbu
 * @subpackage Sync
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @author     Vitaly Baev <hello@vitalybaev.ru>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://phpbu.de/
 * @since      Class available since Release 5.1.0
 */
trait Clearable
{
    /**
     * @var \phpbu\App\Configuration\Backup\Cleanup
     */
    protected $cleanupConfig;

    /**
     * @var \phpbu\App\Backup\Cleaner
     */
    protected $cleaner;

    /**
     * Check sync clean configuration entities and set up a proper cleaner
     *
     * @param  array $options
     * @throws \phpbu\App\Exception
     */
    public function setUpClearable(array $options)
    {
        $config = [];
        foreach ($options as $key => $value) {
            if (strpos($key, "cleanup.") === 0) {
                $config[str_replace('cleanup.', '', $key)] = $value;
            }
        }

        if (isset($config['type'])) {
            $skip = isset($config['skipOnFailure']) ? (bool) $config['skipOnFailure'] : true;
            // creating cleanup config
            $this->cleanupConfig = new Cleanup($config['type'], $skip, $config);
            // creating cleaner
            $this->cleaner = (new Factory())->createCleaner($this->cleanupConfig->type, $this->cleanupConfig->options);
        }
    }

    /**
     * Creates collector for remote cleanup
     *
     * @param Target $target
     * @return Collector
     */
    abstract protected function createCollector(Target $target): Collector;

    /**
     * Execute the remote clean up if needed
     *
     * @param \phpbu\App\Backup\Target $target
     * @param \phpbu\App\Result        $result
     */
    public function cleanup(Target $target, Result $result)
    {
        if (!$this->cleaner) {
            return;
        }

        $collector = $this->createCollector($target);
        $this->cleaner->cleanup($target, $collector, $result);
    }

    /**
     * Simulate remote cleanup.
     *
     * @param Target $target
     * @param Result $result
     */
    public function simulateRemoteCleanup(Target $target, Result $result)
    {
        if ($this->cleaner) {
            $result->debug("  sync cleanup: {$this->cleanupConfig->type}" . PHP_EOL);
        }
    }
}
