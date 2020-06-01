<?php

namespace Aphpi\Installer\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class NewCommand extends Command
{
    const REPOSITORY = 'https://github.com/Aphpi/aphpi.git';
    const NAMESPACE = 'Aphpi\\Template';

    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Aphpi template')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addArgument('namespace', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $output->writeln('<info>Crafting application...</info>');

        $name = $input->getArgument('name');
        $directory = $name && $name !== '.' ? getcwd() . '/' . $name : getcwd();
        $namespace = $input->getArgument('namespace');

        $this->cloneRepository($directory);

        $this->replaceNamespaces($directory, $namespace);

        $commands = [
            'composer install',
        ];

        $process = Process::fromShellCommandline(implode(' && ', $commands), $directory);
        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        if ($process->isSuccessful()) {
            $output->writeln('<comment>Application ready! Build something amazing.</comment>');
        }

        return 0;
    }

    protected function cloneRepository(string $directory)
    {
        $process = Process::fromShellCommandline('git clone ' . self::REPOSITORY . ' ' . $directory);
        $process->run(function ($type, $line) {
            $this->output->write($line);
        });
    }

    protected function replaceNamespaces(string $directory, string $namespace)
    {
        $this->output->writeln('<info>Replacing Namespaces...</info>');

        $this->replaceNamespacesPath($directory . '/src', $namespace);
        $this->replaceNamespacesPath($directory . '/tests', $namespace);

        $this->replaceNamespace($directory . '/api', self::NAMESPACE, $namespace);
        $this->replaceNamespace($directory . '/composer.json', str_replace("\\", "\\\\", self::NAMESPACE), str_replace("\\", "\\\\", $namespace));
    }

    protected function replaceNamespacesPath(string $path, string $namespace)
    {
        $di = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($di);

        foreach($it as $file) {
            if (pathinfo($file, \PATHINFO_EXTENSION) == "php") {
                $this->replaceNamespace($file, self::NAMESPACE, $namespace);
            }
        }
    }

    protected function replaceNamespace(string $path, string $old_namespace, string $new_namespace)
    {
        $file_contents = file_get_contents($path);
        $file_contents = str_replace($old_namespace, $new_namespace, $file_contents);
        file_put_contents($path, $file_contents);
    }
}