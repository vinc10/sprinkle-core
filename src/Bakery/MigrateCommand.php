<?php

/*
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @copyright Copyright (c) 2019 Alexander Weissman
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\Core\Bakery;

use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UserFrosting\Bakery\WithSymfonyStyle;
use UserFrosting\Sprinkle\Core\Bakery\Helper\ConfirmableTrait;
use UserFrosting\Sprinkle\Core\Database\Migrator\Migrator;

/**
 * migrate Bakery Command
 * Perform database migration.
 */
class MigrateCommand extends Command
{
    use ConfirmableTrait;
    use WithSymfonyStyle;

    /** @Inject */
    protected Migrator $migrator;

    /** @Inject */
    protected Capsule $db;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('migrate')
             ->setDescription('Perform database migration')
             ->setHelp('This command runs all the pending database migrations.')
             ->addOption('pretend', 'p', InputOption::VALUE_NONE, 'Run migrations in "dry run" mode.')
             ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the operation to run when in production.')
             ->addOption('database', 'd', InputOption::VALUE_REQUIRED, 'The database connection to use.')
             ->addOption('step', 's', InputOption::VALUE_NONE, 'Migrations will be run so they can be rolled back individually.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title("UserFrosting's Migrator");

        // Get options
        $pretend = $input->getOption('pretend');
        $step = $input->getOption('step');

        // Get migrator
        $migrator = $this->setupMigrator($input);

        // Get pending migrations
        $pending = $migrator->getPending();

        // Don't go further if no migration is pending
        if (empty($pending)) {
            $this->io->success('Nothing to migrate');

            return self::SUCCESS;
        }

        // Show migrations about to be ran when in production mode
        //TODO : Reimplement production status
        // if ($this->isProduction()) {
            $this->io->section('Pending migrations');
            $this->io->listing($pending);

            // Confirm action when in production mode
            if (!$this->confirmToProceed($input->getOption('force'))) {
                exit(1);
            }
        // }

        // Run migration
        try {
            if ($pretend) {
                $migrated = $migrator->migrate($step);
            } else {
                $migrated = $migrator->pretendToMigrate();
            }
        } catch (\Exception $e) {
            // $this->displayNotes($migrator);
            $this->io->error($e->getMessage());
            exit(1);

            /*
            $messages = ['Unfulfillable migrations found :: '];
            foreach ($unfulfillable as $migration => $dependency) {
                $messages[] = "=> $migration (Missing dependency : $dependency)";
            }

            throw new \Exception(implode("\n", $messages));
            */
        }

        // Get notes and display them
        // $this->displayNotes($migrator);

        // If all went well, there's no fatal errors and we have migrated
        // something, show some success
        if (empty($migrated)) {
            $this->io->warning('Nothing migrated !');
        } else {
            $this->io->success('Migration successful !');
        }

        return self::SUCCESS;
    }

    /**
     * Setup migrator and the shared options between other command.
     *
     * @param InputInterface $input
     *
     * @return Migrator The migrator instance
     */
    protected function setupMigrator(InputInterface $input)
    {
        // Set connection to the selected database
        $database = $input->getOption('database');
        if ($database != '') {
            $this->io->note("Running {$this->getName()} with `$database` database connection");
            $this->db->getDatabaseManager()->setDefaultConnection($database);
        }

        // Make sure repository exist. Should be done in ServicesProvider,
        // but if we change connection, it might not exist
        if (!$this->migrator->repositoryExists()) {
            $this->migrator->getRepository()->create();
        }

        // Show note if pretending
        if ($input->hasOption('pretend') && $input->getOption('pretend')) {
            $this->io->note("Running {$this->getName()} in pretend mode");
        }

        return $this->migrator;
    }

    /**
     * Display migrator notes.
     *
     * @param Migrator $migrator
     */
    protected function displayNotes(Migrator $migrator)
    {
        if (!empty($notes = $migrator->getNotes())) {
            $this->io->writeln($notes);
        }
    }
}
