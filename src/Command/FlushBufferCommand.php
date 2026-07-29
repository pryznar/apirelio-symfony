<?php

declare(strict_types=1);

namespace Tracium\Symfony\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tracium\Symfony\Transport\FileBufferTransport;

#[AsCommand(
    name: 'tracium:flush',
    description: 'Flush buffered Tracium events to the ingestion API.',
)]
final class FlushBufferCommand extends Command
{
    public function __construct(private readonly FileBufferTransport $transport)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->transport->flushIfDue(true);
        $output->writeln('<info>Tracium event buffer flushed.</info>');

        return self::SUCCESS;
    }
}
