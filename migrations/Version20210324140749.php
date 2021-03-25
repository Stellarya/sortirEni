<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210324140749 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE groupe_participant (groupe_id INT NOT NULL, participant_id INT NOT NULL, INDEX IDX_584218DF7A45358C (groupe_id), INDEX IDX_584218DF9D1C3019 (participant_id), PRIMARY KEY(groupe_id, participant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE groupe_participant ADD CONSTRAINT FK_584218DF7A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE groupe_participant ADD CONSTRAINT FK_584218DF9D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE participant_groupe');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE participant_groupe (participant_id INT NOT NULL, groupe_id INT NOT NULL, INDEX IDX_B6C532A09D1C3019 (participant_id), INDEX IDX_B6C532A07A45358C (groupe_id), PRIMARY KEY(participant_id, groupe_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE participant_groupe ADD CONSTRAINT FK_B6C532A07A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participant_groupe ADD CONSTRAINT FK_B6C532A09D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE groupe_participant');
    }
}
