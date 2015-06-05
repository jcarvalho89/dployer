<?php
namespace Dployer\Services;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProjectPacker
 *
 * This service will pack the latest state of current repository into a zip file
 * Also a copy the actual `vendor` directory will be present inside the created
 * zip.
 *
 * @package  Dployer\Services
 */
class ProjectPacker
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Sets the output interface
     *
     * @param  $OutputInterface $output
     */
    public function setOutput(OutputInterface $output = null)
    {
        $this->output = $output;
    }

    /**
     * Packs the current repository + the vendor directory. Returns the filename
     * or null if the file was not generated.
     *
     * @param array $excludePaths Paths to exclude before pack zip file
     * @param array $copyPaths Paths to copy from project before create zip file
     *
     * @return string Filename
     */
    public function pack(array $excludePaths = [], array $copyPaths = [])
    {
        // Clone the repo into a tmp folder
        $this->output->writeln("Clonning clean repository...");
        exec('git clone . ../.deployment > /dev/null');

        if (false === empty($copyPaths)) {
            $this->copyPaths($copyPaths);
        }

        // Create the zip the file
        $currentDir  = getcwd();
        $zipFilename = exec('echo ver_$(git log --format="%H" -n 1).zip');
        chdir("../.deployment");

        if (false === empty($excludePaths)) {
            $this->removePaths($excludePaths);
        }

        $this->output->writeln("Creating zip file...");
        exec('zip -r '.$zipFilename.' * > /dev/null');
        exec('mv '.$zipFilename.' "'.$currentDir.'/'.$zipFilename.'"');
        chdir($currentDir);

        // Remove tmp folder
        $this->output->writeln("Removing temporary files...");
        exec('rm -rf ../.deployment');

        return $zipFilename;
    }

    /**
     * Remvove the given paths
     *
     * @param  array  $paths
     */
    public function removePaths(array $paths)
    {
        $this->output->writeln(
            "Removing files in the 'exclude-paths' key from config file:"
        );

        foreach ($paths as $path) {
            $this->output->writeln(" * ".$path);
            exec('rm -rf '.$path);
        }
    }

    /**
     * Copy the given paths to .deployment folder
     *
     * @param  array  $paths
     */
    public function copyPaths(array $paths)
    {
        $this->output->writeln(
            "Copying files in the 'copy-paths' key from config file:"
        );

        foreach ($paths as $path) {
            $this->output->writeln(" * ".$path);
            exec(sprintf('cp -rf --parents %s ../.deployment', $path));
        }
    }
}
