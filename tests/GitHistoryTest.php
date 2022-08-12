<?php

use NX\GitHistory;
use PHPUnit\Framework\TestCase;

/**
 * Class GitHistoryTest
 */
class GitHistoryTest extends TestCase
{
    /** @var GitHistory */
    private $history;

    /** @var false|string */
    private $originalWorkingDirectory;

    public static function setUpBeforeClass(): void
    {
        file_put_contents(dirname(__DIR__) . '/log.txt', '');
    }

    public function setUp(): void 
    {
        $joomlaRoot                     = ROOT_DIR;
        $this->originalWorkingDirectory = getcwd();
        chdir($joomlaRoot);
        
        $this->history = new GitHistory();
    }

    public function tearDown(): void 
    {
        chdir($this->originalWorkingDirectory);
    }

    /**
     * @return array|string[][]
     */
    public function cases(): array
    {
        return [
            ['index.php', '2005'],
            ['administrator/components/com_actionlogs/src/Controller/ActionlogsController.php', '2018'],
            ['plugins/system/log/log.xml', '2007'],
            ['libraries/src/Application/WebApplication.php', '2011'],
        ];
    }

    /**
     * @param  string  $file
     * @param  string  $expected
     *                          
     * @dataProvider cases
     */
    public function testFindCreationDate(string $file, string $expected): void
    {
        $this->assertEquals($expected, $this->history->findCreationDate($file));
    }
}