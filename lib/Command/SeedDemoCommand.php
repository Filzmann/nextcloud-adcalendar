<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Command;

use DateTimeImmutable;
use DateTimeZone;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCA\AdCalendar\Service\DemoFixtureCatalog;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** Zweck: Erzeugt idempotent benannte Demokonten, Gruppenmitgliedschaften und neutrale Kalendereintraege. */
final class SeedDemoCommand extends Command {
    public function __construct(
        private IGroupManager $groups,
        private IUserManager $users,
        private CalendarEntryRepository $entries,
        private DemoFixtureCatalog $fixtures,
    ) { parent::__construct(); }

    protected function configure(): void {
        $this->setName('adcalendar:demo:seed')->setDescription('Erzeugt vollständige AD-Kalender-Demokonten und Demodaten.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $fixtures = $this->fixtures->all();
        $monday = new DateTimeImmutable('monday this week 08:00', new DateTimeZone('Europe/Berlin'));
        $createdEntries = 0;
        $skippedEntries = 0;
        foreach ($fixtures as $index => $fixture) {
            $user = $this->ensureUser($fixture['uid'], $fixture['name']);
            foreach ($fixture['groups'] as $groupId) $this->groups->createGroup($groupId)?->addUser($user);
            if ($this->entries->existsCreatedByForEmployee('demo-seed', $user->getUID(), $monday, $monday->modify('+7 days'))) {
                $skippedEntries++;
                continue;
            }
            $day = $monday->modify('+' . ($index % 5) . ' days');
            $utc = new DateTimeZone('UTC');
            $start = $day->setTimezone($utc);
            $end = $day->modify('+8 hours')->setTimezone($utc);
            $shiftId = $this->entries->save(CalendarEntry::get(['employeeUid' => $user->getUID(), 'start' => $start, 'end' => $end, 'type' => 'shift']), 'demo-seed');
            $this->entries->save(CalendarEntry::get(['employeeUid' => $user->getUID(), 'start' => $day->modify('+2 hours')->setTimezone($utc), 'end' => $day->modify('+3 hours')->setTimezone($utc), 'type' => 'appointment', 'title' => 'Neutraler Teamtermin', 'parentEntryId' => $shiftId]), 'demo-seed');
            $this->entries->save(CalendarEntry::get(['employeeUid' => $user->getUID(), 'start' => $day->modify('+10 hours')->setTimezone($utc), 'end' => $day->modify('+11 hours')->setTimezone($utc), 'type' => 'appointment', 'title' => 'Neutraler Sperrtermin']), 'demo-seed');
            $createdEntries++;
        }
        $count = count($fixtures);
        $output->writeln("<info>{$count} Demokonten synchronisiert; Kalendereinträge für {$createdEntries} erzeugt, {$skippedEntries} bereits vorhanden.</info>");
        return self::SUCCESS;
    }

    private function ensureUser(string $uid, string $displayName): IUser {
        $user = $this->users->get($uid) ?? $this->users->createUser($uid, bin2hex(random_bytes(24)));
        if ($user === null) throw new \RuntimeException("Demokonto {$uid} konnte nicht angelegt werden.");
        $user->setDisplayName($displayName);
        return $user;
    }
}
