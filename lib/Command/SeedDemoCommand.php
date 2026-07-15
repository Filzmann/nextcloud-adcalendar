<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Command;

use OCA\AdCalendar\Service\CalendarDemoPackService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** Zweck: Erzeugt idempotent benannte Demokonten, Gruppenmitgliedschaften und neutrale Kalendereintraege. */
final class SeedDemoCommand extends Command {
    public function __construct(
        private CalendarDemoPackService $demoPack,
    ) { parent::__construct(); }

    protected function configure(): void {
        $this->setName('adcalendar:demo:seed')->setDescription('Erzeugt vollständige AD-Kalender-Demokonten und Demodaten.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $result = $this->demoPack->install();
        $accountCount = $result['accounts']['createdUsers'] + $result['accounts']['reusedUsers'];
        $output->writeln("<info>{$accountCount} Demokonten synchronisiert; Kalendereinträge für {$result['createdCalendars']} erzeugt, {$result['skippedCalendars']} bereits vorhanden.</info>");
        return self::SUCCESS;
    }
}
