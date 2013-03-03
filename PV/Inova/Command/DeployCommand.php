<?php 
namespace PV\Inova\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gitter\Client as Gitter;

class DeployCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('inova:deploy')
            ->setDescription(
                'Este comando fará o deploy de arquivos, usando log do git, diretamente para o servidor 
                Local da Inova.'
            )
            ->addArgument(
                'repositorio',
                InputArgument::REQUIRED,
                'Qual o caminho do repositório do GIT?'
            )
            ->addArgument(
                'nome-servidor',
                InputArgument::REQUIRED,
                'Qual o nome do servidor?'
            )
            ->addArgument(
                'destino',
                InputArgument::REQUIRED,
                'Qual o destino no servidor?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getConfig($output);

        $repository = $input->getArgument('repositorio');
        $server_name = $input->getArgument('nome-servidor');
        $destination_dir = $input->getArgument('destino');

        $repository = $config->root . DIRECTORY_SEPARATOR . $repository;

        if ( ! is_dir($repository)) {
            throw new \RunTimeException('Este repositório não existe!');
        }

        if ( ! isset($config->servers->$server_name)) {
            throw new \RunTimeException('Este servidor não existe!');
        }

        $client = new Gitter;
        $repository = $client->getRepository($repository);

        $last_commit = $repository->getCommits()[0];

        $changed = array_filter(explode("\n", $client->run(
            $repository, 
            'show --pretty="format:" --name-only '.$last_commit->getHash()
        )));

        if (empty($changed))
            "Nenhum arquivo modificado!";

        echo ('scp -r '.implode(' ', $changed).' '.$config->servers->$server_name.':'.$destination_dir);
    }

    protected function getConfig(OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        if ( ! is_file('config.json')) {
            $root = $this->askRoot($output);
            $servers = $this->askServers($output);
            //$projects = $this->askProjects($output); TODO: Implementar controle de projetos

            $config = [
                'root'    => $root,
                'servers' => $servers
            ];

            file_put_contents('config.json', json_encode($config));
        }

        return json_decode(file_get_contents('config.json'));
    }

    protected function askRoot(OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        
        return $dialog->askAndValidate(
            $output,
            '<question>Informe o diretório raiz das suas aplicações? [/home/www]</question> ',
            function ($answer) {
                if ( ! is_dir($answer)) {
                    throw new \RunTimeException(
                        'Este não é um diretório válido!'
                    );
                }
                return $answer;
            },
            true,
            '/home/www'
        );
    }

    protected function askServers(OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $servers = [];

        $i = 0;
        do
        {
            $server = $dialog->ask(
                $output,
                '<question>Informe um novo servidor? [false]</question> ',
                false
            );

            if ($server) {
                $default = $server.$i;

                $server_name = $dialog->ask(
                    $output,
                    '<question>Informe um nome para o servidor '.$server.'? ['.$default.']</question> ',
                    $default
                );

                $servers[$server_name] = $server;
                $i++;
            }

        } while( $server);

        return $servers;
    }
}