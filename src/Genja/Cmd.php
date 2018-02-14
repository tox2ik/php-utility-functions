<?php

namespace Genja;

use AdamBrett\ShellWrapper\Command;
use AdamBrett\ShellWrapper\Command\Argument;
use AdamBrett\ShellWrapper\Command\Flag;
use AdamBrett\ShellWrapper\Command\Param;
use AdamBrett\ShellWrapper\Command\SubCommand;
use AdamBrett\ShellWrapper\Runners\ReturnValue;
use AdamBrett\ShellWrapper\Runners\Runner;

/**
 * Run a command in the OS and report if it failed.
 *
 * maybe-todo: enforce order
 *
 *   (new Cmd('zecho', 1))
 *       ->action('render')
 *       ->option('V')
 *       ->long('help')
 *       ->argument('./tmp file.xls')
 *       ->action('2>&1')
 *       ->run();
 */
class Cmd extends Command\AbstractCommand implements Runner, ReturnValue {

    protected $alwaysLog;
    public $command;
    public $exitCode;
    public $output;

    public function __construct($command = null, $alwaysLog = false)
    {
        $this->alwaysLog = $alwaysLog;
        parent::__construct($command);
        $this->command = new Command($command);
    }

    public function __invoke($binaryPath)
    {
        return new Cmd($binaryPath);
    }

    public function __toString()
    {
        return (string) $this->command;
    }

    /**
     * @param $subCommand string the add in git add
     * @return $this
     */
    public function action($subCommand)
    {
        $this->command->addSubCommand(new SubCommand($subCommand));
        return $this;
    }

    public function long($option, $value = null)
    {
        $this->command->addArgument(new Argument($option, $value));
        return $this;
    }

    public function option($flag, $value = null)
    {
        $this->command->addFlag(new Flag($flag, $value));
        return $this;
    }

    public function argument($arg)
    {
        $this->command->addParam(new Param($arg));
        return $this;
    }

    public function getReturnValue()
    {
        return $this->exitCode;
    }

    public function err2std()
    {
        return $this->action('2>&1');
    }

    /**
     * @param Command\CommandInterface|null $command
     * @param bool $alwaysLog write output to error log even if command succeeded
     */
    public function run(Command\CommandInterface $command = null, $alwaysLog = false)
    {
        $this->execWithReport((string) $this->command ?: $command, $alwaysLog || $this->alwaysLog);
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
