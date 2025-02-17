<?php

/**
 * Gearman Bundle for Symfony2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

namespace Mmoreram\GearmanBundle\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mmoreram\GearmanBundle\Command\Abstracts\AbstractGearmanCommand;
use Mmoreram\GearmanBundle\Service\GearmanClient;
use Mmoreram\GearmanBundle\Service\GearmanDescriber;
use Mmoreram\GearmanBundle\Service\GearmanExecute;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Gearman Job Execute Command class
 *
 * @since 2.3.1
 */
class GearmanJobExecuteCommand extends AbstractGearmanCommand
{
    /**
     * @var GearmanClient
     *
     * Gearman client
     */
    protected $gearmanClient;

    /**
     * @var GearmanDescriber
     *
     * GearmanDescriber
     */
    protected $gearmanDescriber;

    /**
     * @var GearmanExecute
     *
     * Gearman execute
     */
    protected $gearmanExecute;

    /**
     * @var QuestionHelper
     *
     * Question
     */
    protected $question;

    /**
     * Set gearman client
     *
     * @param GearmanClient $gearmanClient Gearman client
     *
     * @return GearmanJobExecuteCommand self Object
     */
    public function setGearmanClient(GearmanClient $gearmanClient)
    {
        $this->gearmanClient = $gearmanClient;

        return $this;
    }

    /**
     * set Gearman describer
     *
     * @param GearmanDescriber $gearmanDescriber GearmanDescriber
     *
     * @return GearmanJobExecuteCommand self Object
     */
    public function setGearmanDescriber(GearmanDescriber $gearmanDescriber)
    {
        $this->gearmanDescriber = $gearmanDescriber;

        return $this;
    }

    /**
     * set Gearman execute
     *
     * @param GearmanExecute $gearmanExecute GearmanExecute
     *
     * @return GearmanJobExecuteCommand self Object
     */
    public function setGearmanExecute(GearmanExecute $gearmanExecute)
    {
        $this->gearmanExecute = $gearmanExecute;

        return $this;
    }

    /**
     * Console Command configuration
     */
    protected function configure()
    {
        $this
            ->setName('gearman:job:execute')
            ->setDescription('Execute one single job')
            ->addArgument(
                'job',
                InputArgument::REQUIRED,
                'job to execute'
            )
            ->addOption(
                'no-description',
                null,
                InputOption::VALUE_NONE,
                'Don\'t print job description'
            )
            ->addOption(
                'iterations',
                null,
                InputOption::VALUE_OPTIONAL,
                'Override configured iterations'
            )
            ->addOption(
                'minimum-execution-time',
                null,
                InputOption::VALUE_OPTIONAL,
                'Override configured minimum execution time'
            )
            ->addOption(
                'timeout',
                null,
                InputOption::VALUE_OPTIONAL,
                'Override configured timeout'
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract class is not implemented
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var QuestionHelper $question
         */
        $question = $this->getHelperSet()->get('question');

        if (
            !$input->getOption('no-interaction') &&
            !$question->ask(
                $input,
                $output,
                new ConfirmationQuestion('This will execute asked job?')
            )
        ) {
            return 0;
        }

        if (!$input->getOption('quiet')) {

            $output->writeln(sprintf(
                '<info>[%s] loading...</info>',
                date('Y-m-d H:i:s')
            ));
        }

        $job = $input->getArgument('job');
        $jobStructure = $this
            ->gearmanClient
            ->getJob($job);

        if (
            !$input->getOption('no-description') &&
            !$input->getOption('quiet')
        ) {

            $this
                ->gearmanDescriber
                ->describeJob(
                    $output,
                    $jobStructure,
                    true
                );
        }

        if (!$input->getOption('quiet')) {

            $output->writeln(sprintf(
                '<info>[%s] loaded. Ctrl+C to break</info>',
                date('Y-m-d H:i:s')
            ));
        }

        $this
            ->gearmanExecute
            ->setOutput($output)
            ->executeJob($job, array(
                'iterations'             => $input->getOption('iterations'),
                'minimum_execution_time' => $input->getOption('minimum-execution-time'),
                'timeout'                => $input->getOption('timeout')
            ));

        return 0;
    }
}
