<?php

namespace OtusHw\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OtusHw\Exception\UsernameInUseRegistrationException;
use OtusHw\Security\User;
use OtusHw\Service\InterestService;
use OtusHw\Service\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UsersGenerateCommand extends Command
{
    protected const MIN_AGE = 18;
    protected const MAX_AGE = 150;

    protected static $defaultName = 'users:generate';

    protected static array $citiesList = ['Izhevsk', 'Moscow', 'Kazan', 'Vorkuta', 'Bishkek', 'Ryazan', 'Kerch', 'Ufa'];

    protected static array $genders = ['male', 'female'];

    protected static array $interestsValues = [
        'Anime', 'Astronomy', 'Baking', 'Books', 'Bowling', 'Calligraphy', 'Checkmates', 'Chess', 'Cooking', 'Craft',
        'Cryptography', 'Dance', 'Digital Arts', 'DIY', 'Drawing', 'Driving', 'Esports', 'Fishing', 'Games', 'Go',
        'Gunsmithing', 'Hide\'n\'Seek', 'Homebrewing', 'Hunting', 'Laser Tag', 'Leather Crafting', 'Lego', 'Mahjong',
        'Music', 'Origami', 'Photography', 'Poker', 'Programming', 'Puzzles', 'Quizzes', 'Random Stuff', 'Singing',
        'SQL Injections\'); DROP TABLE user; --MWA-HA-HA', 'Surfing', 'Swimming', 'TCG', 'Tennis', 'Tourism', 'Yoga',
    ];

    /** @var UserPasswordEncoderInterface */
    protected UserPasswordEncoderInterface $passwordEncoder;

    /** @var UserService */
    protected UserService $userService;

    /** @var InterestService */
    protected InterestService $interestService;

    /** @var Connection */
    protected Connection $conn;

    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /**
     * UsersGenerateCommand constructor.
     *
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param UserService $userService
     * @param InterestService $interestService
     * @param Connection $conn
     * @param LoggerInterface $logger
     */
    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        UserService $userService,
        InterestService $interestService,
        Connection $conn,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->passwordEncoder = $passwordEncoder;
        $this->userService = $userService;
        $this->interestService = $interestService;
        $this->conn = $conn;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Generates random users names combinations from user names list and user\'s info')
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
        $this->logger->info('AmountToGenerate: ' . $amountToGenerate);

        $userSettingsStmt = $this->conn->prepare("INSERT INTO user_settings (username, password) VALUES (:username, :password);");
        $userInfoStmt = $this->conn->prepare('INSERT INTO user_info (user_id, name, surname, age, gender, city) VALUES (:userId, :name, :surname, :age, :gender, :city);');
        $this->interestService->addInterests(self::$interestsValues);

        $allInterests = $this->interestService->getAllInterests();
        $interestsCount = count($allInterests);

        while ($amountToGenerate > 0) {
            try {
                $name = $names[random_int(0, $namesCount-1)];
                $surname = $surnames[random_int(0, $surnamesCount-1)];
                $age = random_int(self::MIN_AGE, self::MAX_AGE);
                $username = $name.$surname.'_'.time().'_'.uniqid('', false);
                $gender = self::$genders[random_int(0, 1)];
                $city = self::$citiesList[random_int(0, $citiesCount-1)];
                $interestsAmountForUser = random_int(1, 7);
                $userInterests = [];
                for ($i = 0; $i < $interestsAmountForUser; $i++) {
                    $userInterests[] = $allInterests[random_int(0, $interestsCount-1)];
                }

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
                    ':age' => $age,
                    ':gender' => $gender,
                    ':city' => $city,
                ]);

                $interestsValuesPairsForQuery = [];
                foreach ($userInterests as $interestData) {
                    if (!isset($interestData['id'])) {
                        continue;
                    }

                    $interestsValuesPairsForQuery[] = "({$interestData['id']}, $userId)";
                }

                $addInterestsQuery = 'INSERT INTO user_has_interest (interest_id, user_id) VALUES ' .
                    implode(',', $interestsValuesPairsForQuery);
                $userInterestsStmt = $this->conn->prepare($addInterestsQuery);
                $userInterestsStmt->execute();

                $this->conn->commit();

                $amountToGenerate--;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        fclose($handle);

        $endTime = microtime(true);
        $this->logger->info('Start time: ' . $startTime . ', end time: ' . $endTime);
        $this->logger->info('Execution time: ' . ($endTime - $startTime));
        return 0;
    }
}
