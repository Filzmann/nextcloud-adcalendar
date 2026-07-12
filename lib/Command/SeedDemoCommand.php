<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Command;

use DateTimeImmutable;
use DateTimeZone;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCA\AdCalendar\Service\CalendarAccessService;
use OCP\IGroupManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** Zweck: Erzeugt neutrale, wiedererkennbare Demoeintraege fuer vorhandene Rollenmitglieder. */
final class SeedDemoCommand extends Command {
    public function __construct(private IGroupManager $groups, private CalendarEntryRepository $entries) { parent::__construct(); }
    protected function configure(): void { $this->setName('adcalendar:demo:seed')->setDescription('Erzeugt neutrale AD-Kalender-Demodaten fuer die aktuelle Woche.'); }
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $users = [];
        foreach ([CalendarAccessService::ROLE_EB, CalendarAccessService::ROLE_PFK] as $groupId) {
            foreach ($this->groups->get($groupId)?->getUsers() ?? [] as $user) $users[$user->getUID()] = $user;
        }
        if ($users === []) { $output->writeln('<comment>Keine Mitglieder in ad-EB oder ad-PFK gefunden.</comment>'); return self::SUCCESS; }
        $monday = new DateTimeImmutable('monday this week 08:00', new DateTimeZone('Europe/Berlin'));
        if ($this->entries->existsCreatedBy('demo-seed', $monday, $monday->modify('+7 days'))) {
            $output->writeln('<comment>Demodaten fuer diese Woche existieren bereits.</comment>');
            return self::SUCCESS;
        }
        foreach (array_slice(array_values($users), 0, 6) as $index => $user) {
            $day = $monday->modify('+' . ($index % 5) . ' days');
            $this->entries->save(CalendarEntry::get(['employeeUid' => $user->getUID(), 'start' => $day->format(DATE_ATOM), 'end' => $day->modify('+8 hours')->format(DATE_ATOM), 'type' => 'shift']), 'demo-seed');
            $this->entries->save(CalendarEntry::get(['employeeUid' => $user->getUID(), 'start' => $day->modify('+2 hours')->format(DATE_ATOM), 'end' => $day->modify('+3 hours')->format(DATE_ATOM), 'type' => 'appointment', 'title' => 'Neutraler Teamtermin']), 'demo-seed');
            $this->entries->save(CalendarEntry::get(['employeeUid' => $user->getUID(), 'start' => $day->modify('+10 hours')->format(DATE_ATOM), 'end' => $day->modify('+11 hours')->format(DATE_ATOM), 'type' => 'appointment', 'title' => 'Neutraler Sperrtermin']), 'demo-seed');
        }
        $output->writeln('<info>Demodaten wurden erzeugt.</info>');
        return self::SUCCESS;
    }
}
