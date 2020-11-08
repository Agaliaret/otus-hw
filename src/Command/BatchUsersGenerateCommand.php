<?php

namespace OtusHw\Command;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OtusHw\Exception\UsernameInUseRegistrationException;
use OtusHw\Security\User;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BatchUsersGenerateCommand extends UsersGenerateCommand
{
    protected const BATCH_SIZE = 1000;

    protected static $defaultName = 'users:batch-generate';

    protected function configure()
    {
        $this
            ->setDescription('Generates random users names combinations from user names list and user\'s info (using once generated info for a batch of users, while name and surname generates for each individual user)')
            ->addArgument('file', InputArgument::OPTIONAL, 'Path to file with names', __DIR__ . '/../../generation/names.txt')
            ->addOption('amount', 'a', InputOption::VALUE_REQUIRED, 'Amount of users to generate', 1000)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);
        $filename = $input->getArgument('file');
        $handle = fopen($filename, 'rb');
        $names = $surnames = [];
        while (($line = fgets($handle)) !== false) {
            [$name, $surname] = explode(' ', trim($line));
            $names[] = $name;
            $surnames[] = $surname;
        }

        $namesCount = count($names);
        $surnamesCount = count($surnames);
        $citiesCount = count(self::$citiesList);
        $amountToGenerate = $input->getOption('amount');
        $encodedPassword = $this->passwordEncoder->encodePassword(new User(), 123);
        $this->logger->info('AmountToBatchGenerate: ' . $amountToGenerate);

        $userSettingsStmt = $this->conn->prepare("INSERT INTO user_settings (username, password) VALUES (:username, :password);");
        $userInfoStmt = $this->conn->prepare('INSERT INTO user_info (user_id, name, surname, age, gender, city) VALUES (:userId, :name, :surname, :age, :gender, :city);');
        $userInterestsStmt = $this->conn->prepare("INSERT INTO user_has_interest (interest_id, user_id) VALUES (:interestId, :userId);");
        $this->interestService->addInterests(self::$interestsValues);

        $allInterests = $this->interestService->getAllInterests();
        $interestsCount = count($allInterests);

        while ($amountToGenerate > 0) {
            try {
                $batchAge = random_int(self::MIN_AGE, self::MAX_AGE);
                $batchGender = self::$genders[random_int(0, 1)];
                $batchCity = self::$citiesList[random_int(0, $citiesCount-1)];
                $batchInterestsAmountForUser = random_int(1, 7);
                $batchUserInterests = [];
                for ($i = 0; $i < $batchInterestsAmountForUser; $i++) {
                    $batchUserInterests[] = $allInterests[random_int(0, $interestsCount-1)];
                }
                for ($currentBatchSize = self::BATCH_SIZE; $currentBatchSize > 0; $currentBatchSize--) {
                    if ($amountToGenerate <= 0) {
                        break;
                    }
                    $name = $names[random_int(0, $namesCount-1)];
                    $surname = $surnames[random_int(0, $surnamesCount-1)];
                    $username = $name.$surname.'_'.time().'_'.uniqid('', false);

                    $this->conn->beginTransaction();
                    try {
                        $userSettingsStmt->execute([
                            ':username' => $username,
                            ':password' => $encodedPassword
                        ]);
                        $userId = (int)$this->conn->lastInsertId();
                    } catch (UniqueConstraintViolationException $e) {
                        $this->conn->rollBack();
                        throw new UsernameInUseRegistrationException('Username is already in use');
                    }

                    $userInfoStmt->execute([
                        ':userId' => $userId,
                        ':name' => $name,
                        ':surname' => $surname,
                        ':age' => $batchAge,
                        ':gender' => $batchGender,
                        ':city' => $batchCity,
                    ]);

                    foreach ($batchUserInterests as $interestData) {
                        if (!isset($interestData['id'])) {
                            continue;
                        }

                        $userInterestsStmt->execute([
                            ':interestId' => $interestData['id'],
                            ':userId' => $userId
                        ]);
                    }
                    $this->conn->commit();

                    $amountToGenerate--;
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        fclose($handle);

        $endTime = microtime(true);
        $this->logger->info('Start time: ' . $startTime . ', end time: ' . $endTime);
        $this->logger->info('Execution time (batch): ' . ($endTime - $startTime));
        return 0;
    }
}
