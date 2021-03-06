<?php

namespace Heyday\Satis\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\ArrayInput;

use Composer\Json\JsonFile;

class ConfigInit extends Command
{

    protected function configure()
    {
        $this
            ->setName('config-init')
            ->setDescription('Initialises a satis.json file through a dialog process')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_OPTIONAL,
                'The path to a config file to initialise',
                'satis.json'
            )
            ->addOption(
                'output-dir',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Location where to output built files',
                getcwd()
            );
    }

    protected function getRepositories(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $finished = false;
        $repos = array();

        while (!$finished) {

            $answer = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    'Do you want to add a repository',
                    'yes'
                ),
                'yes'
            );

            if ($answer == 'yes') {

                $type = $dialog->askAndValidate(
                    $output,
                    $dialog->getQuestion(
                        'What type is the repository?',
                        'satis-git'
                    ),
                    function ($answer) {
                        $types = array(
                            'composer',
                            'vcs',
                            'pear',
                            'git',
                            'svn',
                            'hg',
                            'satis-git'
                        );
                        if (!in_array($answer, $types)) {
                            throw new \RuntimeException('Repository type must be one of the following: ' . implode(', ', $types));
                        }
                        return $answer;
                    },
                    false,
                    'satis-git'
                );

                $url = $dialog->ask(
                    $output,
                    $dialog->getQuestion(
                        'What is the url of the repository?'
                    ),
                    null
                );

                $repos[] = array(
                    'type' => $type,
                    'url' => $url
                );
            } else {
                $finished = true;
            }

        }

        return $repos;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $name = $dialog->ask(
            $output,
            $dialog->getQuestion(
                'What is your repository\'s name?',
                'Statis'
            ),
            'Statis'
        );

        $homepage = $dialog->ask(
            $output,
            $dialog->getQuestion(
                'What is your repository\'s homepage?'
            ),
            null
        );

        $json = new JsonFile($input->getOption('file'));

        $options = array(
            'name' => $name,
            'homepage' => $homepage,
            'repositories' => $this->getRepositories($input, $output),
            'require-all' => true
        );

        if ($input->isInteractive()) {
            $output->writeln(array(
                '',
                $json->encode($options),
                ''
            ));
            if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
                $output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        $json->write($options);

        $this->runBuild($input, $output);

    }

    protected function runBuild(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        if ($dialog->ask(
            $output,
            $dialog->getQuestion(
                'Do you want to build the satis repository?',
                'yes'
            ),
            'yes'
        ) == 'yes') {
            $this->getApplication()->find('build')->run(new ArrayInput(array(
                'command' => 'build',
                'file' => $input->getOption('file'),
                'output-dir' => $input->getOption('output-dir')
            )), $output);
        }

    }

}
