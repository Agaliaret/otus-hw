<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200707151810 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Добавлена начальная структура БД';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_settings (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_user_settings_username (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE friends (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, friend_user_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_info (user_id INT NOT NULL, name VARCHAR(255) NOT NULL, surname VARCHAR(255) NOT NULL, age SMALLINT NOT NULL, gender VARCHAR(10) NOT NULL, city VARCHAR(50) NOT NULL, PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE interest (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_interest_value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_has_interest (id INT AUTO_INCREMENT NOT NULL, interest_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user_settings');
        $this->addSql('DROP TABLE friends');
        $this->addSql('DROP TABLE user_info');
        $this->addSql('DROP TABLE interest');
        $this->addSql('DROP TABLE user_has_interest');
    }
}
