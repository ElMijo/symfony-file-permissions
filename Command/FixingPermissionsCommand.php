<?php

namespace SymfonyTools\FixingPermissionsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use SymfonyTools\FixingPermissionsBundle\Exception\InvalidPasswordException;
use SymfonyTools\FixingPermissionsBundle\Exception\UnsupportedOSException;
use Symfony\Component\Console\Input\InputOption;

/**
 *
 */
class FixingPermissionsCommand extends ContainerAwareCommand
{
    const MAC_OS = "Darwin";

    const LINUX_OS = "Linux";

    const FREEBSD_OS = "FreeBSD";

    const WINDOW_OS = "Windows";

    const WINDOW32_OS = "WIN32";

    const WINDOWNT_OS = "WINNT";

    const COMMAND_FAIL = 1;

    /**
     * The project absolute path
     * @var string
     */
    private $projectDir;

    /**
     * The kernel absolute path
     * @var string
     */
    private $kernelDirName;

    /**
     * The cache and log folder absolute path
     * @var string
     */
    private $cacheLogFolder;

    /**
     * The symfony version
     * @var float
     */
    private $symfonyVersion;

    /**
     * The current user
     * @var string
     */
    private $user;

    /**
     * The web server user
     * @var string
     */
    private $userGroup;

    protected function configure()
    {
        $this
            ->setName("symfony-tools:permissions")
            ->setDescription('This command allows you to define the user permissions within a Symfony or eZ project.')
            ->addOption(
                'current-user',
                null,
                InputOption::VALUE_NONE,
                'Permissions for the user running the command'
            )
            ->addOption(
                'clear-folder',
                null,
                InputOption::VALUE_NONE,
                'Not clean cache and logs folders'
            )
            ->addOption(
                'user',
                null,
                InputOption::VALUE_OPTIONAL,
                'User to be used in permissions'
            )
            ->addOption(
                'user-group',
                null,
                InputOption::VALUE_OPTIONAL,
                'User group to be used in permissions'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->projectDir = dirname(
            $this->getContainer()->getParameter('kernel.root_dir')
        );
        $this->kernelDirName = basename(
            $this->getContainer()->getParameter('kernel.root_dir')
        );
        $this->symfonyVersion = floatval(sprintf(
            "%s.%s",
            Kernel::MAJOR_VERSION,
            Kernel::MINOR_VERSION
        ));
        $this->cacheLogFolder = $this->getCacheLogFolder();
        $this->userGroup = $this->getUserGroup($input);
        $this->user = $this->getUser($input);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandList = $this->getCommandList($this->user, $this->userGroup);
        if ($input->getOption("clear-folder")) {
            $this->clearFolders();
        }
        // var_dump($commandList);exit;
        foreach ($commandList as $command) {
            system($command, $status);
            if (self::COMMAND_FAIL == $status) {
                throw new InvalidPasswordException();
            }
        }
        $output->writeln("<info>Successfully applied permissions [$this->user:$this->userGroup]</info>");
    }

    /**
     * It allows to obtain the list of commands according to the operating system
     * @param  string $user
     * @param  string $group
     * @return array
     *
     * @throw \SymfonyTools\FixingPermissionsBundle\Exception\UnsupportedOSException
     */
    private function getCommandList($user, $group)
    {
        switch (PHP_OS) {
            case self::MAC_OS:
                $commandList = [
                    sprintf(
                        'sudo chmod +a "%s allow delete,write,append,file_inherit,directory_inherit" %s/cache %s/logs',
                        $group,
                        $this->cacheLogFolder,
                        $this->cacheLogFolder
                    ),
                    sprintf(
                        'sudo chmod +a "%s allow delete,write,append,file_inherit,directory_inherit" %s/cache %s/logs',
                        $user,
                        $this->cacheLogFolder,
                        $this->cacheLogFolder
                    )
                ];
                if ($this->isEzPublish()) {
                    $commandList = [
                        sprintf(
                            'sudo chmod +a "%s allow delete,write,append,file_inherit,directory_inherit" s%/cache %s/logs %s/config %s/sessions %s/ezpublish_legacy/design %s/ezpublish_legacy/extension %s/ezpublish_legacy/settings %s/ezpublish_legacy/var %s/web',
                            $group,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir
                        ),
                        sprintf(
                            'sudo chmod +a "%s allow delete,write,append,file_inherit,directory_inherit" %s/cache %s/logs %s/config %s/sessions %s/ezpublish_legacy/design %s/ezpublish_legacy/extension %s/ezpublish_legacy/settings %s/ezpublish_legacy/var %s/web',
                            $user,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir
                        )
                    ];
                }
                break;
            case self::LINUX_OS:
                $commandList = [
                    sprintf(
                        'sudo setfacl -R -m u:%s:rwX -m u:%s:rwX %s/cache %s/logs',
                        $group,
                        $user,
                        $this->cacheLogFolder,
                        $this->cacheLogFolder
                    ),
                    sprintf(
                        'sudo setfacl -dR -m u:%s:rwX -m u:%s:rwX %s/cache %s/logs',
                        $group,
                        $user,
                        $this->cacheLogFolder,
                        $this->cacheLogFolder
                    )
                ];
                if ($this->isEzPublish()) {
                    $commandList = [
                        sprintf(
                            'sudo setfacl -R -m u:%s:rwx -m u:%s:rwx %s/cache %s/logs %s/config %s/sessions %s/ezpublish_legacy/design %s/ezpublish_legacy/extension %s/ezpublish_legacy/settings %s/ezpublish_legacy/var %s/web',
                            $group,
                            $user,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir
                        ),
                        sprintf(
                            'sudo setfacl -dR -m u:%s:rwx -m u:%s:rwx  %s/cache %s/logs %s/config %s/sessions %s/ezpublish_legacy/design %s/ezpublish_legacy/extension %s/ezpublish_legacy/settings %s/ezpublish_legacy/var %s/web',
                            $group,
                            $user,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->cacheLogFolder,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir,
                            $this->projectDir
                        )
                    ];
                }
                break;
            default:
                throw new UnsupportedOSException();
                break;
        }
        return $commandList;
    }

    /**
     * Clean cache and logs folders
     * @return void
     *
     * @throw \SymfonyTools\FixingPermissionsBundle\Exception\InvalidPasswordException
     */
    private function clearFolders()
    {
        system(sprintf("sudo rm -rf %s/cache/*", $this->cacheLogFolder), $status);
        if (self::COMMAND_FAIL == $status) {
            throw new InvalidPasswordException();
        }
        system(sprintf("sudo rm -rf %s/logs/*", $this->cacheLogFolder));
    }

    private function getUserGroup(InputInterface $input)
    {
        return $input->getOption("user-group") ? $input->getOption("user-group") : trim(shell_exec(
            "ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' \
             | grep -v root | head -1 | cut -d\  -f1"
        ));
    }

    private function getUser(InputInterface $input)
    {
        $user = get_current_user();

        if (!$input->getOption("current-user")) {
            $user = $input->getOption("user");
            if (!$user) {
                $user = $this->userGroup;
            }
        }
        return $user;
    }

    private function getCacheLogFolder()
    {
        $cacheLogFolder = sprintf("%s/%s", $this->projectDir, $this->kernelDirName);
        if ($this->symfonyVersion >= 3) {
            $cacheLogFolder = sprintf("%s/var", $this->projectDir);
        }
        return $cacheLogFolder;
    }

    private function isEzPublish()
    {
        return is_dir(sprintf("%s/ezpublish_legacy", $this->projectDir));
    }
}
