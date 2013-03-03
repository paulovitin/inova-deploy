<?php 
namespace PV\Inova\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployConfigCommand extends DeployCommand
{
    protected function configure()
    {
        $this
            ->setName('inova:deploy:config')
            ->setDescription(
                'Este comando fará a configuração para o deploy.'
            )
            ->addOption(
                'opcao',
                'null',
                InputArgument::OPTIONAL,
                'Qual opção?',
                'tudo'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $option = $input->getOption('opcao');
        $config_data = $config = [];

        if ($option === 'root' || $option === 'tudo') {
            $config['root'] = $this->askRoot($output);
        }

        if ($option === 'servers' || $option === 'tudo') {
            $config['servers'] = $this->askServers($output);
        }

        if ( is_file('config.json')) {
            $config_data = json_decode(file_get_contents('config.json'), true) ?: [];
        }

        $config_data = array_merge($config_data, $config);

        file_put_contents('config.json', json_encode($config_data));
    }
}