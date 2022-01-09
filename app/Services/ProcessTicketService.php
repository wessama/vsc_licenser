<?php


namespace App\Services;

use App\Ticket;
use App\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ProcessTicketService
{
    public $ticket;
    public $directory;
    public $authenticatedUrl;

    /**
     * ProcessTicketService constructor.
     * @param $ticket_id
     */
    public function __construct($ticket_id)
    {
        $this->ticket = Ticket::find($ticket_id);
        $this->directory = 'storage/' . uniqid();
        $this->authenticatedUrl = str_replace('https://', 'https://' . env('GITHUB_PAT', null) . '@', $this->ticket->repo);
    }

    /**
     * Entry point
     * @return bool
     */
    public function run()
    {
        // manually define the process here to work in the root cwd
        $process = new Process(['git', 'clone', $this->authenticatedUrl, $this->directory]);
        $process->start();
        $process->wait();
        $success = $process->isSuccessful();
        if ($success) {
            if(!empty($this->ticket->branch))
            {
                $checkout = $this->exec(['git', 'checkout', $this->ticket->branch]);
                if(!$checkout->isSuccessful())
                {
                    return false;
                }
            }
            $licenses = new LicenseService;
            $success = $licenses->exec($this->directory, $this->ticket->attachments->isNotEmpty() ? $this->ticket->attachments->first()->getPath() : '', $this->ticket->extensions);
            if ($success)
            {
                $success = ProcessTicketService::commit($this);
            }
            // TODO: Delete the directory when done
        } else {
            $this->log($process);
            return $success;
        }
        return $success;
    }

    /**
     * Commit and push to VCS
     * @param ProcessTicketService $instance
     * @return bool
     */
    public static function commit($instance)
    {
        $checkout = empty($instance->ticket->branch) ? 'external/License-Header' : 'external/License-Header-'.$instance->ticket->branch;
        $cmds = [
            ['git', 'checkout', '-b', $checkout],
            ['git', 'commit', '-a', '-m', 'Added license headers'],
            ['git', 'push', $instance->authenticatedUrl],
        ];
        foreach ($cmds as $cmd) {
            $process = $instance->exec($cmd);
            if(!$process->isSuccessful())
            {
                return false;
            }
        }
        return true;
    }

    /**
     * Process a shell command
     * @param array $cmd
     * @return Process
     */
    public function exec($cmd = [])
    {
        $process = new Process($cmd);
        $process->setWorkingDirectory($this->directory);
        $process->start();
        $process->wait();
        if (!$process->isSuccessful()) {
            $this->log($process);
        }
        return $process;
    }

    /**
     * Log ticket status
     * @param Process $process
     */
    public function log(Process $process)
    {
        $log = new Log();
        $log->ticket_id = $this->ticket->id;
        $log->exit_code = $process->getExitCode();
        $log->last_run_cmd = $process->getCommandLine();
        $log->message = $process->getErrorOutput();
        $log->started_at = $process->getStartTime();
        $log->ended_at = $process->getLastOutputTime();
        $log->save();
    }
}
