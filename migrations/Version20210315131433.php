<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210315131433 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("INSERT INTO `etat`(`libelle`) VALUES ('Créée');");
        $this->addSql("INSERT INTO `etat`(`libelle`) VALUES ('Ouverte');");
        $this->addSql("INSERT INTO `etat`(`libelle`) VALUES ('Clôturée');");
        $this->addSql("INSERT INTO `etat`(`libelle`) VALUES ('Activité en cours');");
        $this->addSql("INSERT INTO `etat`(`libelle`) VALUES ('Passée');");
        $this->addSql("INSERT INTO `etat`(`libelle`) VALUES ('Annulée');");

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
