<?php

namespace Genja;

use ReflectionClass;

/**
 * Run a command in the OS and report if it failed.
 *
 * Inspired by the over-engineered https://github.com/adambrett/php-shell-wrapper
 *
 * The arguments, options and sub commands are ordered.
 */
class Cmd
{

    public $parts = [];
    protected $alwaysLog;
    public $command;
    public $exitCode;
    public $output;

    /**
     * @return Cmd|object
     */
    public static function gi()
    {
        try {

        return (new ReflectionClass(get_called_class()))->newInstanceArgs(func_get_args());
        } catch (\Exception $e) {
            return new Cmd;
        }



    }

    public function __construct($command = null, $alwaysLog = false)
    {
        $this->command = (string) $command;
        $this->parts = [];
    }

    public function __invoke($binaryPath)
    {
        return new Cmd($binaryPath);
    }

    /**
     * @return string ready-to-run command (escaped)
     */
    public function __toString()
    {
        foreach ($this->parts as $i => $e) {
            $this->parts[$i] = (string) $e;
        }
        $args = join(' ', $this->parts);
        return "$this->command $args";

    }

    /**
     * @param $subCommand string the add in git add
     * @return $this
     */
    public function action($subCommand)
    {
        $this->parts[] = escapeshellcmd((string) $subCommand);
        return $this;
    }

    public function long($option, $value = null)
    {
        $this->parts[]= "--$option";
        $values = [];
        if (! is_array($value) && $value !== null) {
            $values = [ $value ];
        }
        foreach ($values as $e) {
            $this->parts[] = escapeshellarg($e);
        }
        return $this;
    }

    /**
     * @param $flag
     * @param null $value
     * @return $this
     */
    public function option($flag, $value = null)
    {
        $this->parts[]= strpos($flag, '+') === 0 ? $flag : "-$flag";
        $values = [];
        if (! is_array($value) && $value !== null) {
            $values = [ $value ];
        }
        foreach ($values as $e) {
            $this->parts[] = escapeshellarg($e);
        }
        return $this;
    }

    /**
     * @param $flag
     * @param null $value
     * @return $this
     */
    public function short($flag, $value = null)
    {
        $this->option($flag, $value);
        return $this;
    }

    /**
     * @param $arg
     * @return $this
     */
    public function argument($arg)
    {
        $this->parts[] = escapeshellarg($arg);
        return $this;
    }

    /**
     * @return int exit code
     */
    public function getReturnValue()
    {
        return $this->exitCode;
    }

    /**
     * Redirect standard-error to standard-out
     * @return Cmd
     */
    public function err2std()
    {
        return $this->action('2>&1');
    }

    /**
     * @param Command\CommandInterface|null $command
     * @param bool $alwaysLog write output to error log even if command succeeded
     */
    public function run($alwaysLog = false)
    {
        $this->execWithReport((string) $this, $alwaysLog || $this->alwaysLog);
        return $this;
    }

    /**
     * Execute an OS command and write to error log if it is not happy.
     * @param $shellCommand string is run as is. You can add stderr (2>&1) redirection if needed.
     * @param $reportIfSuccess boolean write to the log even if exit status is 0.
     */
    protected function execWithReport($shellCommand, $reportIfSuccess = false) {
        $this->output = array();
        exec($shellCommand, $this->output, $this->exitCode);

        if ($this->exitCode != 0 || $reportIfSuccess) {
            $outLines = implode("\n", $this->output);
            $failed = $this->exitCode == 0 ? '' : ' failed';
            $out = "Command$failed: $shellCommand";
            if ($outLines) {
                $out .= "\nOutput;\n$outLines";
            }
            error_log($out);
        }
    }


}
