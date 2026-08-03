<?php

namespace Tests\Feature;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\MasterSupervisor;
use Tests\TestCase;

class HorizonCheckCommandTest extends TestCase {
    public function testItFailsWhenNoMasterSupervisorIsHeartbeating(): void {
        $this->fakeHorizon([]);

        $this->artisan('mpm-system-checks:horizon')
            ->expectsOutputToContain('no master supervisor heartbeat')
            ->assertExitCode(Command::FAILURE);
    }

    public function testItFailsWhenAllMasterSupervisorsArePaused(): void {
        $this->fakeHorizon([$this->master('host-abcd', status: 'paused')]);

        $this->artisan('mpm-system-checks:horizon')
            ->expectsOutputToContain('paused')
            ->assertExitCode(Command::FAILURE);
    }

    public function testItFailsWhenTheMasterHasNoChildSupervisorHeartbeat(): void {
        $this->fakeHorizon([$this->master('host-abcd')], []);

        $this->artisan('mpm-system-checks:horizon')
            ->expectsOutputToContain('no child supervisor heartbeat')
            ->assertExitCode(Command::FAILURE);
    }

    public function testItFailsWhenEveryWorkerPoolHasDrained(): void {
        $this->fakeHorizon(
            [$this->master('host-abcd')],
            [$this->supervisor(['redis:payment' => 0, 'redis:sms' => 0, 'redis:token' => 0])]
        );

        $this->artisan('mpm-system-checks:horizon')
            ->expectsOutputToContain('No active worker processes found.')
            ->assertExitCode(Command::FAILURE);
    }

    public function testItCountsWorkerProcessesRatherThanQueues(): void {
        $this->fakeHorizon(
            [$this->master('host-abcd')],
            [$this->supervisor(['redis:payment' => 4, 'redis:sms' => 2])]
        );

        $this->artisan('mpm-system-checks:horizon')
            ->expectsOutputToContain('Active worker processes: 6.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function testItAcceptsTheMasterSupervisorOfTheCurrentHostWhenScopedLocally(): void {
        $this->fakeHorizon(
            [$this->master(MasterSupervisor::basename().'-abcd')],
            [$this->supervisor(['redis:payment' => 1])]
        );

        $this->artisan('mpm-system-checks:horizon', ['--local' => true])
            ->assertExitCode(Command::SUCCESS);
    }

    public function testItIgnoresMasterSupervisorsOfOtherHostsWhenScopedLocally(): void {
        $this->fakeHorizon(
            [$this->master('some-other-host-abcd')],
            [$this->supervisor(['redis:payment' => 1])]
        );

        $this->artisan('mpm-system-checks:horizon', ['--local' => true])
            ->expectsOutputToContain('no master supervisor heartbeat')
            ->assertExitCode(Command::FAILURE);
    }

    public function testItLogsCriticallyOnFailureWhenAlertingIsRequested(): void {
        Log::spy();
        $this->fakeHorizon([]);

        $this->artisan('mpm-system-checks:horizon', ['--alert' => true])
            ->assertExitCode(Command::FAILURE);

        Log::shouldHaveReceived('critical')->once();
    }

    public function testItDoesNotLogOnFailureWithoutAlerting(): void {
        Log::spy();
        $this->fakeHorizon([]);

        $this->artisan('mpm-system-checks:horizon')
            ->assertExitCode(Command::FAILURE);

        Log::shouldNotHaveReceived('critical');
    }

    /**
     * @param object[] $masters
     * @param object[] $supervisors
     */
    private function fakeHorizon(array $masters, array $supervisors = []): void {
        $this->mock(MasterSupervisorRepository::class, function ($mock) use ($masters) {
            $mock->shouldReceive('all')->andReturn($masters);
        });

        $this->mock(SupervisorRepository::class, function ($mock) use ($supervisors) {
            $mock->shouldReceive('get')->andReturn($supervisors);
        });

        $this->mock(JobRepository::class, function ($mock) {
            $mock->shouldReceive('countRecentlyFailed')->andReturn(0);
            $mock->shouldReceive('countPending')->andReturn(0);
        });
    }

    private function master(string $name, string $status = 'running'): object {
        return (object) [
            'name' => $name,
            'environment' => 'testing',
            'pid' => 1,
            'status' => $status,
            'supervisors' => [$name.':supervisor-1'],
        ];
    }

    /**
     * @param array<string, int> $processes
     */
    private function supervisor(array $processes): object {
        return (object) [
            'name' => 'host-abcd:supervisor-1',
            'master' => 'host-abcd',
            'pid' => 2,
            'status' => 'running',
            'processes' => $processes,
            'options' => ['timeout' => 60],
        ];
    }
}
