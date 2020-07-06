<?php

namespace NX;

class GitHistory
{
    /**
     * An implementation of file history algorithm with renames detection.
     * (This comment block is from the IntelliJ source code at https://github.com/JetBrains/intellij-community/blob/ea20241265f9fb956c5a99b1023765aa0e941979/plugins/git4idea/src/git4idea/history/GitFileHistory.java)
     *
     * 'git log --follow' does detect renames, but it has a bug - merge commits aren't handled properly: they just disappear from the history.
     * See http://kerneltrap.org/mailarchive/git/2009/1/30/4861054 and the whole thread about that: --follow is buggy, but maybe it won't be fixed.
     * To get the whole history through renames we do the following:
     * 1. 'git log <file>' - and we get the history since the first rename, if there was one.
     * 2. 'git show -M --follow --name-status <first_commit_id> -- <file>'
     * where <first_commit_id> is the hash of the first commit in the history we got in #1.
     * With this command we get the rename-detection-friendly information about the first commit of the given file history.
     * (by specifying the <file> we filter out other changes in that commit; but in that case rename detection requires '--follow' to work,
     * that's safe for one commit though)
     * If the first commit was ADDING the file, then there were no renames with this file, we have the full history.
     * But if the first commit was RENAMING the file, we are going to query for the history before rename.
     * Now we have the previous name of the file:
     *
     * ~/sandbox/git # git show --oneline --name-status -M 4185b97
     * 4185b97 renamed a to b
     * R100    a       b
     *
     * 3. 'git log <rename_commit_id> -- <previous_file_name>' - get the history of a before the given commit.
     * We need to specify <rename_commit_id> here, because <previous_file_name> could have some new history, which has nothing common with our <file>.
     * Then we repeat 2 and 3 until the first commit is ADDING the file, not RENAMING it.
     *
     * @param  string       $file
     * @param  string|null  $hash
     *
     * @return string|null
     */
    public function findCreationDate(string $file, ?string $hash = null): ?string
    {
        $firstCommitId = $this->getFirstCommitId($file, $hash);

        if (empty($firstCommitId)) {
            $this->logError('not under version control');
            return null;
        }

        $commitType = $this->getCommitType($firstCommitId, $file);

        if (!preg_match('~([ACR])\d*\s+(\S+)(?:\s+(\S+))?~', $commitType, $match)) {
            $this->logError("unexpected commit type, terminating\n");
            throw new \RuntimeException("Unexpected commit type for $file: $commitType");
        }

        if ($match[1] === 'R') {
            // File was renamed, follow old file
            return $this->findCreationDate($match[2], $firstCommitId);
        }

        // File was created or copied
        return $this->getCommitDate('%Y', $firstCommitId, $file);
    }

    /**
     * @param  string  $command
     */
    private function logCommand(string $command): void
    {
        $this->writeLog("\$ {$command}\n");
    }

    /**
     * @param  string  $output
     */
    private function logOutput(string $output): void
    {
        $this->writeLog("> {$output}\n");
    }

    /**
     * @param  string  $error
     */
    private function logError(string $error): void
    {
        $this->writeLog("# {$error}\n");
    }

    /**
     * @param  string  $message
     */
    private function writeLog(string $message): void
    {
        #echo $message;
        file_put_contents(dirname(__DIR__) . '/log.txt', $message, FILE_APPEND);
    }

    /**
     * @param  string       $file
     * @param  string|null  $hash
     *
     * @return string
     */
    protected function getFirstCommitId(string $file, ?string $hash): string
    {
        if ($hash === null) {
            $command = "git log --pretty=format:%H \"{$file}\" | tail -n 1";
        } else {
            $command = "git log --pretty=format:%H \"{$hash}\" -- \"{$file}\" | tail -n 1";
        }

        return $this->runCommand($command);
    }

    /**
     * @param  string  $firstCommitId
     * @param  string  $file
     *
     * @return string
     */
    protected function getCommitType(string $firstCommitId, string $file): string
    {
        $command = "git show -M --follow --name-status \"{$firstCommitId}\" -- \"{$file}\" | tail -n 1";

        return $this->runCommand($command);
    }

    /**
     * @param  string  $format
     * @param  string  $firstCommitId
     * @param  string  $file
     *
     * @return string|null
     */
    protected function getCommitDate(string $format, string $firstCommitId, string $file): ?string
    {
        $command = "git log --date=format:\"{$format}\" --pretty=format:\"%cd\" \"{$firstCommitId}\" -- \"{$file}\" | tail -n 1";

        return $this->runCommand($command);
    }

    /**
     * @param  string  $command
     *
     * @return string
     */
    protected function runCommand(string $command): string
    {
        $this->logCommand($command);
        $output = trim(shell_exec($command));
        $this->logOutput($output);

        return $output;
    }
}
