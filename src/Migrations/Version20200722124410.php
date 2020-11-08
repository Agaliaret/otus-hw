<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200722124410 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Добавлена альтернативная структура БД, с индексами двух видов';
    }

    public function up(Schema $schema) : void
    {
        // Создаём таблицы для тестирования составного индекса на поля name, surname в таблице user_info_wci
        $this->addSql('CREATE TABLE user_settings_wci (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_user_settings_wci_username (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB SELECT * FROM user_settings;');
        $this->addSql('CREATE TABLE friends_wci (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, friend_user_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB SELECT * FROM friends;');
        $this->addSql('ALTER TABLE friends_wci ADD CONSTRAINT FK_friends_wci_user_id FOREIGN KEY (user_id) REFERENCES user_settings_wci (id);');
        $this->addSql('ALTER TABLE friends_wci ADD CONSTRAINT FK_friends_wci_friend_user_id FOREIGN KEY (friend_user_id) REFERENCES user_settings_wci (id);');
        $this->addSql('CREATE TABLE user_info_wci (user_id INT NOT NULL, name VARCHAR(255) NOT NULL, surname VARCHAR(255) NOT NULL, age SMALLINT NOT NULL, gender VARCHAR(10) NOT NULL, city VARCHAR(50) NOT NULL, INDEX IDX_user_info_wci_name_surname (name, surname), PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB SELECT * FROM user_info;');
        $this->addSql('CREATE TABLE interest_wci (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_interest_wci_value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB SELECT * FROM interest;');
        $this->addSql('CREATE TABLE user_has_interest_wci (id INT AUTO_INCREMENT NOT NULL, interest_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB SELECT * FROM user_has_interest;');
        $this->addSql('ALTER TABLE user_has_interest_wci ADD CONSTRAINT FK_user_has_interest_wci_interest_id FOREIGN KEY (interest_id) REFERENCES interest_wci (id);');
        $this->addSql('ALTER TABLE user_has_interest_wci ADD CONSTRAINT FK_user_has_interest_wci_user_id FOREIGN KEY (user_id) REFERENCES user_settings_wci (id);');

        // Создаём таблицы для тестирования двух отдельных индексов на поля name, surname в таблице user_info_wsi
        $this->addSql('CREATE TABLE user_settings_wsi (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_user_settings_wsi_username (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB SELECT * FROM user_settings;');
        $this->addSql('CREATE TABLE friends_wsi (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, friend_user_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB SELECT * FROM friends;');
        $this->addSql('ALTER TABLE friends_wsi ADD CONSTRAINT FK_friends_wsi_user_id FOREIGN KEY (user_id) REFERENCES user_settings_wsi (id);');
        $this->addSql('ALTER TABLE friends_wsi ADD CONSTRAINT FK_friends_wsi_friend_user_id FOREIGN KEY (friend_user_id) REFERENCES user_settings_wsi (id);');
        $this->addSql('CREATE TABLE user_info_wsi (user_id INT NOT NULL, name VARCHAR(255) NOT NULL, surname VARCHAR(255) NOT NULL, age SMALLINT NOT NULL, gender VARCHAR(10) NOT NULL, city VARCHAR(50) NOT NULL, INDEX IDX_user_info_wsi_name (name), INDEX IDX_user_info_wsi_surname (surname), PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB SELECT * FROM user_info;');
        $this->addSql('CREATE TABLE interest_wsi (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_interest_wsi_value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB SELECT * FROM interest;');
        $this->addSql('CREATE TABLE user_has_interest_wsi (id INT AUTO_INCREMENT NOT NULL, interest_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB SELECT * FROM user_has_interest;');
        $this->addSql('ALTER TABLE user_has_interest_wsi ADD CONSTRAINT FK_user_has_interest_wsi_interest_id FOREIGN KEY (interest_id) REFERENCES interest_wsi (id);');
        $this->addSql('ALTER TABLE user_has_interest_wsi ADD CONSTRAINT FK_user_has_interest_wsi_user_id FOREIGN KEY (user_id) REFERENCES user_settings_wsi (id);');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE friends_wci');
        $this->addSql('DROP TABLE user_has_interest_wci');
        $this->addSql('DROP TABLE user_settings_wci');
        $this->addSql('DROP TABLE user_info_wci');
        $this->addSql('DROP TABLE interest_wci');

        $this->addSql('DROP TABLE friends_wsi');
        $this->addSql('DROP TABLE user_has_interest_wsi');
        $this->addSql('DROP TABLE user_settings_wsi');
        $this->addSql('DROP TABLE user_info_wsi');
        $this->addSql('DROP TABLE interest_wsi');
    }
}
