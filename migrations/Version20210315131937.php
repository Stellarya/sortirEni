<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210315131937 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("INSERT INTO `ville`(`nom`, `code_postal`) VALUES ('Rennes',35000)");
        $this->addSql("INSERT INTO `ville`(`nom`, `code_postal`) VALUES ('Brest',29200)");
        $this->addSql("INSERT INTO `ville`(`nom`, `code_postal`) VALUES ('Vitré',35500)");
        $this->addSql("INSERT INTO `ville`(`nom`, `code_postal`) VALUES ('Nantes',44000)");
        $this->addSql("INSERT INTO `ville`(`nom`, `code_postal`) VALUES ('Niort',79000)");
        $this->addSql("INSERT INTO `ville`(`nom`, `code_postal`) VALUES ('Quimper',29000)");

        $this->addSql("INSERT INTO `site`(`nom`) VALUES ('Chartres de Bretagne')");
        $this->addSql("INSERT INTO `site`(`nom`) VALUES ('La Roche sur Yon')");
        $this->addSql("INSERT INTO `site`(`nom`) VALUES ('Saint Herblain')");

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
