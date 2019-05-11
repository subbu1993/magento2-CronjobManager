<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Config\ScopeInterface;

use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Model\Data\Schedule;

class KillJobTest extends TestCase
{
    private $command;
    private $mockState;
    private $mockScheduleRepository;
    private $mockScheduleManagement;
    private $mockSearchCriteriaBuilder;
    private $mockFilterBuilder;
    private $mockFilterGroupBuilder;

    protected function setUp()
    {
        $this->mockState = $this->getMockBuilder(State::class)->setConstructorArgs([
            $this->createMock(ScopeInterface::class),
            State::MODE_PRODUCTION
        ])->getMock();

        $this->mockScheduleRepository = $this->getMockBuilder(ScheduleRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept([])
            ->getMock();

        $this->mockScheduleManagement = $this->getMockBuilder(ScheduleManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockSearchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->mockFilterBuilder = $this->getMockBuilder(FilterBuilder::class)->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->mockFilterGroupBuilder = $this->getMockBuilder(FilterGroupBuilder::class)->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->command = new \EthanYehuda\CronjobManager\Console\Command\KillJob(
            $this->mockState,
            $this->mockScheduleRepository,
            $this->mockScheduleManagement,
            $this->mockSearchCriteriaBuilder,
            $this->mockFilterBuilder,
            $this->mockFilterGroupBuilder
        );
    }

    public function testExecute()
    {
        $mockSchedule = new Schedule([
            "schedule_id" => "2246",
            "job_code" => "long_running_cron",
            "status" => "running",
            "pid" => 999,
            "kill_request" => null
        ]);

        $this->mockQueryResults([$mockSchedule]);

        $this->mockState->expects($this->once())
            ->method('setAreaCode')
            ->with(
                Area::AREA_ADMINHTML
            );

        $this->mockScheduleManagement->expects($this->once())
            ->method('kill')
            ->with(
                $this->equalTo("2246"),
                $this->isType('int')
            );

        $commandTester = new CommandTester($this->command);
        $resultCode = $commandTester->execute([
            'job_code' => 'sitemap_generate'
        ]);

        $this->assertEquals(0, $resultCode);
    }

    private function mockQueryResults($queryResults)
    {
        $this->mockFilterBuilder->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->createMock(Filter::class));
        $this->mockFilterGroupBuilder->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->createMock(FilterGroup::class));
        $this->mockSearchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($this->createMock(SearchCriteria::class));

        $searchResults = $this->createMock(\Magento\Framework\Api\SearchResultsInterface::class);

        $this->mockScheduleRepository->expects($this->once())
            ->method('getList')
            ->willReturn($searchResults);
        $searchResults->expects($this->once())
            ->method('getItems')
            ->willReturn($queryResults);
    }
}
