<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Console\Command;

use Hieu\VehicleFit\Model\CsvImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento vehiclefit:import var/import/fitment.csv
 * Good for cron or for a nightly feed from the ERP / supplier.
 */
class ImportCommand extends Command
{
    public function __construct(
        private readonly CsvImporter $importer,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('vehiclefit:import')
            ->setDescription('Import vehicle -> SKU fitment links from a CSV file')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to CSV (make,model,year_from,year_to,engine,sku)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = (string)$input->getArgument('file');
        if (!is_readable($file)) {
            $output->writeln(sprintf('<error>Cannot read %s</error>', $file));
            return Command::FAILURE;
        }

        try {
            $result = $this->importer->import($file);
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>%d rows, %d vehicles, %d links, %d skipped. Cache cleaned.</info>',
            $result['rows'],
            $result['vehicles'],
            $result['links'],
            count($result['errors'])
        ));
        foreach ($result['errors'] as $line => $error) {
            $output->writeln(sprintf('  line %d: %s', $line, $error));
        }

        return Command::SUCCESS;
    }
}
