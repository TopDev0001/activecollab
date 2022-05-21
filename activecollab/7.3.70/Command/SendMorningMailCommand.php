<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use Angie\Command\Command;
use AngieApplication;
use ConfigOptions;
use DateTimeValue;
use DateValue;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SendMorningMailCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Send morning mail.')
            ->addArgument('day', InputArgument::OPTIONAL, "Day at which the morning mail is supposed to be dispatched (e.g. 2015-09-29). Ommit to use today's date");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $before_action_time = time();
            $output->writeln('<comment>Sending morning mail...</comment>');

            $day = $input->getArgument('day')
                ? DateValue::makeFromString($input->getArgument('day'))
                : DateTimeValue::now()->getSystemDate();

            // Dispatch the emails
            AngieApplication::morningMailResolver()->getMorningMailManager()->send($day);

            // Check if there are recent morning paper activty
            if ($last_mail_time = ConfigOptions::getValue('morning_paper_last_activity')) {
                if ($last_mail_time >= $before_action_time) {
                    return $this->success('Morning paper email was dispatched successfully!', $input, $output);
                }
            } else {
                throw new \RuntimeException('Whops! Something went wrong while dispathing the Morning paper email!');
            }

            return $this->success('Done', $input, $output);
        } catch (Throwable $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
