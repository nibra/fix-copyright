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
        $joomlaRoot                     = '/home/nibra/Development/joomla-cms/';
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
            ['administrator/components/com_admin/admin.php', '2006'],
            ['index.php', '2005'],
            ['administrator/components/com_actionlogs/controllers/actionlogs.php', '2018'],
            ['administrator/components/com_actionlogs/actionlogs.php', '2018'],
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