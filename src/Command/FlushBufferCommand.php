<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Command;

use Apirelio\Symfony\Transport\FileBufferTransport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'apirelio:flush',
    description: 'Flush buffered Apirelio events to the ingestion API.',
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
        $output->writeln('<info>Apirelio event buffer flushed.</info>');

        return self::SUCCESS;
    }
}
